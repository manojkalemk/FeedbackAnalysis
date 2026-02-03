<?php
    session_start();
    require_once "../../db.php";
    $inHouseFacultyData = [];
    if(isset($_SESSION['inHouseFacultyData'])) {
        $inHouseFacultyData = json_decode($_SESSION['inHouseFacultyData'], true);
        $facultyWithAverage = json_decode($_SESSION['facultyWithAverage'], true);
        $categoryFacultyAverage = json_decode($_SESSION['categoryFacultyAverage'], true);
        $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        $fulltimeAverage = number_format($categoryFacultyAverage['fulltime']/count($inHouseFacultyData), 2);

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
                    <th>Average</th>
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
                    // foreach($inHouseFacultyData as $facultyId => $facultyDetails) {
                    //     $rowspan = 0;
                    //     foreach ($facultyDetails['courses'] as $course => $details) {
                    //         if (is_array($details)) {
                    //             // Courses which have an group
                    //             $rowspan += count($details);
                    //         } else {
                    //             // Courses which do not have an group
                    //             $rowspan += 1; 
                    //         }
                    //     }

                    //     // echo $facultyDetails['fullName'] . " - " . $rowspan . "<br>";

                    // // echo "<pre>";
                    // // print_r($facultyDetails['courses'][1563]);
                    // // echo "</pre>";

                    // echo "<tr id='mainRow'>";
                    //     if(!$facultyDetails['courses'][1563]){
                    //         echo "<td rowspan='" . ($rowspan + 1) ."'>" . $facultyDetails['employeeCode'] . "</td>";
                    //         echo "<td rowspan='" . ($rowspan + 1) ."' onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyDetails['fullName'] . "</td>";
                    //     }else{
                    //         // echo $rowspan . "<br>";
                    //         echo "<td rowspan='" . $rowspan ."'>" . $facultyDetails['employeeCode'] . "</td>";
                    //         echo "<td rowspan='" . $rowspan ."' onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyDetails['fullName'] . "</td>";
                    //     }
                    //     if(isset($facultyWithAverage[$facultyId])) {
                    //         $average = number_format($facultyWithAverage[$facultyId], 2);
                    //         $color = getColor($average);
                    //         echo "<td rowspan='" . ($rowspan + 1) ."'>
                    //             <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                    //         </td>";
                    //     } else{
                    //         echo "<td rowspan='" . $rowspan ."'><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    //     }
    
                    //     $onGoingRow = true;
                    //     foreach($facultyDetails['courses'] as $courseId => $groupsArray) {

                    //         // $semester = $conn->query("SELECT cat3.name AS Semester
                    //                                     // FROM {$prefix}course_categories cat1
                    //                                     // LEFT JOIN  {$prefix}course_categories cat2 ON cat1.id = cat2.parent
                    //                                     // LEFT JOIN  {$prefix}course_categories cat3 ON cat2.id = cat3.parent
                    //                                     // LEFT JOIN  {$prefix}course_categories cat4 ON cat3.id = cat4.parent
                    //                                     // LEFT JOIN  {$prefix}course_categories cat5 ON cat4.id = cat5.parent
                    //                                     // LEFT JOIN  {$prefix}course c ON c.category IN (cat1.id, cat2.id, cat3.id, cat4.id, cat5.id)
                    //                                     // WHERE cat1.parent = 0 and c.id is not null and c.id=$courseid ORDER BY c.fullname;") -> fetch_assoc()['semester'];

                    //         // echo $semester . " = " . $courseId . "<br>";


                    //        $semester = $conn->query(" WITH RECURSIVE category_path AS (
                    //                                 -- Step 1: Start from the course's category
                    //                                 SELECT 
                    //                                     c.id AS course_id,
                    //                                     cat.id AS category_id,
                    //                                     cat.name AS category_name,
                    //                                     c.fullname AS subject_name,
                    //                                     cat.parent,
                    //                                     1 AS level
                    //                                 FROM {$prefix}course c
                    //                                 JOIN {$prefix}course_categories cat ON c.category = cat.id
                    //                                 WHERE c.id = $courseId  -- Replace dynamically

                    //                                 UNION ALL

                    //                                 -- Step 2: Climb up the hierarchy
                    //                                 SELECT 
                    //                                     cp.course_id,
                    //                                     cat.id AS category_id,
                    //                                     cat.name AS category_name,
                    //                                     cp.subject_name,
                    //                                     cat.parent,
                    //                                     cp.level + 1 AS level
                    //                                 FROM category_path cp
                    //                                 JOIN {$prefix}course_categories cat ON cat.id = cp.parent
                    //                             )

                    //                             -- Final: Select only Semester name
                    //                             SELECT 
                    //                                 MAX(CASE WHEN level = max_level - 2 THEN category_name END) AS Semester
                    //                             FROM (
                    //                                 SELECT cp.*, 
                    //                                     (SELECT MAX(level) FROM category_path WHERE course_id = cp.course_id) AS max_level
                    //                                 FROM category_path cp
                    //                             ) AS leveled
                    //                             GROUP BY course_id;");
                    //             $semester = $semester->fetch_assoc()['Semester'];
                    //             $onGoingRow = false;
                    //             $onGoingGroupRow = false;


                    //         if($semester == '') {
                    //             $semester = 'No Semester';
                    //         }

                    //         $courseName = $conn->query("SELECT fullname from {$prefix}course where id = $courseId") -> fetch_assoc()['fullname'];
                    //         if(!$onGoingRow) {
                    //             echo "<tr>";
                    //         }


                    //         if(is_array($groupsArray)) {

                    //             echo $facultyDetails['fullName'] . " - " . $courseName . " - ". count($groupsArray). "<br>";

                    //             echo "<td rowspan = '" . count($groupsArray) . "'>" . $semester ."</td>";
                    //             echo "<td rowspan = '" . count($groupsArray) . "' onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                    //             $onGoingGroupRow = true;
                    //             foreach($groupsArray as $groupName => $groupAverage) {
                    //                 if(!$onGoingGroupRow) {
                    //                     echo "<tr>";
                    //                 }
                    //                 echo "<td>" . $groupName . "</td>";
                    //                 $average = number_format((float)$groupAverage, 2);
                    //                 $color = getColor($average);
                    //                 echo "<td>
                    //                     <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                    //                 </td>";
                    //                 echo "</tr>";
                    //                 $onGoingGroupRow = false;
                    //             }
                    //         } else{
                    //             // groups array as Course Average which do not have group

                    //             echo "<td>" . $semester ."</td>";

                    //             echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";

                    //             $check = false;

                    //             if($groupsArray == 'NGA') {
                    //                 echo "<td> No Group Assigned </td>"; 
                    //                 echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    //                 $check = true;
                    //             } else if($groupsArray == 'NA'){
                    //                 echo "<td>No Group</td>";
                    //                 echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    //                 $check = true;
                    //             }
                    //             if(!$check) {
                    //                 echo "<td>No Group</td>";
                    //                 $average = number_format($groupsArray, 2);
                    //                 $color = getColor($average);

                    //                 echo "<td>
                    //                     <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                    //                 </td>";

                    //             }


                    //         }
                    //         echo "</tr>";
                    //         $onGoingRow = false;

                    //         // if (is_array($groupsArray)) {
                    //         //     // echo $facultyDetails['fullName'] . " - " . $courseName . " - " . count($groupsArray) . "<br>";
                    //         //     if(isset($courseId)){
                    //         //     $rowspan = count($groupsArray);
                    //         //     $firstRow = true;

                    //         //     foreach ($groupsArray as $groupName => $groupAverage) {
                    //         //         echo "<tr>";
                    //         //         if ($firstRow) {
                    //         //             echo "<td rowspan='" . $rowspan . "'>" . $semester . "</td>";
                    //         //             echo "<td rowspan='" . $rowspan . "' onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                    //         //             $firstRow = false;
                    //         //         }

                    //         //         echo "<td>" . $groupName . "</td>";
                    //         //         $average = number_format((float)$groupAverage, 2);
                    //         //         $color = getColor($average);
                    //         //         echo "<td>
                    //         //                 <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$average</span>
                    //         //             </td>";
                    //         //         echo "</tr>";
                    //         //     }
                    //         // }
                    //         // } else {
                    //         //     echo "<tr>";
                    //         //     echo "<td>" . $semester . "</td>";
                    //         //     echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";

                    //         //     if ($groupsArray == 'NGA') {
                    //         //         echo "<td>No Group Assigned</td>";
                    //         //         echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    //         //     } elseif ($groupsArray == 'NA') {
                    //         //         echo "<td>No Group</td>";
                    //         //         echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    //         //     } else {
                    //         //         echo "<td>No Group</td>";
                    //         //         $average = number_format((float)$groupsArray, 2);
                    //         //         $color = getColor($average);
                    //         //         echo "<td>
                    //         //                 <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$average</span>
                    //         //             </td>";
                    //         //     }

                    //         //     echo "</tr>";
                    //         // }


                    //     }   
                    //     echo "</tr>";
                    // }

                    foreach($inHouseFacultyData as $facultyId => $facultyDetails) {
    $rowspan = 0;
    foreach ($facultyDetails['courses'] as $course => $details) {
        $rowspan += is_array($details) ? count($details) : 1;
    }

    // Faculty-level cells (name, code, average) — printed only once with rowspan
    $printedFacultyRow = false;

                    foreach($facultyDetails['courses'] as $courseId => $groupsArray) {
                            $semesterQuery = $conn->query("WITH RECURSIVE category_path AS (
                                                -- Step 1: Start from the course's category
                                                SELECT 
                                                    c.id AS course_id,
                                                    cat.id AS category_id,
                                                    cat.name AS category_name,
                                                    cat.parent,
                                                    1 AS level
                                                FROM {$prefix}course c
                                                JOIN {$prefix}course_categories cat ON c.category = cat.id
                                                WHERE c.id = $courseId  -- Replace with your course ID

                                                UNION ALL

                                                -- Step 2: Climb up the hierarchy
                                                SELECT 
                                                    cp.course_id,
                                                    cat.id AS category_id,
                                                    cat.name AS category_name,
                                                    cat.parent,
                                                    cp.level + 1 AS level
                                                FROM category_path cp
                                                JOIN {$prefix}course_categories cat ON cat.id = cp.parent
                                            )

                                            -- Final: Select only Semester and PgmName
                                            SELECT 
                                                MAX(CASE WHEN level = max_level THEN category_name END) AS PgmName,
                                                MAX(CASE WHEN level = max_level - 2 THEN category_name END) AS Semester
                                            FROM (
                                                SELECT cp.*, 
                                                    (SELECT MAX(level) FROM category_path WHERE course_id = cp.course_id) AS max_level
                                                FROM category_path cp
                                            ) AS leveled
                                            GROUP BY course_id;"); 
                                                
                            $row = $semesterQuery->fetch_assoc();

                            $stream = $row['PgmName'] ?? 'No Stream';
                            $semester = $row['Semester'] ?? 'No Semester';

        $courseName = $conn->query("SELECT fullname from {$prefix}course where id = $courseId")
                           ->fetch_assoc()['fullname'];

        // GROUP CASE
        if (is_array($groupsArray)) {
            $groupCount = count($groupsArray);
            $firstGroup = true;

            foreach ($groupsArray as $groupName => $groupAverage) {
                echo "<tr>";

                if (!$printedFacultyRow) {
                    echo "<td rowspan='$rowspan'>{$facultyDetails['employeeCode']}</td>";
                    echo "<td rowspan='$rowspan' onClick='facultyAnalysis($facultyId)' class='faculty'>{$facultyDetails['fullName']}</td>";

                    if (isset($facultyWithAverage[$facultyId])) {
                        $avg = number_format($facultyWithAverage[$facultyId], 2);
                        $color = getColor($avg);
                        echo "<td rowspan='$rowspan'><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$avg</span></td>";
                    } else {
                        echo "<td rowspan='$rowspan'><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                    }

                    $printedFacultyRow = true;
                }

                if ($firstGroup) {
                    echo "<td rowspan='$groupCount' style = 'width:93px;'>$stream</td>";
                    echo "<td rowspan='$groupCount' style = 'width:93px;'>$semester</td>";
                    echo "<td rowspan='$groupCount' onClick='courseAnalysis($courseId)' class='course'>$courseName</td>";
                    $firstGroup = false;
                }

                $avg = number_format((float)$groupAverage, 2);
                $color = getColor($avg);
                echo "<td>$groupName</td>";
                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$avg</span></td>";

                echo "</tr>";
            }

        // NON-GROUP CASE
        } else {
            echo "<tr>";

            if (!$printedFacultyRow) {
                echo "<td rowspan='$rowspan'>{$facultyDetails['employeeCode']}</td>";
                echo "<td rowspan='$rowspan' onClick='facultyAnalysis($facultyId)' class='faculty'>{$facultyDetails['fullName']}</td>";

                if (isset($facultyWithAverage[$facultyId])) {
                    $avg = number_format($facultyWithAverage[$facultyId], 2);
                    $color = getColor($avg);
                    echo "<td rowspan='$rowspan'><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$avg</span></td>";
                } else {
                    echo "<td rowspan='$rowspan'><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                }

                $printedFacultyRow = true;
            }

            echo "<td>$stream</td>";
            echo "<td>$semester</td>";
            echo "<td onClick='courseAnalysis($courseId)' class='course'>$courseName</td>";

            if ($groupsArray === 'NGA' || $groupsArray === 'NA') {
                echo "<td>No Group Assigned</td>";
                echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
            } else {
                echo "<td>No Group</td>";
                $avg = number_format((float)$groupsArray, 2);
                $color = getColor($avg);
                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>$avg</span></td>";
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

            XLSX.writeFile(workbook, "Fulltime Faculty.xlsx");

        });
        function courseAnalysis(courseId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Course.php?courseId=${courseId}`, "_blank");
        }
        function facultyAnalysis(facultyId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Faculty.php?facultyId=${facultyId}`, "_blank");
        }
    </script>
</body>
</html>