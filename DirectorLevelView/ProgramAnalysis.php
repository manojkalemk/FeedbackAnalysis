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
    
    // function debugLog($title, $data = null) {
    //     echo "<tr style='background:#222;color:#7CFC00;font-size:12px;'>
    //             <td colspan='6'>
    //                 <b>[DEBUG]</b> $title";
    //     if ($data !== null) {
    //         echo "<pre style='color:#7CFC00;'>" . print_r($data, true) . "</pre>";
    //     }
    //     echo "</td></tr>";
    // }

    
    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    
    function getBatchAndSemester($conn, $prefix, $courseId) {
        
        // $sql="SELECT 
        //         cat2.name AS batch,
        //         cat3.name AS semester
        //     FROM {$prefix}course c
        //     JOIN {$prefix}course_categories cat3 ON cat3.id = c.category
        //     JOIN {$prefix}course_categories cat2 ON cat2.id = cat3.parent
        //     WHERE c.id = $courseId
        //     LIMIT 1";
        
        $sql = "
            SELECT 
    cat2.name AS batch,
    cat3.name AS semester,
    cat4.name AS specialization,
    cat1.name AS program
FROM {$prefix}course c
JOIN {$prefix}course_categories cat4 ON cat4.id = c.category
JOIN {$prefix}course_categories cat3 ON cat3.id = cat4.parent
JOIN {$prefix}course_categories cat2 ON cat2.id = cat3.parent
JOIN {$prefix}course_categories cat1 ON cat1.id = cat2.parent
WHERE c.id = $courseId
LIMIT 1;
            
        ";
    
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
    
        return ['batch' => '-', 'semester' => '-'];
    }

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
                            <th>Batch</th>
                            <th>Semester</th>
                            <th>Course Average</th>
                            <th>Teacher Name</th>
                            <th>Groups/Section Name</th>
                            <th>Groups/Section Average</th>
                            <th>Teacher Average</th>
                        </tr>
                    </thead>
                    
                    <tbody>
<?php
// debugLog("START TABLE RENDERING");
// debugLog("RAW courseInProgram", $coursesInProgram);

$validAverageCount = 0;
$programAverage = 0;

foreach ($coursesInProgram as $courseId => $courseArray) {

    // debugLog("Processing courseId = $courseId", $courseArray);

    // Get course name
    if (is_array($courseArray)) {
        $courseName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id=$courseId")
                           ->fetch_assoc()['fullname'] ?? "Unknown Course";
    } else {
        // This is plain text course name
        $courseName = $courseArray;
    }

    // If no structured feedback data exists
    if (!is_array($courseArray) || !isset($courseArray['faculty'])) {
        echo "<tr>
                <td>$courseName</td>
                <td colspan='5' style='text-align:center;color:red;'>No feedback data available</td>
              </tr>";
        // debugLog("No feedback structure found for course $courseId");
        continue;
    }
    
    

    // Structured data exists
    $enrolledFaculty = getFacultyData($conn, $prefix, $courseId);
    
    // Program rating calculation
    if (isset($courseArray['courseWeightage']) && is_numeric($courseArray['courseWeightage'])) {
        $programAverage += (float)$courseArray['courseWeightage'];
        $validAverageCount++;
    }
    
    // debugLog("Faculty fetched for course $courseId", $enrolledFaculty);

    $rowSpan = 0;
    if (is_array($enrolledFaculty)) {
        foreach ($enrolledFaculty as $facultyId => $groups) {
            if (is_array($groups)) {
                $rowSpan += count($groups);
            } else {
                $rowSpan++;
            }
        }
    } else {
        $rowSpan = 1;
    }

    echo "<tr>";

    // Course name
    // echo "<td rowspan='$rowSpan' class='course' data-course-id='$courseId'>$courseName</td>";
    
    // Course name, batch , semester 
    $batchSemester = getBatchAndSemester($conn, $prefix, $courseId);
    $batch = htmlspecialchars($batchSemester['batch']);
    $semester = htmlspecialchars($batchSemester['semester']);
    
    echo "<td rowspan='$rowSpan' class='course' data-course-id='$courseId'>$courseName</td>";
    echo "<td rowspan='$rowSpan'>$batch</td>";
    echo "<td rowspan='$rowSpan'>$semester</td>";
    

    // Course average
    if (isset($courseArray['courseWeightage'])) {
        $avg = number_format($courseArray['courseWeightage'], 2);
        $color = getColor($avg);
        echo "<td rowspan='$rowSpan'>
                <span style='background:$color;padding:4px;border-radius:4px;'>$avg</span>
              </td>";
    } else {
        echo "<td rowspan='$rowSpan'>N/A</td>";
    }

    $firstRow = true;

    if (!is_array($enrolledFaculty)) {
        echo "<td colspan='4'>No faculty enrolled</td></tr>";
        continue;
    }

    foreach ($enrolledFaculty as $facultyId => $groupArray) {

        $facultyName = $conn->query(
            "SELECT CONCAT(firstname,' ',lastname) AS name 
             FROM {$prefix}user WHERE id=$facultyId"
        )->fetch_assoc()['name'] ?? "Unknown Faculty";

        if (!$firstRow) echo "<tr>";

        echo "<td rowspan='" . (is_array($groupArray) ? count($groupArray) : 1) . "' 
                 class='faculty' data-faculty-id='$facultyId'>$facultyName</td>";

        if (is_array($groupArray)) {
            foreach ($groupArray as $gIndex => $groupName) {
                if ($gIndex > 0) echo "<tr>";

                echo "<td>$groupName</td>";

                if (isset($facultyCourseAverage[$facultyId][$courseId])) {
                    $avg = number_format($courseArray['faculty'][$facultyId] ?? 0, 2);
                    $color = getColor($avg);
                    echo "<td>
                            <span style='background:$color;padding:4px;border-radius:4px;'>$avg</span>
                          </td>
                          <td>
                            <span style='background:$color;padding:4px;border-radius:4px;'>$avg</span>
                          </td>";
                } else {
                    echo "<td>-</td><td>-</td>";
                }

                echo "</tr>";
            }
        } else {
            echo "<td>$groupArray</td><td>-</td><td>-</td></tr>";
        }

        $firstRow = false;
    }
}

