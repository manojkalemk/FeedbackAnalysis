<?php
    session_start();
    require_once "../../db.php";
    $inHouseFacultyData = [];
    if(isset($_SESSION['inHouseFacultyData'])) {
        $inHouseFacultyData = json_decode($_SESSION['inHouseFacultyData'], true);
        $facultyWithAverage = json_decode($_SESSION['facultyWithAverage'], true);
        $categoryFacultyAverage = json_decode($_SESSION['categoryFacultyAverage'], true);
        // $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        $selectedAcademicYear = $_SESSION['selectedAcademicYear'];
        
        // correctly doing the average
        $validFacultySum = 0.0;
        $validFacultyCount = 0;
        
        foreach ($facultyWithAverage as $avg) {
            if ((float)$avg > 0) {
                $validFacultySum += (float)$avg;
                $validFacultyCount++;
            }
        }

        $fulltimeAverage = $validFacultyCount > 0 ? number_format($validFacultySum / $validFacultyCount, 2) : '0.00';

        //
        
        // $fulltimeAverage = number_format($categoryFacultyAverage['fulltime']/count($inHouseFacultyData), 2);

    } else{
        echo "Something Went Wrong";
    }

    $serverName = $_SERVER['SERVER_NAME'];
    $serverPort = $_SERVER['SERVER_PORT'];

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    if (($protocol === 'http' && $serverPort == 80) || ($protocol === 'https' && $serverPort == 443)) {
        $fullServerName = $serverName . "/attendance-report";
    } else {
        $fullServerName = $serverName . ':' . $serverPort . "/attendance-report";
    }

    // echo "<pre>";
    // print_r($facultyWithAverage);
    // echo "</pre>";

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
    
    
    // echo "<pre>";
    // echo "POST : ";
    // var_dump($_POST);
    // echo "<br>GET : ";
    // var_dump($_GET);
    
    // echo "<br>SESSION : ";
    // var_dump($_SESSION);
    // echo "</pre>";
    

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulltime Teacher Analysis</title>
    <link rel="stylesheet" href="Faculty.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" href="../../assets/logo.png" type="image/x-icon">
    <style>
        .info-icon {
            cursor: pointer;
            margin-left: 6px;
            font-size: 14px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>SYMBIOSIS</h1>
        <h2>Teacher Feedback Analysis</h2>
        <p id='download'>Download</p>
    </div>
    
    <div class="inhouseFacultyAnalysis">
        <h1>Full time Teacher</h1>
        <h3><?php echo "A.Y. " .$selectedAcademicYear; ?></h3>
	<table border='1' id='fulltimefaculty'>
            <thead>
                <tr>
                    <th>Average <span class="info-icon" onclick="showAverageInfo()"> (i) </span> </th>
                    <th>No. of Teacher</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php $color = getColor($fulltimeAverage); echo "<span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $fulltimeAverage . "</span>"; ?> </td>
                    <td><?php echo count($inHouseFacultyData); ?></td>
                </tr>
            </tbody>
        </table>
        <table border='1' id='fulltimefaculty'>
            <thead>
                <tr>
                    <th>Employee Code</th>
                    <th>Teacher name</th>
                    <th>Teacher Average</th>
                    <th>Stream</th>
                    <th>Semester </th>
                    <th>Course</th>
                    <th>Groups/Section</th>
                    <th>Average</th>
                </tr>
            </thead>
            <tbody>
    
                <?php
                    foreach ($inHouseFacultyData as $facultyId => $facultyDetails) {

                        /* ---------- NORMALIZE COURSES ---------- */
                        if (empty($facultyDetails['courses'])) {
                            $facultyDetails['courses'] = [
                                'NO_FEEDBACK' => 'NO_FEEDBACK'
                            ];
                        }
                    
                        /* ---------- CALCULATE ROWSPAN ---------- */
                        $rowspan = 0;
                        foreach ($facultyDetails['courses'] as $details) {
                            $rowspan += is_array($details) ? max(count($details), 1) : 1;
                        }
                    
                        $printedFacultyRow = false;
                    
                        /* ---------- RENDER ---------- */
                        foreach ($facultyDetails['courses'] as $courseId => $groupsArray) {
                    
                            /* ===== NO FEEDBACK CASE ===== */
                            if ($courseId === 'NO_FEEDBACK') {
                                echo "<tr>";
                    
                                echo "<td rowspan='$rowspan'>{$facultyDetails['employeeCode']}</td>";
                                echo "<td rowspan='$rowspan' class='faculty' onclick='facultyAnalysis($facultyId)'> {$facultyDetails['fullName']} </td>";
                                
                                $facultyAvg = number_format($facultyWithAverage[$facultyId] ?? 0, 2);
                                $color = getColor($facultyAvg);
                                
                                echo "<td rowspan='$rowspan'> <span style='background:$color;padding:4px;border-radius:5px'> $facultyAvg </span> </td>";
                                echo "<td>No Stream</td>";
                                echo "<td>No Semester</td>";
                                echo "<td>No Course Assigned / Feedback Not Available</td>";
                                echo "<td> - </td>";
                                echo "<td> <span style='background:#ff7676;padding:4px;border-radius:5px'> 0.00 </span> </td>";
                                echo "</tr>";
                                break;
                            }
                    
                            /* ===== VALID COURSE ===== */
                            $courseName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id = $courseId")->fetch_assoc()['fullname'] ?? 'Unknown Course';
                    
                            /* Semester / Stream */
                            $semesterRow = $conn->query("
                                WITH RECURSIVE category_path AS (
                                    SELECT c.id course_id, cat.id category_id, cat.name, cat.parent, 1 level
                                    FROM {$prefix}course c
                                    JOIN {$prefix}course_categories cat ON c.category = cat.id
                                    WHERE c.id = $courseId
                                    UNION ALL
                                    SELECT cp.course_id, cat.id, cat.name, cat.parent, cp.level + 1
                                    FROM category_path cp
                                    JOIN {$prefix}course_categories cat ON cat.id = cp.parent
                                )
                                SELECT
                                    MAX(CASE WHEN level = max_level THEN name END) AS PgmName,
                                    MAX(CASE WHEN level = max_level - 2 THEN name END) AS Semester
                                FROM (
                                    SELECT *, (SELECT MAX(level) FROM category_path) max_level
                                    FROM category_path
                                ) t
                            ")->fetch_assoc();
                    
                            $stream   = $semesterRow['PgmName'] ?? 'No Stream';
                            $semester = $semesterRow['Semester'] ?? 'No Semester';
                    
                            /* ===== GROUPED ===== */
                            if (is_array($groupsArray)) {
                    
                                $firstGroup = true;
                                foreach ($groupsArray as $groupName => $groupAvg) {
                    
                                    echo "<tr>";
                    
                                    if (!$printedFacultyRow) {
                                        echo "<td rowspan='$rowspan'>{$facultyDetails['employeeCode']}</td>";
                                        echo "<td rowspan='$rowspan' class='faculty' onclick='facultyAnalysis($facultyId)'> {$facultyDetails['fullName']} </td>";
                    
                                        $fAvg = number_format($facultyWithAverage[$facultyId] ?? 0, 2);
                                        $fColor = getColor($fAvg);
                                        echo "<td rowspan='$rowspan'> <span style='background:$fColor;padding:4px;border-radius:5px'> $fAvg </span> </td>";
                    
                                        $printedFacultyRow = true;
                                    }
                    
                                    if ($firstGroup) {
                                        $gc = count($groupsArray);
                                        echo "<td rowspan='$gc'>$stream</td>";
                                        echo "<td rowspan='$gc'>$semester</td>";
                                        echo "<td rowspan='$gc' class='course' onclick='courseAnalysis($courseId)'>$courseName</td>";
                                        $firstGroup = false;
                                    }
                    
                                    $avg = number_format((float)$groupAvg, 2);
                                    $color = getColor($avg);
                                    echo "<td>$groupName</td>";
                                    echo "<td> <span style='background:$color;padding:4px;border-radius:5px'> $avg </span> </td>";
                                    echo "</tr>";
                                }
                    
                            }
                            /* ===== NON-GROUPED ===== */
                            else {
                    
                                echo "<tr>";
                    
                                if (!$printedFacultyRow) {
                                    echo "<td rowspan='$rowspan'>{$facultyDetails['employeeCode']}</td>";
                                    echo "<td rowspan='$rowspan' class='faculty' onclick='facultyAnalysis($facultyId)'> {$facultyDetails['fullName']} </td>";
                    
                                    $fAvg = number_format($facultyWithAverage[$facultyId] ?? 0, 2);
                                    $fColor = getColor($fAvg);
                                    echo "<td rowspan='$rowspan'> <span style='background:$fColor;padding:4px;border-radius:5px'> $fAvg </span> </td>";
                    
                                    $printedFacultyRow = true;
                                }
                    
                                echo "<td>$stream</td>";
                                echo "<td>$semester</td>";
                                echo "<td class='course' onclick='courseAnalysis($courseId)'>$courseName</td>";
                    
                                if ($groupsArray === 'NA' || $groupsArray === 'NGA') {
                                    echo "<td>No Group</td>";
                                    echo "<td> <span style='background:#ff7676;padding:4px;border-radius:5px'> 0.00 </span> </td>";
                                } else {
                                    $avg = number_format((float)$groupsArray, 2);
                                    $color = getColor($avg);
                                    echo "<td>No Group</td>";
                                    echo "<td> <span style='background:$color;padding:4px;border-radius:5px'> $avg </span> </td>";
                                }
                    
                                echo "</tr>";
                            }
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
    <script>
        let serverName = '<?php echo $fullServerName; ?>';
        let protocol = '<?php echo $protocol; ?>';
        let selectedAcademicYear = '<?php echo $selectedAcademicYear; ?>';
	    document.getElementById("download").addEventListener("click", function () {
            const tables = document.querySelectorAll("#fulltimefaculty");
            const workbook = XLSX.utils.book_new();

            const worksheetData = [];
            worksheetData.push(["Fulltime Faculty"])
            worksheetData.push([selectedAcademicYear])
            worksheetData.push([])

            tables.forEach((table, index) => {
                const tableData = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table), { header: 1 });

                if (index > 0) {
                    worksheetData.push([]);
                }
                worksheetData.push([`Table ${index + 1}`]);
                worksheetData.push(...tableData);
            });
            const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

            XLSX.utils.book_append_sheet(workbook, worksheet, "Combined Tables");

            XLSX.writeFile(workbook, `fulltime_faculty_${selectedAcademicYear}.xlsx`);

        });
        function courseAnalysis(courseId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Course.php?courseId=${courseId}`, "_blank");
        }
        function facultyAnalysis(facultyId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Faculty.php?facultyId=${facultyId}`, "_blank");
        }
        
        function showAverageInfo() {
            alert(
                "Average is calculated only for teachers who have assigned courses and valid feedback.\n\n" +
                "Teachers with no course assigned, no feedback submitted, or whose calculated average is 0.00 are shown in the report but excluded from the average calculation."
            );
        }
        
    </script>
</body>
</html>