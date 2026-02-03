<?php
    include "../db.php";
    $protocol = '';
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        if(isset($_POST['programName']) && isset($_POST['courseInProgram']) && isset($_POST['courseFacultyAverage']) && isset($_POST['facultyCourseAverage']) && isset($_POST['feedbackType'])) {
            $programName = $_POST['programName'];
            // $coursesInProgram = json_decode($_POST['courseInProgram'], true);
            $courseFacultyAverage = json_decode($_POST['courseFacultyAverage'], true);
            $facultyCourseAverage = json_decode($_POST['facultyCourseAverage'], true);
            $selectedAcademicYear = $_POST['selectedAcademicYear'];


            $rawData = $_POST['courseInProgram']; 
            $feedbackType = $_POST['feedbackType'];
            $_SESSION['feedbackType']=$feedbackType;

            $cleanData = preg_replace('/[[:cntrl:]]/', '', $rawData);
            $cleanData = trim($cleanData);
            $coursesInProgram = json_decode($cleanData, true);

            $serverName = $_SERVER['SERVER_NAME'];
            $serverPort = $_SERVER['SERVER_PORT'];

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            if (($protocol === 'http' && $serverPort == 80) || ($protocol === 'https' && $serverPort == 443)) {
                $fullServerName = $serverName . "/attendance-report";
            } else {
                $fullServerName = $serverName . ':' . $serverPort . "/attendance-report";
            }
        } else{
            echo "Something Went Wrong";
        }
    } else{
        echo "404";
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
    
    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $programName; ?> Analysis</title>
    <link rel="stylesheet" href="ProgramAnalysis.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" href="../assets/logo.png" type="image/x-icon">
    
    <script>
        const serverName = '<?php echo $fullServerName; ?>';
        const protocol = '<?php echo $protocol; ?>';
        const selectedAcademicYear = '<?php echo $selectedAcademicYear; ?>';
        const programName = '<?php echo $programName; ?>';
        
        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("course")) {
                const id = e.target.dataset.courseId;
                window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Course.php?courseId=${id}`, "_blank");
            }
        
            if (e.target.classList.contains("faculty")) {
                const id = e.target.dataset.facultyId;
                window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Faculty.php?facultyId=${id}`, "_blank");
            }
        });
    </script>
