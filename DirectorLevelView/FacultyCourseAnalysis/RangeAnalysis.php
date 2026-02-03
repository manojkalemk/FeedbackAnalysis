<?php
    include "../../db.php";
    session_start();
    if(isset($_GET['type']) && $_GET['range'] && isset($_SESSION['facultyCourseWiseAverageArray']) && isset($_SESSION['courseFacultyAverages']) && isset($_SESSION['lowestHighest'])) {
        $type = $_GET['type'];
        $range = $_GET['range'];
        $facultyCourseWiseAverageArray = json_decode($_SESSION['facultyCourseWiseAverageArray'], true);
        $courseFacultyAverages = json_decode($_SESSION['courseFacultyAverages'], true);
        $lowestHighest = json_decode($_SESSION['lowestHighest'], true);
        $feedbackInCourse = json_decode($_SESSION['feedbackInCourse'], true);
        // $selectedAcademicYear = json_decode($_SESSION['selectedAcademicYear'], true);
        $selectedAcademicYear = $_SESSION['selectedAcademicYear'];

        $highestScoringFacultyId = $lowestHighest['HSF'];
        $lowestScoringFacultyId = $lowestHighest['LSF'];
        $highestScoringCourseId = $lowestHighest['HSC'];
        $lowestScoringCourseId = $lowestHighest['LSC'];

        $type = strtoupper($type[0]) . substr($type, 1);
        if($range == "B4.0TO4.5") {
            $title = "$type Score Between 4.0 and 4.5";
        } else if($range == "B3.5TO4") {
            $title = "$type Score Between 3.5 and 4.0";
        }else if($range == "A4.5") {
            $title = "$type Score Above 4.5";
        } else if($range == "total") {
            $title = "Total Institute $type";
        } else if($range == "HS") {
            $title = "Highest Scoring $type";
        }else if($range == "LS") {
            $title = "Lowest Scoring $type";
        } else{
            $title = "$type Score Below 3.5";
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
    } else{
        echo "Invalid Request";
        exit;
    }

?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="RangeAnalysis.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" href="../../assets/logo.png" type="image/x-icon">
</head>
<body>
    <div class="range-container">
        <div class="navbar">    
            <h1>SYMBIOSIS</h1>
            <h2>Teacher Feedback Analysis</h2>
            <p id='download'>Download</p>
        </div>

        <div class="range-analysis">
            <div class="header">
                <h1>
                <?php
                    echo $title;
                ?>
                </h1>
                <h3>
                    <?php echo "A.Y. " . $selectedAcademicYear; ?>
                </h3>
            </div>

            <div class="range-info">
                <table>
                    <?php
                    if($type == "Course") { ?>
                        <thead>
                            <tr>
                                <th><?php echo $type;?> Name</th>
                                <th>Enrolled Student</th>
                                <th>Total Feedback Instances</th>
                                <th>Feedback Submitted</th>
                                <th>Average</th>
                            </tr>
                        </thead>

                    <?php } else { ?>
                    <thead>
                        <tr>
                            <th><?php echo $type;?> Name</th>
                            <th>Average</th>
                        </tr>
                    </thead>
                    <?php } ?>
                    <tbody>
                        <?php
                            if($type == "Faculty") {
                                if($range == "HS") {
                                    $facultyId = $highestScoringFacultyId;
                                    $facultyName = $conn->query("SELECT concat(firstname, ' ', lastname) as name FROM {$prefix}user WHERE id = $facultyId")->fetch_assoc()['name'];
                                    echo "<tr>";
                                    echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>"; 
                                    $average = number_format($facultyCourseWiseAverageArray[$facultyId]['facultyWeightage'], 2);
                                    $color = getColor($average);
                                    echo "<td>
                                        <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                    </td>";
                                    echo "</tr>";
                                } else if($range == "LS") {
                                    $facultyId = $lowestScoringFacultyId;
                                    $facultyName = $conn->query("SELECT concat(firstname, ' ', lastname) as name FROM {$prefix}user WHERE id = $facultyId")->fetch_assoc()['name'];
                                    echo "<tr>";
                                    echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>"
                                    . $facultyName . "</td>";
                                    $average = number_format($facultyCourseWiseAverageArray[$facultyId]['facultyWeightage'], 2);
                                    $color = getColor($average);
                                    echo "<td>
                                        <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                    </td>";
                                    echo "</tr>";
                                } else {
                                    foreach($facultyCourseWiseAverageArray as $facultyId => $average) {
                                        
                                        echo "<tr>";
                                        $facultyName = $conn->query("SELECT concat(firstname, ' ', lastname) as name FROM {$prefix}user WHERE id = $facultyId")->fetch_assoc()['name'];
                                        if($range == "total") {
                                            if(isset($average['facultyWeightage'])) {
                                                echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>";
                                                $average = number_format($average['facultyWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                            }
                                            
                                        } else if($range == "A4.5") {
                                            if(isset($average['facultyWeightage']) && $average['facultyWeightage'] > 4.5) {
                                                echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>";
                                                $average = number_format($average['facultyWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                            }
                                        } else if($range == "B4.0TO4.5") {
                                            if(isset($average['facultyWeightage']) && $average['facultyWeightage'] >= 4.0 && $average['facultyWeightage'] < 4.5) {
                                                echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>";
                                                $average = number_format($average['facultyWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                            } 
                                            
                                        } else if($range == "B3.5TO4") {
                                            if(isset($average['facultyWeightage']) && $average['facultyWeightage'] >= 3.5 && $average['facultyWeightage'] < 4.0) {
                                                echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>";
                                                $average = number_format($average['facultyWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                            } 
            
                                        } else if($range == "B3.5") {
                                            if(isset($average['facultyWeightage']) && $average['facultyWeightage'] < 3.5) {
                                                echo "<td onClick='facultyAnalysis($facultyId)' class='faculty'>" . $facultyName . "</td>";
                                                $average = number_format($average['facultyWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                            } 
                                        }
                                        echo "</tr>";
                                    }
                                }

                            } else if($type == "Course"){
                                if($range == "HS") {
                                    $courseId = $highestScoringCourseId;
                                    $courseName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id = $courseId")->fetch_assoc()['fullname'];
                                    echo "<tr>";
                                    echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                    $average = number_format($courseFacultyAverages[$courseId]['courseWeightage'], 2);
                                    $color = getColor($average); 
                                    echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                    echo "</tr>";
                                } else if($range == "LS") {
                                    $courseId = $lowestScoringCourseId;
                                    $courseName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id = $courseId")->fetch_assoc()['fullname'];
                                    echo "<tr>";
                                    echo "<td onClick='courseAnalysis($courseId)' class='course'>". $courseName . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                    echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                    $average = number_format($courseFacultyAverages[$courseId]['courseWeightage'], 2);
                                    $color = getColor($average);
                                    echo "<td><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span></td>";
                                    echo "</tr>";
                                } else {
                                    foreach($courseFacultyAverages as $courseId => $average) {
                                        echo "<tr>";
                                        $courseName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id = $courseId")->fetch_assoc()['fullname'];
                                        if($range == "total") {
                                            if(isset($average['courseWeightage'])) {
                                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                                $average = number_format($average['courseWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            } 
                                            
                                            
                                        } else if($range == "A4.5") {
                                            if(isset($average['courseWeightage']) && $average['courseWeightage'] > 4.5) {
                                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                                $average = number_format($average['courseWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            }
                                        } else if($range == "B4.0TO4.5") {
                                            if(isset($average['courseWeightage']) && $average['courseWeightage'] >= 4.0 && $average['courseWeightage'] < 4.5) {
                                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                                $average = number_format($average['courseWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            } 
                                            
                                        } else if($range == "B3.5TO4") {
                                            if(isset($average['courseWeightage']) && $average['courseWeightage'] >= 3.5 && $average['courseWeightage'] < 4.0) {
                                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                                $average = number_format($average['courseWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            } 
                                            
                                        } else if($range == "B3.5") {
                                            if(isset($average['courseWeightage']) && $average['courseWeightage'] < 3.5) {
                                                echo "<td onClick='courseAnalysis($courseId)' class='course'>" . $courseName . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['enrolledStudent'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalStudentFeedback'] . "</td>";
                                                echo "<td>" . $feedbackInCourse[$courseId]['totalResponses'] . "</td>";
                                                $average = number_format($average['courseWeightage'], 2);
                                                $color = getColor($average);
                                                echo "<td>
                                                    <span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . $average . "</span>
                                                </td>";
                                            }    
                                            
                                        }
                                        echo "</tr>";
                                    }
                                }
                            } else {
                                echo "Invalid type";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let serverName = '<?php echo $fullServerName; ?>';
        let selectedAcademicYear = '<?php echo $selectedAcademicYear; ?>';
        let protocol = '<?php echo $protocol; ?>';
        const title = "<?php echo $title; ?>";
        document.getElementById("download").addEventListener("click", function () {
            const table = document.querySelector("table");
            const workbook = XLSX.utils.book_new();

            const tableData1 = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table), { header: 1 });

            const header1 = [title];
            const header2 = [selectedAcademicYear];
            const combinedData1 = [header1, header2, [], ...tableData1];

            const worksheet = XLSX.utils.aoa_to_sheet(combinedData1);
            XLSX.utils.book_append_sheet(workbook, worksheet, "Range Analysis");

            XLSX.writeFile(workbook, `${title}_AY_${selectedAcademicYear}.xlsx`);
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