// debugLog("END TABLE RENDERING");
?>
</tbody>


                    
                </table>
            </div>

            <div class="program-rating">
                <p>Program Rating : <span><?php
                if($validAverageCount > 0) {
                    echo number_format($programAverage / $validAverageCount, 2);
                } else {
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

    // echo "<div style='background:#111;color:#00ff00;padding:10px;margin:10px 0;'>
    //         <b>[DEBUG] getFacultyData() START for Course ID:</b> $courseId
    //       </div>";

    $facultyData = [];

    $sqlFaculty = "
        SELECT ra.roleid, ra.userid as userid, cc.id as courseid, cc.fullname 
        FROM {$prefix}role_assignments ra 
        JOIN {$prefix}context con ON con.id = ra.contextid
        JOIN {$prefix}course cc ON cc.id = con.instanceid
        WHERE con.contextlevel = 50 
        AND ra.roleid = 3 
        AND cc.id = $courseId
    ";

    // echo "<pre style='color:cyan;'>[DEBUG] Faculty SQL:\n$sqlFaculty</pre>";

    $enrolledFaculty = $conn->query($sqlFaculty);

    // if (!$enrolledFaculty) {
    //     echo "<pre style='color:red;'>[ERROR] Faculty query failed: {$conn->error}</pre>";
    //     return "Faculty Query Failed";
    // }

    // echo "<pre style='color:yellow;'>[DEBUG] Faculty Rows Found: {$enrolledFaculty->num_rows}</pre>";

    // if ($enrolledFaculty->num_rows == 0) {
    //     echo "<pre style='color:orange;'>[DEBUG] No faculty enrolled for course $courseId</pre>";
    //     return "No Faculty Enrolled";
    // }

    while ($faculty = mysqli_fetch_assoc($enrolledFaculty)) {

        $facultyId = $faculty['userid'];
        // echo "<pre style='color:#7fffd4;'>[DEBUG] Processing Faculty ID: $facultyId</pre>";

        $sqlGroupAvailable = "SELECT id FROM {$prefix}groups WHERE courseid=$courseId";
        // echo "<pre style='color:cyan;'>[DEBUG] Group Available SQL:\n$sqlGroupAvailable</pre>";

        $groupAvailableToCourse = $conn->query($sqlGroupAvailable);

        if (!$groupAvailableToCourse) {
            // echo "<pre style='color:red;'>[ERROR] Group availability query failed: {$conn->error}</pre>";
            $facultyData[$facultyId] = "Group Query Failed";
            continue;
        }

        if ($groupAvailableToCourse->num_rows == 0) {
            // echo "<pre style='color:orange;'>[DEBUG] No groups exist for course $courseId</pre>";
            $facultyData[$facultyId] = "No Group";
            continue;
        }

        $sqlGroupQuery = "
            SELECT 
                u.firstname, 
                u.lastname, 
                g.name AS gname, 
                g.id AS id, 
                f.name
            FROM {$prefix}user AS u
            JOIN {$prefix}groups_members AS ra ON ra.userid = u.id
            JOIN {$prefix}groups AS g ON ra.groupid = g.id
            JOIN {$prefix}feedback f ON f.course = g.courseid
            WHERE u.id = $facultyId
            AND g.courseid = $courseId
            AND f.name NOT LIKE '%mid%' 
            AND (f.name LIKE '%Student%' AND f.name LIKE '%Feedback%' AND f.name LIKE '%Faculty%') 
            AND f.name LIKE CONCAT('%', g.name, ' : ', u.firstname, ' ', u.lastname, '%')
            GROUP BY f.id, g.name
        ";

        // echo "<pre style='color:cyan;'>[DEBUG] Group Query SQL:\n$sqlGroupQuery</pre>";

        $groupQuery = $conn->query($sqlGroupQuery);

        if (!$groupQuery) {
            // echo "<pre style='color:red;'>[ERROR] Group query failed: {$conn->error}</pre>";
            $facultyData[$facultyId] = "Group Query Failed";
            continue;
        }

        // echo "<pre style='color:yellow;'>[DEBUG] Groups Found for Faculty $facultyId: {$groupQuery->num_rows}</pre>";

        if ($groupQuery->num_rows > 0) {
            while ($group = mysqli_fetch_assoc($groupQuery)) {
                // echo "<pre style='color:#7fff00;'>[DEBUG] Group Assigned: {$group['gname']}</pre>";
                $facultyData[$facultyId][] = $group['gname'];
            }
        } else {
            // echo "<pre style='color:orange;'>[DEBUG] Faculty $facultyId has NO group assigned</pre>";
            $facultyData[$facultyId] = "No Group Assigned";
        }
    }

    // echo "<pre style='color:#00ff00;'>[DEBUG] getFacultyData() RESULT:</pre>";
    // echo "<pre style='color:white;background:#222;padding:10px;'>" . print_r($facultyData, true) . "</pre>";

    // echo "<div style='background:#111;color:#00ff00;padding:10px;margin:10px 0;'>
    //         <b>[DEBUG] getFacultyData() END for Course ID:</b> $courseId
    //       </div>";

    return $facultyData;
}

?>