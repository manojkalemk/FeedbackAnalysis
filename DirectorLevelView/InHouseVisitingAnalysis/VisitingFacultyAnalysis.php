<?php
    session_start();
    $visitingFacultyData = [];
    require_once "../../db.php";
    if(isset($_SESSION['visitingFacultyData'])) {
        $visitingFacultyData = json_decode($_SESSION['visitingFacultyData'], true);
        $facultyWithAverage = json_decode($_SESSION['facultyWithAverage'], true);
        $categoryFacultyAverage = json_decode($_SESSION['categoryFacultyAverage'], true);
        // $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        
        $selectedAcademicYear = $_SESSION['selectedAcademicYear'];
        
        //correctly calculating the average for visiting faculty and removing the zero average faculties
        $validFacultyCount = 0;
        $validFacultySum   = 0.0;
        
        foreach ($facultyWithAverage as $avg) {
            if ((float)$avg > 0) {
                $validFacultySum += (float)$avg;
                $validFacultyCount++;
            }
        }

        $visiting = $validFacultyCount > 0 ? number_format($validFacultySum / $validFacultyCount, 2) : '0.00';
        //ends here 
        
        // $visiting = number_format($categoryFacultyAverage['visiting']/count($visitingFacultyData), 2); // this will give average from all teacher with the zero avearege teachers also.
    } else {
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
    <title>Visiting Faculty Analysis</title>
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

    <div class="visitingFacultyAnalysis">
        <h1>Visiting Teacher</h1>
        <h3><?php echo "A.Y. " . $selectedAcademicYear; ?></h3>
	<table border='1' id='visitingfaculty'>
            <thead>
                <tr>
                    <th>Average <span class="info-icon" onclick="showAverageInfo()"> (i) </span> </th>
                    <th>No. of Teacher</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php $color = getColor($visiting); echo "<span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $visiting . "</span>"; ?> </td>
                    <td><?php echo count($visitingFacultyData); ?></td>
                </tr>
            </tbody>
        </table>
        <table border='1' id='visitingfaculty'>
            <thead>
                <tr>
                    <th>Employee Code</th>
                    <th>Teacher name</th>
                    <th>Teacher Average</th>
                    <th>Groups/Section</th>
                    <th>Average</th>
                    <th>Course Average</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($visitingFacultyData as $facultyId => $faculty) {
                        /* ---------- PRECOMPUTE ROWS ---------- */
                        $rows = [];
                    
                        foreach ($faculty['courses'] as $courseId => $groups) {
                    
                            $courseName = $conn->query(
                                "SELECT fullname FROM {$prefix}course WHERE id = $courseId"
                            )->fetch_assoc()['fullname'];
                    
                            if (is_array($groups)) {
                                foreach ($groups as $groupName => $avg) {
                                    $rows[] = [
                                        'courseId'   => $courseId,
                                        'courseName' => $courseName,
                                        'group'      => $groupName,
                                        'avg'        => $avg
                                    ];
                                }
                            } else {
                                $rows[] = [
                                    'courseId'   => $courseId,
                                    'courseName' => $courseName,
                                    'group'      => 'No Group',
                                    'avg'        => ($groups === 'NA' || $groups === 'NGA') ? 0 : $groups
                                ];
                            }
                        }
                        
                        if (empty($rows)) {
                            $rows[] = [
                                'courseId'   => null,
                                'courseName' => 'No Course Assigned / Feedback Not Available / No Feedback Submitted',
                                'group'      => '-',
                                'avg'        => 0
                            ];
                        }
                    
                        $rowspan = count($rows);
                        $facultyAvg = number_format($facultyWithAverage[$facultyId] ?? 0, 2);
                        $facultyColor = getColor($facultyAvg);
                    
                        /* ---------- RENDER ---------- */
                        foreach ($rows as $index => $row) {
                            echo "<tr>";
                    
                            if ($index === 0) {
                                echo "<td rowspan='$rowspan'>{$faculty['employeeCode']}</td>";
                                echo "<td rowspan='$rowspan' onclick='facultyAnalysis($facultyId)' class='faculty'>
                                        {$faculty['fullName']}
                                      </td>";
                                echo "<td rowspan='$rowspan'>
                                        <span style='background:$facultyColor;padding:4px;border-radius:5px'>
                                            $facultyAvg
                                        </span>
                                      </td>";
                            }
                    
                            $avg = number_format((float)$row['avg'], 2);
                            $color = getColor($avg);
                            
                    
                            if($row['courseId'] === null) {
                                echo "<td> {$row['courseName']} </td>";
                                echo "<td>{$row['group']}</td>";
                                echo "<td> <span style='background:$color;padding:4px;border-radius:5px'> $avg </span> </td>";
                            } else {
                                echo "<td onclick='courseAnalysis({$row['courseId']})' class='course'> {$row['courseName']} </td>";
                                echo "<td>{$row['group']}</td>";
                                echo "<td> <span style='background:$color;padding:4px;border-radius:5px'> $avg </span> </td>";
                            }
                    
                            echo "</tr>";
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

            const tables = document.querySelectorAll("#visitingfaculty");
            const workbook = XLSX.utils.book_new();

            const worksheetData = [];

            worksheetData.push(["Visiting Faculty"])
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

            XLSX.writeFile(workbook, `visiting_faculty_${selectedAcademicYear}.xlsx`);
        });
        
        function courseAnalysis(courseId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Course.php?courseId=${courseId}`, "_blank");
        }
        function facultyAnalysis(facultyId) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/course-faculty/Faculty.php?facultyId=${facultyId}`, "_blank");
        }
        
        function showAverageInfo() {
            alert(
                "Average is calculated only for teachers with assigned courses and valid feedback.\n\n" +
                "Teachers with no course assigned, no feedback submitted, or average = 0 are excluded."
            );
        }
        
    </script>

    
</body>
</html>