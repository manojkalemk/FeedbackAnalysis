<?php 
session_start();
require_once "../db.php";
$serverName = $_SERVER['SERVER_NAME'];
$serverPort = $_SERVER['SERVER_PORT'];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
if (($protocol === 'http' && $serverPort == 80) || ($protocol === 'https' && $serverPort == 443)) {
    $fullServerName = $serverName . "/attendance-report";
} else {
    $fullServerName = $serverName . ':' . $serverPort . "/attendance-report";
}

$type='mid';

if(isset($_COOKIE['userId'])) {

    $userId = $_COOKIE['userId'];
    $checkDesignation = $conn->query("SELECT u.id as userId, ud.data as designation, concat(u.firstname, ' ', u.lastname) as fullname from {$prefix}user u
            join {$prefix}user_info_data as ud on ud.userid = u.id
            join {$prefix}user_info_field as uf on uf.id = ud.fieldid
            where uf.name like '%designation%' and u.id=$userId");
    
    if($checkDesignation -> num_rows > 0) {
        $data = $checkDesignation -> fetch_assoc();
        $designation = $data['designation'];
        $fullName = $data['fullname'];
        
        $accessDesignationArray = ['director', 'deputy director', 'dy director', 'head', 'feedback incharge'];
        if($designation == '' && !in_array(strtolower($designation), $accessDesignationArray)) {
            echo "Access denied. Please check your designation. Ensure the designation is spelled correctly and is not left blank.";
            exit;
        }
    } else{
        echo "User not found in a database";
        exit;
    }
} else{
    header("Location: $protocol://$fullServerName/FeedbackAnalysis");
    exit;
}

if(isset($_GET['type'])){
    // $type=$_GET['type'];
    $type='mid';
    
}
// echo $type;

$academicYearListSelect = [];

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['academicYearSelect'])) {
//     $academicYearListSelect[] = $_POST['academicYearSelect'];
// } else{
//     $academicYearListSelect[] = '2024 - 2025';
// }

if (isset($_GET['year'])) {
    $academicYearListSelect[] = $_GET['year'];
    // echo "<br>GET METHOD IS USED HERE for date :" . $academicYearListSelect . "<br>";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['academicYearSelect'])) {
    //  echo "<br>POST METHOD IS USED HERE for date ... <br>";
    $academicYearListSelect[] = $_POST['academicYearSelect'];
} else {
    //  echo "<br>DEFAULT DATE IS USED HERE ... <br>";
    $academicYearListSelect[] = '2024 - 2025';
}

$selectedAcademicYear = $academicYearListSelect[0];

$_SESSION['selectedAcademicYear'] = $selectedAcademicYear;

include "../Backend/Template.php";


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Feedback Analysis</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" href="../assets/logo.png" type="image/x-icon">
    <script>
        function submitform(courseInProgram, programName) {
            document.getElementById('courseInProgram').value = courseInProgram;
            document.getElementById('programName').value = programName;
            document.getElementById('feedbackType').value = feedbackType;
            document.getElementById('hiddenForm').submit();
        }
    </script>
    <style>
        .excelButton {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            background: #003667;
            font-family: math;
            font-size: 15px;
            color: white;
        }
    </style>
</head>
<body>  
    <div class="container">

        <div class="navbar">
            <h1>SYMBIOSIS</h1>
            <h2>Teacher Feedback Analysis</h2>
            <div class="academicYearSelection">
                <form action="Directorview.php" method="post" id='year'>
                    
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($_GET['type'] ?? 'mid'); ?>">
                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($_GET['year'] ?? $academicYearListSelect[0]); ?>">
                    
                    <label for="academicYearSelect">Choose Academic Year</label>
                    <select name="academicYearSelect" id="academicYearSelect">
                        <option value=""><?php echo $academicYearListSelect[0]; ?></option>
                        <?php
                            $currentYear = date('Y');
                            $startYear = "2022"; 
                    
                            while ($startYear <= ($currentYear)) { 
                                $academicYear = $startYear . " - " . ($startYear+1) ;
                                if($academicYear !== $academicYearListSelect[0]) {
                                    echo "<option value='$academicYear'>" . $academicYear . "</option>";
                                }
                                $startYear ++;
                            }
                        ?>
                    </select>
                </form>
            </div>
            <button class="excelButton" id='download'>Download</button>
        </div>

        <div class="info">
            <?php
                $instituteName = $conn->query("SELECT fullname, shortname from {$prefix}course where id=1")->fetch_assoc();
                echo "<h2 id='instituteName'>" . $instituteName['fullname'] . "</h2>";
                echo "<p id='designation'>Director Dashboard</p>";
                // echo "<p id='designation'>" . $designation . " : " . $fullName . "</p>";

                $shortName = $instituteName['shortname'];
            ?>
        </div>

        <div class="programWiseAnalysis">
            <h1>Institute Programwise Analysis</h1>
            <?php 
                $sumOfAverageOfProgram = 0;
                $programHaveAnAverage = 0;

                $instituteTotalCourses = 0;
                $instituteTotalFaculty = 0;

                $courseRankingAnalysis = [
                    "HSC" => 0,
                    "LSC" => 10,
                    "A4.5" => 0,
                    "B4.0TO4.5" => 0,
                    "B3.5TO4" => 0,
                    "B3.5" => 0
                ];
                include "ProgramWiseAnalysis.php"; 
            ?>

            <div class="institute-rating">
                <p>Institute Teacher Rating : <span><?php
                if($programHaveAnAverage > 0) {
                    echo number_format($sumOfAverageOfProgram / $programHaveAnAverage, 2); 
                } else{
                    echo "0.0";
                }
                 ?></span></p>
            </div>
        </div>
        <div class="faculty-course-analysis">
            <?php include "FacultyCourseAnalysis/CourseAnalysis.php"; ?>
            <?php include "FacultyCourseAnalysis/FacultyAnalysis.php"; ?>
        </div>

        <div class="inhouse-visiting-faculty-analysis">
            <div class="header">
                <h2>In detail Teacher Analysis</h2>
            </div>

            <div class="faculty-type">
                <p id='fulltime'>Fulltime Teacher</p>
                <p id='visiting'>Visiting Teacher </p>
            </div>
        </div>
    </div>


    <script>
        let data = '<?php echo json_encode($academicYearWithCourses); ?>';
        let serverName = '<?php echo $fullServerName; ?>';
        let protocol = '<?php echo $protocol; ?>';
        let shortName = '<?php echo $shortName; ?>';
        let feedbackType = '<?php echo $type; ?>';
        let selectedAcademicYear = '<?php echo $academicYearListSelect[0]; ?>';
        
        document.getElementById("academicYearSelect").addEventListener("change", function() {
            let selectedAcademicYear = this.value;
            // console.log("Academic year Data ", selectedAcademicYear);
        });

        document.getElementById('fulltime').addEventListener('click', function() {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/InHouseVisitingAnalysis/FullTimeFacultyAnalysis.php`, "_blank");
        });
        
        document.getElementById('visiting').addEventListener('click', function() {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/InHouseVisitingAnalysis/VisitingFacultyAnalysis.php`, "_blank");
        });
        
        document.getElementById('academicYearSelect').addEventListener('change', function() {
            const form = document.getElementById('year');
            form.submit();
        });
        
        function rangeAnalysis(type, range) {
            window.open(`${protocol}://${serverName}/FeedbackAnalysis/DirectorLevelView/FacultyCourseAnalysis/RangeAnalysis.php?type=` + type + "&range=" + range, "_blank");
        }

        document.getElementById("download").addEventListener("click", function () {
            const table1 = document.getElementById("programTable");
            const table2 = document.getElementById("courseTable");
            const table3 = document.getElementById("facultyTable");

            const workbook = XLSX.utils.book_new();

            let instituteName = document.getElementById('instituteName').textContent;
            let designation = document.getElementById('designation').textContent;
            const header1 = [instituteName];
            const header2 = [designation];
            const header3 = ["Institute Programwise Analysis"];
            const header4 = [`Academic Year Data : ${selectedAcademicYear}`];
            const headers = [header1, header2, header3, header4, []];

            const tableData1 = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table1), { header: 1 });
            const combinedData1 = [...headers, ...tableData1];
            const combinedWorksheet1 = XLSX.utils.aoa_to_sheet(combinedData1);
            XLSX.utils.book_append_sheet(workbook, combinedWorksheet1, "Program Analysis");

            const tableData2 = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table2), { header: 1 });
            const combinedData2 = [...headers, ...tableData2];
            const combinedWorksheet2 = XLSX.utils.aoa_to_sheet(combinedData2);
            XLSX.utils.book_append_sheet(workbook, combinedWorksheet2, "Faculty Analysis");

            const tableData3 = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table3), { header: 1 })
                .filter(row => row.some(cell => cell !== null && cell !== "")); 

            const combinedData3 = [...headers, ...tableData3];
            const combinedWorksheet3 = XLSX.utils.aoa_to_sheet(combinedData3);
            XLSX.utils.book_append_sheet(workbook, combinedWorksheet3, "Course Analysis");
            XLSX.writeFile(workbook, `${shortName}_faculty_feedback_analysis_${selectedAcademicYear}.xlsx`);
        });
    </script>
</body>
</html>

<?php
$lowestHighest = [
    'HSF' => $highestScoringFacultyId,
    'LSF' => $lowestScoringFacultyId,
    'HSC' => $highestScoringCourseId,
    'LSC' => $lowestScoringCourseId
];

$_SESSION['lowestHighest'] = json_encode($lowestHighest);   

?>