</head>
<body>
    <div class="program-container">
    
        <div class="navbar">    
            <h1>SYMBIOSIS</h1>
            <h2>Teacher Feedback Analysis</h2>
            <p id='download'>Download</p>
        </div>
    
        <div class="data">
            <div class="header">
                <h1><?php echo $programName; ?></h1>
                <h3><?php echo "A.Y. " . $selectedAcademicYear; ?></h3>
            </div>
            <div class="program-analysis">
                <table border="1" id='singleProgramrogramTable'>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Course Average</th>
                            <th>Teacher Name</th>
                            <th>Groups/Section Name</th>
                            <th>Groups/Section Average</th>
                            <th>Teacher Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $validAverageCount=0;
                            $programAverage = 0;
                            foreach($coursesInProgram as $courseId => $courseArray) {
                                
                                // echo "I am in base for loop.  <br>";
                                
                                // if($courseId == 1785) {

                                    echo "<tr>";
                                    $enrolledFaculty = getFacultyData($conn, $prefix, $courseId);
                                    $rowSpan = 0;  
                                    if(is_array($enrolledFaculty)) {
                                        foreach($enrolledFaculty as $facultyName => $groupsDetails) {
                                            if(is_array($groupsDetails)) {
                                                $rowSpan += count($groupsDetails);
                                            } else{
                                                $rowSpan ++;
                                            }
                                        }
                                    } else{
                                        $rowSpan = 1;
                                    }

                                    // echo "<pre>";
                                    // print_r($enrolledFaculty);
                                    // echo "</pre>";
                                    $courseName = $conn->query("SELECT fullname from {$prefix}course where id=$courseId")->fetch_assoc()['fullname'];
                                    echo "<td rowspan='" . $rowSpan. "' data-course-id='$courseId' class='course'>" . $courseName . "</td>";

                                    if(is_array($courseArray)) {
                                        $validAverageCount++;
                                        $programAverage += $courseArray['courseWeightage'];
                                        $average = $courseArray['courseWeightage'];
                                        $color = getColor($average);
                                        echo "<td rowspan='" . $rowSpan. "'><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . number_format($courseArray['courseWeightage'], 2) . "</span></td>";
                                    } else{
                                        echo "<td rowspan='" . $rowSpan . "'> No Feedback Available</td>";
                                    }
                                    $firstRow = true;

                                    if(is_array($enrolledFaculty)) {
                                        // echo "I am in inner if condition : ";
                                        // print_r($enrolledFaculty);
                                        // echo "<br>";
                                        foreach($enrolledFaculty as $facultyId => $groupArray) {
                                            
                                            // echo "I am in inner for loop for enrolled faculty : $facultyId <br>";
                                            
                                            $fullName = $conn->query("SELECT concat(firstname, ' ', lastname) as FullName from {$prefix}user where id=$facultyId") -> fetch_assoc()['FullName'];
                                            if(is_array($groupArray)) {
                                                if(!$firstRow) {
                                                    echo "<tr>";
                                                    $firstRow=true;
                                                }
                                                // echo "<pre>";
                                                // print_r($groupArray);
                                                // echo "</pre>";

                                                echo "<td rowspan='".count($groupArray)."' data-faculty-id='$facultyId' class='faculty'>" . $fullName . "</td>";
                                                foreach($groupArray as $groupName) {
                                                    
                                                    // echo "I am in inneer for loop Group array : $groupName <br>";
                                                    
                                                    if(isset($courseArray['faculty'][$facultyId])) {
                                                        
                                                        
                                                        $feedbackModuleid = $conn -> query("SELECT id from {$prefix}modules where name='feedback'");
                                                        if($feedbackModuleid->num_rows<=0) {
                                                            throw new Exception("In FeedbackAvailabilty.php : Feedback Module Not Found");
                                                        }
                                                        $feedbackModuleid = $feedbackModuleid -> fetch_assoc()['id'];

                                                        $feedback = $conn ->query("SELECT f.id as id from {$prefix}feedback f 
                                                                join {$prefix}course_modules cm on cm.instance = f.id 
                                                                where cm.deletioninprogress = 0 
                                                                and cm.module = $feedbackModuleid 
                                                                and f.course = $courseId 
                                                                and f.name like '%$groupName%' 
                                                                and f.name like '%$fullName%' 
                                                                and ( f.name like '%Student%'
                                                                and f.name like '%Feedback%'
                                                                and f.name like '%Faculty%' ) 
                                                                and f.name not like '%$feedbackType%'
                                                                group by f.name");
                                                                
                                                        if($feedback -> num_rows > 0) {
                                                            $feedbackId = $feedback->fetch_assoc()['id'];
                                                            if($facultyCourseAverage[$facultyId][$courseId][$feedbackId]) {
                                                                
                                                                // echo $courseName . " " . $groupName . "<br>";

                                                                if(!$firstRow) {
                                                                    echo "<tr>";
                                                                }
                                                                echo "<td>" . $groupName . "</td>";

                                                                $average = number_format($facultyCourseAverage[$facultyId][$courseId][$feedbackId]['weightage'], 2);
                                                                $color = getColor($average);
                                                                echo "<td>
                                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                                </td>";

                                                                $average = number_format($courseArray['faculty'][$facultyId], 2);
                                                                $color = getColor($average);
                                                                echo "<td>
                                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                                </td>";
                                                                echo "</tr>";
                                                                $firstRow=false;
                                                            }
                                                        }
                                                    }else{
                                                        if(!$firstRow) {
                                                            echo "<tr>";
                                                        }
                                                        echo "<td> $groupName </td>";
                                                        echo "<td> - </td>";
                                                        echo "<td> - </td>";
                                                        echo "</tr>";
                                                        $firstRow=false;
                                                    }
                                                } 
                                            } else{
                                                echo "<td data-faculty-id='$facultyId' class='faculty'>" . $fullName . "</td>";
                                                echo "<td>" . $groupArray . "</td>";
                                                if($groupArray == "No Group") {
                                                    $feedback = $conn->query("
                                                        SELECT id 
                                                        from {$prefix}feedback 
                                                        where name like '%$fullName%' 
                                                        and (name like '%Student%' and name like '%Feedback%' and name like '%Faculty%') 
                                                        and name not like '%$feedbackType%' and 
                                                              course = $courseId");
                                                    if($feedback -> num_rows > 0) {
                                                        $feedbackId = $feedback->fetch_assoc()['id'];
                                                        if(isset($facultyCourseAverage[$facultyId][$courseId][$feedbackId]['weightage'])) {
                                                            $average = number_format($facultyCourseAverage[$facultyId][$courseId][$feedbackId]['weightage'], 2);
                                                            $color = getColor($average);
                                                            echo "<td>
                                                                <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "<span>
                                                            </td>";
                                                        } else{
                                                            echo "<td> No Feedback Available </td>";
                                                        }
                                                    } else{
                                                        echo "<td> - </td>";
                                                    }
                                                } else{
                                                    echo "<td> - </td>";
                                                }
                                                if(isset($courseArray['faculty'][$facultyId])) {
                                                    $average = number_format($courseArray['faculty'][$facultyId], 2);
                                                    $color = getColor($average);
                                                    echo "<td>
                                                        <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>". $average . "</span>
                                                    </td>";
                                                } else{
                                                    echo "<td> - </td>";
                                                }
                                                echo "</tr>";
                                            }
                                        }
                                    } else{ 
                                        
                                        echo "NO Faculty enrolled in the course <br>";
                                        
                                        echo "<td> No Faculty Enrolled to the Course </td>";
                                        echo "<td> - </td>";
                                        echo "<td> - </td>";
                                        echo "<td> - </td>";
                                    }
                                    echo "</tr>";
                                // }
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="program-rating">
                <p>Program Rating : <span><?php
                if($validAverageCount > 0) {
                    echo number_format($programAverage / $validAverageCount, 2);
                } else{
                    echo "0.0";
                }
                 ?></span></p>
            </div>
        </div>
    </div>
<script>
console.log("Script file loaded");

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM fully loaded");

    const downloadBtn = document.getElementById("download");
    console.log("Download element:", downloadBtn);

    if (!downloadBtn) {
        console.error("Download button not found");
        return;
    }

    downloadBtn.addEventListener("click", function () {
        console.log("Button is clicked");

        const table = document.getElementById("singleProgramrogramTable");
        const workbook = XLSX.utils.book_new();

        const tableData1 = XLSX.utils.sheet_to_json(
            XLSX.utils.table_to_sheet(table),
            { header: 1 }
        );

        const header1 = [programName];
        const header2 = [selectedAcademicYear];
        const combinedData1 = [header1, header2, [], ...tableData1];

        const worksheet = XLSX.utils.aoa_to_sheet(combinedData1);
        XLSX.utils.book_append_sheet(workbook, worksheet, "Visiting Faculty");

        XLSX.writeFile(
            workbook,
            `${programName}_A_Y_${selectedAcademicYear} Analysis.xlsx`
        );
    });
});
</script>



</body>
</html>

<?php
    function getFacultyData($conn, $prefix, $courseId) {
        $facultyData = [];
        $enrolledFaculty = $conn->query("SELECT ra.roleid, ra.userid as userid, cc.id as courseid, cc.fullname 
                                            from {$prefix}role_assignments ra 
                                            join {$prefix}context con on con.id = ra.contextid
                                            join {$prefix}course cc on cc.id = con.instanceid
                                            where con.contextlevel = 50 and ra.roleid = 3 and cc.id=$courseId");
                                        
        if($enrolledFaculty -> num_rows > 0) {
            while($faculty = mysqli_fetch_assoc($enrolledFaculty)) {

                $groupAvailableToCourse = $conn->query("SELECT id from {$prefix}groups where courseid=$courseId");
                if($groupAvailableToCourse -> num_rows > 0) {
                    // $groupQuery = $conn -> query("SELECT u.firstname, u.lastname, g.name as gname, g.id as id
                    //     FROM {$prefix}user as u
                    //     join {$prefix}groups_members as ra on ra.userid = u.id
                    //     join {$prefix}groups as g on ra.groupid = g.id
                    //     where u.id={$faculty['userid']} and g.courseid=$courseId
                    //     group by g.id");
                    
                    $groupQuery = $conn -> query("SELECT 
                                                u.firstname, u.lastname, g.name AS gname, g.id AS id, f.name
                                                FROM sitpu_user AS u
                                                JOIN sitpu_groups_members AS ra ON ra.userid = u.id
                                                JOIN sitpu_groups AS g ON ra.groupid = g.id
                                                JOIN sitpu_feedback f ON f.course = g.courseid
                                                WHERE u.id = {$faculty['userid']} 
                                                AND g.courseid = $courseId
                                                AND f.name NOT LIKE '%mid%' 
                                                AND (f.name like '%Student%' and f.name like '%Feedback%' and f.name like '%Faculty%') 
                                                AND f.name LIKE CONCAT('%', g.name, ' : ', u.firstname, ' ', u.lastname, '%')
                                                GROUP BY f.id, g.name");


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