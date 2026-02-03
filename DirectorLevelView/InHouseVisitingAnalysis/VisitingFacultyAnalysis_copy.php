<?php
    session_start();
    $visitingFacultyData = [];
    require_once "../../db.php";
    if(isset($_SESSION['visitingFacultyData'])) {
        $visitingFacultyData = json_decode($_SESSION['visitingFacultyData'], true);
        $facultyWithAverage = json_decode($_SESSION['facultyWithAverage'], true);
        $categoryFacultyAverage = json_decode($_SESSION['categoryFacultyAverage'], true);
        $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        $visiting = number_format($categoryFacultyAverage['visiting']/count($visitingFacultyData), 2);
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
    <title>Visiting Faculty Analysis</title>
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

    <div class="visitingFacultyAnalysis">
        <h1>Visiting Teacher</h1>
        <h3><?php echo "A.Y. " . $selectedAcademicYear; ?></h3>
	<table border='1' id='visitingfaculty'>
            <thead>
                <tr>
                    <th>Average</th>
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
                    foreach($visitingFacultyData as $facultyId => $facultyDetails) {
                        $rowspan = 0;
                        foreach ($facultyDetails['courses'] as $course => $details) {
                            if (is_array($details)) {
                                // Courses which have an group
                                $rowspan += count($details);
                            } else {
                                // Courses which do not have an group
                                $rowspan += 1;
                            }
                        }
    
                        echo "<tr id='mainRow'>";
                        echo "<td rowspan='" . $rowspan ."'>" . $facultyDetails['employeeCode'] . "</td>";
                        echo "<td rowspan='" . $rowspan ."' onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyDetails['fullName'] . "</td>";
                        if(isset($facultyWithAverage[$facultyId])) {
                            $average = number_format($facultyWithAverage[$facultyId], 2);
                            $color = getColor($average);
                            echo "<td rowspan='" . $rowspan ."'>
                                <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                            </td>";
                        } else{
                            echo "<td rowspan='" . $rowspan ."'>
                                <span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span>
                            </td>";

                        }
    
                        $onGoingRow = true;
                        foreach($facultyDetails['courses'] as $courseId => $groupsArray) {
                            $courseName = $conn->query("SELECT fullname from {$prefix}course where id = $courseId") -> fetch_assoc()['fullname'];
                            if(!$onGoingRow) {
                                echo "<tr>";
                            }
                            if(is_array($groupsArray)) {
                                echo "<td rowspan = '" . count($groupsArray) . "' onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                $onGoingGroupRow = true;
                                foreach($groupsArray as $groupName => $groupAverage) {
                                    if(!$onGoingGroupRow) {
                                        echo "<tr>";
                                    }
                                    echo "<td>" . $groupName . "</td>";
                                    $average = number_format((float)$groupAverage, 2);
                                    $color = getColor($average);
                                    echo "<td>
                                        <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                    </td>";
                                    echo "</tr>";
                                    $onGoingGroupRow = false;
                                }
                            } else{
                                // groups array as Course Average which do not have group
                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                $check = false;
                                if($groupsArray == 'NGA') {
                                    echo "<td> No Group Assigned </td>"; 
                                    echo "<td>
                                        <span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span>
                                    </td>";
                                    $check = true;
                                } else if($groupsArray == 'NA'){
                                    echo "<td>No Group</td>";
                                    echo "<td><span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";
                                    $check = true;
                                }
                                if(!$check) {
                                    echo "<td>No Group</td>";
                                    $average = number_format($groupsArray, 2);
                                    $color = getColor($average);
                                    echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>". $average . "</span></td>";

                                }
                            }
                            echo "</tr>";
                            $onGoingRow = false;
                        }   
                        echo "</tr>";
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

            XLSX.writeFile(workbook, `Visiting Faculty A_Y_${selectedAcademicYear}.xlsx`);
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