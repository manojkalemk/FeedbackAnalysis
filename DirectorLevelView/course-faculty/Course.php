<?php
session_start();

include "../../db.php";
$courseArray = [];
$facultyCourseWiseAverageArray = [];
$courseFacultyAverages = [];
$courseId = 0;
$validAverageCount = 0;
$feedbackType = '';

    if(isset($_GET['courseId']) && isset($_SESSION['facultyCourseWiseAverageArray']) && isset($_SESSION['courseFacultyAverages']) && isset($_SESSION['feedbackType'])) {
        $courseId = $_GET['courseId'];
        $facultyCourseWiseAverageArray = json_decode($_SESSION['facultyCourseWiseAverageArray'], true);
        $courseFacultyAverages = json_decode($_SESSION['courseFacultyAverages'], true);
        // $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        $selectedAcademicYear = $_SESSION['selectedAcademicYear'];
        $feedbackType = $_SESSION['feedbackType'];
    
        // $program = $conn->query("SELECT 
        //         cat1.id as pid,
        //         cat1.name AS PgmName,
        //         cat2.name AS Batch,
        //         cat3.name AS Semester,
        //         cat4.name AS Specialization,
        //         c.fullname AS Subject,
        //         c.id as c_id
        //         FROM  {$prefix}course_categories cat1
        //         LEFT JOIN  {$prefix}course_categories cat2 ON cat1.id = cat2.parent
        //         LEFT JOIN  {$prefix}course_categories cat3 ON cat2.id = cat3.parent
        //         LEFT JOIN  {$prefix}course_categories cat4 ON cat3.id = cat4.parent
        //         LEFT JOIN  {$prefix}course_categories cat5 ON cat4.id = cat5.parent
        //         LEFT JOIN  {$prefix}course c ON (c.category=cat4.id OR c.category=cat5.id OR c.category=cat1.id OR c.category=cat2.id OR c.category=cat3.id )
        //         WHERE cat1.parent = 0 and c.id is not null and c.id = $courseId ORDER BY c.fullname");


$program = $conn->query("WITH RECURSIVE category_path AS (
                                    -- Step 1: Start from the course's category
                                    SELECT 
                                        c.id AS course_id,
                                        cat.id AS category_id,
                                        cat.name AS category_name,
                                        c.fullname AS subject_name,
                                        cat.parent,
                                        1 AS level
                                    FROM {$prefix}course c
                                    JOIN {$prefix}course_categories cat ON c.category = cat.id
                                    WHERE c.id = $courseId  -- Replace dynamically

                                    UNION ALL

                                    -- Step 2: Climb up the hierarchy
                                    SELECT 
                                        cp.course_id,
                                        cat.id AS category_id,
                                        cat.name AS category_name,
                                        cp.subject_name,              -- Propagate subject_name from previous level
                                        cat.parent,
                                        cp.level + 1 AS level
                                    FROM category_path cp
                                    JOIN {$prefix}course_categories cat ON cat.id = cp.parent
                                )

                                -- Final step: pivot the levels into columns
                                SELECT 
                                    course_id,
                                    MAX(CASE WHEN level = max_level THEN category_name END) AS PgmName,
                                    MAX(CASE WHEN level = max_level - 1 THEN category_name END) AS Batch,
                                    MAX(CASE WHEN level = max_level - 2 THEN category_name END) AS Semester,
                                    MAX(CASE WHEN level = max_level - 3 THEN category_name END) AS Specialization,
                                    -- MAX(CASE WHEN level = 1 THEN category_name END) AS SubjectName,
                                    MAX(subject_name) AS SubjectName
                                FROM (
                                    SELECT cp.*, 
                                        (SELECT MAX(level) FROM category_path WHERE course_id = cp.course_id) AS max_level
                                    FROM category_path cp
                                ) AS leveled
                                GROUP BY course_id;");


        while($row = mysqli_fetch_assoc($program)) {
            $courseArray['CourseName'] = $row['SubjectName'];
            $courseArray['Program'] = $row['PgmName'];
            if($row['Batch']==''){
                $courseArray['Batch'] = "No Batch";    
            }else{
                $courseArray['Batch'] = $row['Batch'];
            }

            if($row['Semester'] == '') {
                $courseArray['Semester'] = "No Semester";
            }else{
                $courseArray['Semester'] = $row['Semester'];
            }

            if($row['Specialization'] == '') {
                $courseArray['Specialization'] = "No Specialization";
            }else{
                $courseArray['Specialization'] = $row['Specialization'];
            }
            
        }

        // echo "<pre>";
        // print_r($courseArray);
        // echo "</pre>";
        

        $courseCode = $conn->query(
            "SELECT 
                case 
                    when f.name like '%Course Code%' then 'Course Code'
                    when f.shortname like 'total_credit' then 'Total Credit'
                end as label,
                d.value as Value 
                from {$prefix}customfield_data d 
                    join {$prefix}customfield_field f on f.id = d.fieldid 
                    where (f.name like '%Course Code%' or f.shortname like '%total_credit%') and d.instanceid=$courseId;
        ");
        $courseArray['courseCode'] = NULL;
        $courseArray['totalCredit'] = NULL;

        if($courseCode -> num_rows > 0) {
            while($row = mysqli_fetch_assoc($courseCode)) {
                if($row['label'] == 'Course Code') {
                    if($row['Value'] == '')
                    {
                        $courseArray['courseCode'] = "No Course Code Available";    
                    }else
                        $courseArray['courseCode'] = $row['Value'];
                }
                if($row['label'] == 'Total Credit') {
                    $courseArray['totalCredit'] = $row['Value'];
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
        
    } else{
        echo "Session Not started Yet";
        exit;
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $courseArray['CourseName']; ?></title>
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
            <h1><?php echo $courseArray['CourseName']; ?></h1>
            <h3><?php echo "(A.Y. " . $selectedAcademicYear . ")"; ?></h3>
        </div>

        <div class="course-details">
            <table border='1' id='table1'>
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Semester</th>
                        <th>Specialization</th>
                        <th>Course Code</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $courseArray['Program']; ?></td>
                        <td><?php echo $courseArray['Batch']; ?></td>
                        <td><?php echo $courseArray['Semester']; ?></td>
                        <td><?php echo $courseArray['Specialization']; ?></td>
                        <td><?php echo $courseArray['courseCode']; ?></td>
                        <td><?php echo $courseArray['totalCredit']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="header">
            <h1>Analysis</h1>
        </div>

        <div class="course-info">
            <table border="1" id="table2">
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Teacher Average</th>
                        <th>Groups/Section Name</th>
                        <th>Groups/Section Average</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $courseAverage = 0;
                        $validFacultyAverageCount = 0;
                        $enrolledFacultyData = getFacultyData($conn, $prefix, $courseId);
                        if(is_array($enrolledFacultyData)) {
                            foreach($enrolledFacultyData as $facultyId => $groupsArray) {
    
                                $fullName = $conn->query("SELECT concat(firstname, ' ', lastname) as FullName from {$prefix}user where id=$facultyId") -> fetch_assoc()['FullName'];
                                echo "<tr>";
                                $firstRow = true;
                                if(is_array($groupsArray)) {
                                    echo "<td rowspan='" . count($groupsArray) . "' onClick='facultyAnalysis($facultyId)' class='faculty'>" . $fullName . "</td>";
                                    if(isset($courseFacultyAverages[$courseId]['faculty'][$facultyId])) {
                                        $validFacultyAverageCount++;
                                        $courseAverage += $courseFacultyAverages[$courseId]['faculty'][$facultyId];
                                        $average = number_format($courseFacultyAverages[$courseId]['faculty'][$facultyId], 2);
                                        $color = getColor($average);
                                        echo "<td rowspan='" . count($groupsArray) . "'>
                                            <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "
                                            </span>
                                        </td>";
                                    } else{
                                        echo "<td rowspan='" . count($groupsArray) . "'> - </td>";
                                    }
                                    foreach($groupsArray as $groupName) {
                                        $feedback = $conn->query("SELECT id from {$prefix}feedback where name like '%$groupName%' and name like '%$fullName%' and name like '%Student Feedback- Faculty%' and name not like '%$feedbackType%' and course = $courseId");
                                        if($feedback -> num_rows > 0) {
                                            $feedbackId = $feedback->fetch_assoc()['id'];
                                            
                                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId][$feedbackId])) {
                                                if(!$firstRow) {
                                                    echo "<tr>";
                                                }
                                                echo "<td>" . $groupName . "</td>";
                                                $average = number_format($facultyCourseWiseAverageArray[$facultyId][$courseId][$feedbackId]['weightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                                echo "</tr>";
                                                $firstRow=false;
                                            } else{
                                                if(!$firstRow) {
                                                    echo "<tr>";
                                                }
                                                echo "<td>" . $groupName . "</td>";
                                                echo "<td> No Feedback Available </td>";
                                                echo "</tr>";
                                                $firstRow = false;
                                            }
                                        } else{
                                            if(!$firstRow) {
                                                echo "<tr>";
                                            }
                                            echo "<td>" . $groupName . "</td>";
                                            echo "<td> No Feedback Available </td>";
                                            echo "</tr>";
                                            $firstRow = false;
                                        }
                                    }

                                } else{

                                    // Course Do not have an Group But student feedback Faculty Feedback is present with Faculty Name
                                    echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $fullName . "</td>";
                                    if(isset($courseFacultyAverages[$courseId]['faculty'][$facultyId])) {
                                        $courseAverage += $courseFacultyAverages[$courseId]['faculty'][$facultyId];
                                        $validFacultyAverageCount++;
                                        $average = number_format($courseFacultyAverages[$courseId]['faculty'][$facultyId], 2);
                                        $color = getColor($average);
                                        echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                    } else{
                                        echo "<td> - </td>";
                                    }
                                    echo "<td>" . $groupsArray . "</td>";
                                    if($groupsArray == "No Group") {
                                        $feedback = $conn->query("SELECT id from {$prefix}feedback where name like '%$fullName%' and name like '%Student Feedback- Faculty%'  and name not like '%$feedbackType%' and course=$courseId");
                                        
                                        if($feedback->num_rows>0) {
                                            $feedbackId = $feedback->fetch_assoc()['id'];
                                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId][$feedbackId]['weightage'])) {
                                                $average = number_format($facultyCourseWiseAverageArray[$facultyId][$courseId][$feedbackId]['weightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            }
                                        } else{
                                            echo "<td>No Feedback Available</td>";
                                        }

                                    } else{
                                        echo "<td> - </td>";
                                    }
                                }
                                echo "</tr>";
                            }
                        } else{
                            echo "<tr>";
                            echo "<td> No Faculty Enrolled </td>";
                            echo "<td> - </td>";
                            echo "<td> - </td>";
                            echo "<td> - </td>";
                            echo "</tr>";
                        }

                    ?>
                </tbody>
            </table>
            
            <div class="course-rating">
                <p>Course Rating : <span><?php
                if($validFacultyAverageCount > 0) {
                    $validAverageCount = number_format($courseAverage / $validFacultyAverageCount, 2);
                    echo $validAverageCount;
                } else{
                    echo "0.0";
                    $validAverageCount = 0;
                }
                
                    ?></span></p>
            </div>
        </div>

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
    const courseName = "<?php echo $courseArray['CourseName']; ?>";
    const average = <?php echo $validAverageCount; ?>;
    let serverName = '<?php echo $fullServerName; ?>';
    let protocol = '<?php echo $protocol; ?>';
    let selectedAcademicYear = '<?php echo $selectedAcademicYear; ?>';
    document.getElementById('download').addEventListener('click', function () {
        const table1 = document.querySelector("#table1"); 
        const table2 = document.querySelector("#table2");

        const workbook = XLSX.utils.book_new();
        const worksheetData = [];

        worksheetData.push([courseName]);
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
        worksheetData.push(["Course Rating", average]);

        const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

        XLSX.utils.book_append_sheet(workbook, worksheet, "Course Analysis");

        XLSX.writeFile(workbook, `${courseName}.xlsx`);
    });

    function facultyAnalysis(facultyId) {
        window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Faculty.php?facultyId=${facultyId}`, "_blank");
    }
</script>