<?php
session_start();
include "../../db.php";

    $facultyArray = [];
    $facultyCourseWiseAverageArray = [];
    $courseFacultyAverages = [];
    $feedbackType = '';
 

    if(isset($_GET['facultyId']) && isset($_SESSION['facultyCourseWiseAverageArray']) && isset($_SESSION['courseFacultyAverages']) && isset($_SESSION['feedbackType'])) {
        $facultyId = $_GET['facultyId'];
        $facultyCourseWiseAverageArray = json_decode($_SESSION['facultyCourseWiseAverageArray'], true);
        $courseFacultyAverages = json_decode($_SESSION['courseFacultyAverages'], true);
        $selectedAcademicYear = $_SESSION['selectedAcademicYear'];
        $feedbackType = $_SESSION['feedbackType'];

        $facultyDetails = $conn->query("SELECT username, timecreated, email, department, concat(firstname, ' ', lastname) as fullname from {$prefix}user where id=$facultyId");
        
        $facultyDetails = $facultyDetails->fetch_assoc();
        $facultyArray['facultyname'] = $facultyDetails['fullname'];
        $facultyArray['username'] = $facultyDetails['username'];
        $facultyArray['email'] = $facultyDetails['email'];
        $facultyArray['department'] = $facultyDetails['department'];
    
        $moreDetails = $conn->query("SELECT id.userid, 
                                    case 
                                        when fi.name like '%Gender%' then 'Gender'
                                        when fi.name like '%User Category%' then 'User Category'
                                        when fi.name like '%Designation%' then 'Designation'
                                        when fi.name like '%Admission Date%' then 'Admission Date'
                                    end as label,
                                    id.fieldid, id.data as value
                                    from {$prefix}user_info_data id
                                    left join {$prefix}user_info_field as fi on fi.id = id.fieldid
                                    where id.userid=$facultyId and (fi.name like '%Gender%' or fi.name like '%User Category%' or fi.name like 'Designation' or fi.name like '%Admission Date%')");

            
        $facultyArray['Gender'] = NULL;
        $facultyArray['User Category'] = NULL;
        $facultyArray['Designation'] = NULL;
    
        while($row = mysqli_fetch_assoc($moreDetails)) {
            if($row['label'] == 'Gender') {
                $facultyArray['Gender'] = $row['value'];
            }
            if($row['label'] == 'User Category') {
                $facultyArray['User Category'] = $row['value'];
            }
            if($row['label'] == 'Designation') {
                $facultyArray['Designation'] = $row['value'];
            }
            if($row['label'] == 'Admission Date') {
                if($row['value'] == 0) {
                    $facultyArray['Admission Date'] = $row['value'];
                } else{
                    $facultyArray['Admission Date'] = getExperience($row['value']);
                }
            }
        }
        function getColor($average) {
            if($average >= 4.0) {
                $color = "#7ff67f";
            } else if($average < 4.0 && $average >= 2.5) {
                $color = "#ffff009e";
            } else{
                $color = "#ff7676";
            }
            return $color;
        }

        $serverName = $_SERVER['SERVER_NAME'];
        $serverPort = $_SERVER['SERVER_PORT'];

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
        if (($protocol === 'http' && $serverPort == 80) || ($protocol === 'https' && $serverPort == 443)) {
            $fullServerName = $serverName . "/attendance-report";
        } else {
            $fullServerName = $serverName . ':' . $serverPort . "/attendance-report";
        }
    } else {
        echo "Session Not started Yet";
        // print_r($_SESSION);
        exit;
    }

    function getExperience($timestamp) {
        $startDate = new DateTime('@' . $timestamp); 
        $startDate->setTimezone(new DateTimeZone('UTC')); 

        $endDate = new DateTime();

        $interval = $startDate->diff($endDate);

        $experience =  $interval->y . " year, " . $interval->m . " month";
        return $experience;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $facultyArray['facultyname']; ?></title>
    <link rel="stylesheet" href="courseFaculty.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" href="../../assets/logo.png" type="image/x-icon">
</head>
<body>
    <div class="course-container">
        <div class="navbar">    
            <h1>SYMBIOSIS</h1>
            <h2>Teacher Feedback Analysis</h2>
            <p id='download'>Download</p>
        </div>

        <div class="header">
            <h1>
            <?php 
                if($facultyArray['Gender'] == "Male" or $facultyArray['Gender'] == "male") {
                    echo "Mr. " . $facultyArray['facultyname'];
                } else if($facultyArray['Gender'] == "female" or $facultyArray['Gender'] == "Female"){
                    echo "Mrs. " . $facultyArray['facultyname']; 
                } else{
                    echo $facultyArray['facultyname'];
                }
                    
            ?>            
            </h1>
            <h3><?php echo "(A.Y. " . $selectedAcademicYear . ")"; ?></h3>
        </div>

        <div class="faculty-details">
            <table border="1" id="table1">
                <thead>
                    <tr>
                        <th>Employee Id</th>
                        <th>Email Id    </th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Year of Experience</th>
                        <th>Gender</th>
                        <th>Type of Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $facultyArray['username']; ?></td>
                        <td><?php echo $facultyArray['email']; ?></td>
                        <td><?php echo $facultyArray['Designation']; ?></td>
                        <td><?php echo $facultyArray['department']; ?></td>
                        <td><?php echo $facultyArray['Admission Date']; ?></td>
                        <td><?php echo $facultyArray['Gender']; ?></td>
                        <td><?php echo $facultyArray['User Category']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="header">
            <h1>Analysis</h1>
        </div>

        <div class="faculty-info">
            <table border="1" id="table2">
                <thead>
                    <tr>
                        <th>CourseName</th>
                        <th>Course Average</th>
                        <th>Groups / Section</th>
                        <th>Groups / Section Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $facultyAverage = 0;
                        $validCourseAverageCount = 0;
                        if(isset($facultyCourseWiseAverageArray[$facultyId])) {
                            foreach($facultyCourseWiseAverageArray[$facultyId] as $courseId => $feedbackDetails) {
                                if($courseId != 'facultyWeightage') {
                                    $enrolledFacultyData = getFacultyData($conn, $prefix, $courseId);
                                    
                                    // echo "<pre>";
                                    // print_r($enrolledFacultyData);
                                    // echo "</pre>";
                                    
                                    echo "<tr>";
                                    $firstRow = true;
                                    $courseName=$conn->query("SELECT fullname from {$prefix}course where id=$courseId")->fetch_assoc()['fullname'];
    
                                    if(is_array($enrolledFacultyData[$facultyId])) {
                                        echo "<td rowspan='" . count($enrolledFacultyData[$facultyId]) . "' onClick='courseAnalysis($courseId)' class='course'>" . $courseName ."</td>";
                                        $forCourseAverageRowSpan = true;
                                        foreach($enrolledFacultyData[$facultyId] as $groupName) {
                                            $feedback = $conn->query("SELECT id from {$prefix}feedback 
                                                        where name like '%$groupName%' and 
                                                        name like '%{$facultyArray['facultyname']}%' and 
                                                        name like '%Student Feedback- Faculty%'  and name not like '%$feedbackType%' and course = $courseId");
                                            if($feedback -> num_rows > 0) {
                                                $feedbackId = $feedback->fetch_assoc()['id'];
                                                if(isset($feedbackDetails[$feedbackId])) {
                                                    if(!$firstRow) {
                                                        echo "<tr>";
                                                    }
                                                    if($forCourseAverageRowSpan) {
                                                        $facultyAverage += $feedbackDetails['facultyCourseWeightage'];
                                                        $average = number_format($feedbackDetails['facultyCourseWeightage'], 2);
                                                        $color = getColor($average);
                                                        echo "<td rowspan='" . count($enrolledFacultyData[$facultyId]) . "'>
                                                            <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                        </td>";
                                                        $forCourseAverageRowSpan = false;
                                                        $validCourseAverageCount++;
                                                    }
                                                    echo "<td> $groupName </td>";
                                                    $average = number_format($feedbackDetails[$feedbackId]['weightage'], 2);
                                                    $color = getColor($average);
                                                    echo "<td>
                                                        <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                    </td>";
                                                    echo "</tr>";
                                                    $firstRow = false;
                                                }
    
                                            } else{
                                                if(!$firstRow) {
                                                    echo "<tr>";
                                                }
                                                echo "<td> No Feedback Available </td>";
                                                echo "<td> $groupName </td>";
                                                echo "<td> No Feedback Available </td>";
                                                echo "</tr>";
                                                $firstRow = false;
                                            }
                                        } 
                                    } else{
                                        echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName ."</td>";
                                        if($enrolledFacultyData[$facultyId] == "No Group") {
                                            $facultyAverage += $feedbackDetails['facultyCourseWeightage'];

                                            $average = number_format($feedbackDetails['facultyCourseWeightage'], 2);
                                            $color = getColor($average);
                                            echo "<td>
                                                <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                            </td>";
                                            echo "<td>" . $enrolledFacultyData[$facultyId] . "</td>";
                                            echo "<td>
                                                <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                            </td>";
                                            $validCourseAverageCount++;
                                        } else{
                                            echo "<td> - </td>";
                                            echo "<td> No Group Assigned </td>";
                                            echo "<td> - </td>";
                                        }
                                    }
                                    echo "</tr>";
                                }
                            }
                        } else{
                            echo "<tr>";
                            echo "<td> - </td>";
                            echo "<td> - </td>";
                            echo "<td> - </td>";
                            echo "<td> - </td>";
                            echo "</tr>";
                        }
                    ?>
                </tbody>
            </table>

            <div class="faculty-rating">
                <p>Teacher Rating : <span><?php
                echo number_format($facultyAverage / $validCourseAverageCount, 2);
                 ?></span></p>
            </div>
        </div>
        
        <div class="header">
            <h1>In Detail Analysis</h1>
        </div>
        <?php include "QuestionWiseAnalysis.php"; ?>
    </div>
</body> 
</html>

<?php
    function getFacultyData($conn, $prefix, $courseId) {
        $facultyData = [];
        $enrolledFaculty = $conn->query("SELECT ra.roleid, ra.userid as userid, cc.id as courseid, cc.fullname from {$prefix}role_assignments ra 
                                            join {$prefix}context con on con.id = ra.contextid
                                            join {$prefix}course cc on cc.id = con.instanceid
                                            where con.contextlevel = 50 and ra.roleid = 3 and cc.id=$courseId");
                                        
        if($enrolledFaculty -> num_rows > 0) {
            while($faculty = mysqli_fetch_assoc($enrolledFaculty)) {

                $groupAvailableToCourse = $conn->query("SELECT id from {$prefix}groups where courseid=$courseId");
                if($groupAvailableToCourse -> num_rows > 0) {
                    $groupQuery = $conn -> query("SELECT u.firstname, u.lastname, g.name as gname, g.id as id
                        FROM {$prefix}user as u
                        join {$prefix}groups_members as ra on ra.userid = u.id
                        join {$prefix}groups as g on ra.groupid = g.id
                        where u.id={$faculty['userid']} and g.courseid=$courseId
                        group by g.id");
                    
                    // $groupQuery = $conn->query("SELECT 
                    //                         u.firstname, u.lastname, g.name AS gname, g.id AS id, f.name
                    //                         FROM sitpu_user AS u
                    //                         JOIN sitpu_groups_members AS ra ON ra.userid = u.id
                    //                         JOIN sitpu_groups AS g ON ra.groupid = g.id
                    //                         JOIN sitpu_feedback f ON f.course = g.courseid
                    //                         WHERE u.id = {$faculty['userid']}
                    //                         AND g.courseid = $courseId
                    //                         AND f.name NOT LIKE '%mid%' 
                    //                         AND f.name LIKE '%Student Feedback- Faculty%' 
                    //                         AND f.name LIKE CONCAT('%', g.name, ' : ', u.firstname, ' ', u.lastname, '%')
                    //                         GROUP BY f.id, g.name;");


                    if($groupQuery -> num_rows > 0) {
                        while($group = mysqli_fetch_assoc($groupQuery)) {
                            $facultyData[$faculty['userid']][] = $group['gname'];

                        }
                    } else{
                        $facultyData[$faculty['userid']] = "No Group Assigned";
                    }
                    
                } else{
                    $facultyData[$faculty['userid']] = "No Group";
                }
            }
        } else{
            $facultyData = "No Faculty Enrolled";
        }


        return $facultyData;
    }
?>

<script>
    const facultyName = "<?php echo $facultyArray['facultyname']; ?>";
    const facultyAverage = "<?php echo number_format($facultyAverage / $validCourseAverageCount, 2); ?>";
    let serverName = '<?php echo $fullServerName; ?>';
    let protocol = '<?php echo $protocol; ?>';
    let selectedAcademicYear = '<?php echo $selectedAcademicYear; ?>';
    document.getElementById('download').addEventListener('click', function () {
        const table1 = document.querySelector("#table1"); 
        const table2 = document.querySelector("#table2");

        const workbook = XLSX.utils.book_new();
        const worksheetData = [];

        worksheetData.push([facultyName]);
        worksheetData.push([selectedAcademicYear]);
        worksheetData.push([]);
        const table1Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table1), { header: 1 });
        worksheetData.push(...table1Data);
        worksheetData.push([]);

        worksheetData.push(["Analysis"]);
        worksheetData.push([]);
        const table2Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table2), { header: 1 });
        worksheetData.push(...table2Data);
        worksheetData.push([]);
        worksheetData.push(["Faculty Rating", facultyAverage]);

        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

        XLSX.utils.book_append_sheet(workbook, worksheet, "Faculty Analysis");

        XLSX.writeFile(workbook, `${facultyName}.xlsx`);
    });
    function courseAnalysis(courseId) {
        window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Course.php?courseId=${courseId}`, "_blank");
    }

    document.querySelectorAll('.feedback-questions-container').forEach((element) => {
        element.addEventListener('click', function() {
            const id = element.id;
            const questionsContainer = document.getElementById(`${id}-question`);
            const image = element.querySelector('img');

            if (!questionsContainer.classList.contains('active')) {
            // Show the questions container with animation
                questionsContainer.classList.add('active');
                element.querySelector('img').style.transform = 'rotate(180deg)';
            } else {
                // Hide the questions container with animation
                questionsContainer.classList.remove('active');
                element.querySelector('img').style.transform = 'rotate(0deg)';
            }
        });
    });

</script>