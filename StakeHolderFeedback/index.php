<?php

include '../db.php';

// session_start();

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
            
            $accessDesignationArray = ['director', 'deputy director', 'dy director', 'head'];
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

    $academicYearListSelect = "2024 - 2025";
    if(isset($_POST['templateFeedbackId']) && isset($_POST['academicYear'])) {
        $templateFeedbackId = $_POST['templateFeedbackId'];
        $academicYearListSelect = $_POST['academicYear'];
    } else{
        $templateFeedbackId = $_GET['id'];
    }

    $totalNoOfRespondents = 0;
    $filterYear = explode(" - ", $academicYearListSelect);
    $startYear = $filterYear[0];
    $endYear = $filterYear[1];  

    $feedbackName = $conn->query("SELECT name FROM {$prefix}feedback WHERE id = $templateFeedbackId")->fetch_assoc()['name'];
    $feedbackName = str_replace("-", "", $feedbackName);
    $feedbackName = preg_replace('/\s+/', ' ', $feedbackName);
    $feedbackName = trim($feedbackName);

    $feedbackName  = explode(" ", $feedbackName);
    $feedbackQuery = "SELECT id, fullname FROM {$prefix}course where";
    
    foreach($feedbackName as $index => $name) {
        if($index == count($feedbackName) - 1) {
            $feedbackQuery .= " fullname like '%$name%'";
        } else {
            $feedbackQuery .= " fullname like '%$name%' and";
        }
    }

    function calculateAspects($label, &$aspectsValues, $average, $qno) {
        $label = trim(substr(strstr($label, "="), 1));
        switch($label) {
            case "AE" : $aspectsValues['AE'][$qno] = $average; break;
            case "CA" : $aspectsValues['CA'][$qno] = $average; break;
            case "TQ" : $aspectsValues['TQ'][$qno] = $average; break;
            case "AA" : $aspectsValues['AA'][$qno] = $average; break;
            case "FP" : $aspectsValues['FP'][$qno] = $average; break;
            case "AP" : $aspectsValues['AP'][$qno] = $average; break;
            case "SS" : $aspectsValues['SS'][$qno] = $average; break;
            case "CC" : $aspectsValues['CC'][$qno] = $average; break;
            case "INF": $aspectsValues['INF'][$qno] = $average; break;
            case "CF" : $aspectsValues['CF'][$qno] = $average; break;
            case "IA" : $aspectsValues['IA'][$qno] = $average; break;
            case "CI" : $aspectsValues['CI'][$qno] = $average; break;
            case "OS" : $aspectsValues['OS'][$qno] = $average; break;
            case "GP" : $aspectsValues['GP'][$qno] = $average; break;
            case "IE" : $aspectsValues['IE'][$qno] = $average; break;

        }
    }
    
    $feedbackList = [];
    $feedbackCourseName = '';
    $courseId = $conn->query($feedbackQuery);
    if($courseId -> num_rows > 0) {
        $courseId = $courseId -> fetch_assoc()['id'];
        $courseFeedbackId = $conn->query("SELECT id, name FROM {$prefix}feedback WHERE course = $courseId and name like '%{$startYear}%' and name like '%{$endYear}%'");
        if($courseFeedbackId -> num_rows > 0) {
            while($row = $courseFeedbackId->fetch_assoc()) {
                $feedbackList[$row['id']] = $row['name'];
                $feedbackCourseName = $row['name'];
            }
            
        } else {
            $feedbackList = "No Feedback available for selected Academic Year"; 
        }
    } else {
        $feedbackList = "No Course Found!";
    }
    $feedbackOptionsList = [];
    $feedbackYesNoOptionsList = [];
    if(is_array($feedbackList)) {
        foreach($feedbackList as $feedbackId => $feedbackName) {
            $totalResponses = $conn->query("SELECT count(*) as totalResponses FROM {$prefix}feedback_completed WHERE feedback = $feedbackId")->fetch_assoc()['totalResponses'];

            $feedbackValidQuestionsId = $conn->query("SELECT id, name, presentation, label from {$prefix}feedback_item  where feedback = $feedbackId and (typ='multichoicerated' or typ='multichoice') and name != '2.Institute'");
            $aspectsValues[$feedbackId] = ["AE" => [], "CA" => [], "TQ" => [], "AA"=>[], "FP"=>[], "AP"=>[], "SS"=>[], "CC"=>[], "INF"=>[], "CF"=>[], "IA"=>[], "CI"=>[], "OS"=>[], "GP"=>[], "IE"=>[]];
            $sr_no = 1;
            $yesNoSrno = 1;
            while($row = $feedbackValidQuestionsId->fetch_assoc()) {
                $answerRatingSum = 0;
                $questionId = $row['id'];
                $questionResponses = $conn->query("SELECT value FROM {$prefix}feedback_value WHERE item = $questionId");
                if(strpos($row['presentation'], "#") !== false) {
                    
                    $feedbackValues = ["1"=>0, "2"=>0, "3"=>0, "4"=>0, "5"=>0];
                    $totalAnswers = 0;
                    if($questionResponses -> num_rows > 0) {
                        while($values = mysqli_fetch_assoc($questionResponses)) {
                            switch($values['value']) {
                                case "1": $answerRatingSum += 5; $feedbackValues["5"]++; $totalAnswers++; break;
                                case "2": $answerRatingSum += 4; $feedbackValues["4"]++; $totalAnswers++; break;
                                case "3": $answerRatingSum += 3; $feedbackValues["3"]++; $totalAnswers++; break;
                                case "4": $answerRatingSum += 2; $feedbackValues["2"]++; $totalAnswers++; break;
                                case "5": $answerRatingSum += 1; $feedbackValues["1"]++; $totalAnswers++; break;
                            }
                        }
                    }

                    $feedbackOptionsList[$feedbackId][$questionId] = $feedbackValues;
                    $feedbackOptionsList[$feedbackId][$questionId]['averageScore'] = number_format($answerRatingSum / $totalAnswers, 2);
                    
                    calculateAspects($row['label'], $aspectsValues[$feedbackId], $feedbackOptionsList[$feedbackId][$questionId]['averageScore'], $sr_no);
                    $sr_no++;
                    $totalNoOfRespondents = max(array_sum($feedbackValues), $totalNoOfRespondents);
                } else{
                    $feedbackValues = ["1"=>0, "2"=>0];
                    $totalAnswers = 0;
                    if($questionResponses -> num_rows > 0) {
                        while($values = mysqli_fetch_assoc($questionResponses)) {
                            switch($values['value']) {
                                case "1": $answerRatingSum += 2; $feedbackValues["2"]++; $totalAnswers++; break;
                                case "2": $answerRatingSum += 1; $feedbackValues["1"]++; $totalAnswers++; break;
                            }
                        }
                    }
                    $feedbackYesNoOptionsList[$feedbackId][$questionId] = $feedbackValues;
                    $feedbackYesNoOptionsList[$feedbackId][$questionId]['averageScore'] = number_format($feedbackValues["2"] / $totalAnswers * 100) . "%";
                    calculateAspects($row['label'], $aspectsValues[$feedbackId], $feedbackYesNoOptionsList[$feedbackId][$questionId]['averageScore'], $yesNoSrno);
                    $yesNoSrno++;
                    $totalNoOfRespondents = max(array_sum($feedbackValues), $totalNoOfRespondents);
                }
            }

            $feedbackRecommendation = $conn->query("SELECT id, name, presentation, label from {$prefix}feedback_item  where feedback = $feedbackId and typ='textarea'");
            $textFields = [];
            $questionName = '';
            if($feedbackRecommendation -> num_rows > 0) {
                $details = $feedbackRecommendation -> fetch_assoc();
                $questionId = $details['id'];
                $questionName = $details['name'];
                $recommendations = $conn->query("SELECT value FROM {$prefix}feedback_value WHERE item = $questionId");
                while($text = mysqli_fetch_assoc($recommendations)) {
                    if(!empty($text['value'])) {
                        $textFields[] = $text['value'];
                    }
                }
            }
            
        }
    } else {
        // echo json_encode(["error" => "No Feedback Found!"]);
        echo $feedbackList;
        exit;
    }

    // echo "<preZ";

    $overallAverage = 0;


    // Remove bullets and trim text
    $cleanedTextFields = array_filter(array_map(function($text) {
        // Remove common bullet characters and trim
        $text = preg_replace('/^[\s\p{Pd}•*‣▪◦?\-]+/u', '', $text); // remove bullet-like chars at start
        $text = trim($text); // remove any remaining whitespace
        return $text;
    }, $textFields));

    // Optional: remove empty values after cleaning
    $cleanedTextFields = array_values(array_filter($cleanedTextFields));

    // echo "<pre>";
    // print_r($aspectsValues);
    // echo "</pre>";

?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $feedbackName; ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

</head>
<body>
    <div class="stakeHolderFeedbackContainer">
        <div class="navbar">    
            <h1>SYMBIOSIS</h1>
            <h2><?php echo $feedbackCourseName; ?></h2>
            <p id='download' onclick="exportToExcelXLSX()">Download</p>
        </div>

        <div class="info">
            <?php
                $instituteName = $conn->query("SELECT fullname, shortname from {$prefix}course where id=1")->fetch_assoc();
                echo "<h2 id='instituteName'>" . $instituteName['fullname'] . "</h2>";
                echo "<p id='designation'>Director dashboard</p>";
                echo "<p id='designation'>Total No of Respondentes: $totalNoOfRespondents</p>";
            
                $shortName = $instituteName['shortname'];
            ?>

            <!-- <button id='download'>Download</button> -->
        </div>

        <form action="index.php" method="post" id='year'>
            <label for="Ayear">Choose Academic Year</label>
            <select name="Ayear" id="Ayear">
                <option value="<?php echo $academicYearListSelect; ?>"><?php echo $academicYearListSelect; ?></option>
                <?php
                    $currentYear = date('Y');
                    $startYear = "2025"; 
            
                    while ($startYear <= ($currentYear)) { 
                        $academicYear = $startYear . " - " . ($startYear+1) ;
                        if($academicYear !== $academicYearListSelect) {
                            echo "<option value='$academicYear'>" . $academicYear . "</option>";
                        }
                        $startYear ++;
                    }
                ?>
            </select>
        </form>

        <div class="analysis">
            <!-- Questionwise Analysis -->
            <table border='1' id='questionTable'>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Question</th>
                        <th>Strongly Disagree</th>
                        <th>Disagree</th>
                        <th>Neutral</th>
                        <th>Agree</th>
                        <th>Strongly Agree</th>
                        <th>Average Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $srNo = 1;
                        $totalQuestion = 0;
                        foreach($feedbackOptionsList as $feedbackId => $questions) {
                            foreach($questions as $questionId => $question) {
                                $questionName = $conn->query("SELECT name FROM {$prefix}feedback_item WHERE id = $questionId")->fetch_assoc()['name'];
                                $overallAverage += $question['averageScore'];
                                $questionName = preg_replace('/^\d+\.\s*/', '', $questionName);;
                                echo "<tr>";
                                echo "<td>$srNo</td>";
                                echo "<td id='question'>$questionName</td>";
                                echo "<td>$question[1]</td>";
                                echo "<td>$question[2]</td>";
                                echo "<td>$question[3]</td>";
                                echo "<td>$question[4]</td>";
                                echo "<td>$question[5]</td>";
                                echo "<td>$question[averageScore]</td>";
                                echo "</tr>";
                                $srNo++;
                                $totalQuestion++;
                            }  
                        }
                    ?>
                </tbody>
            </table>
            
            <!-- Yes No Questionwise Analysis -->
            <table border='1' id='yesNoQuestionTable'>
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Question</th>
                        <th>Yes/Recommended/Very Satisfied</th>
                        <th>No/Not Recommended/Other</th>
                        <th>Percentage/Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $srNo = 1;
                        foreach($feedbackYesNoOptionsList as $feedbackId => $questions) {
                            foreach($questions as $questionId => $question) {
                                $questionName = $conn->query("SELECT name FROM {$prefix}feedback_item WHERE id = $questionId")->fetch_assoc()['name'];
                                $questionName = preg_replace('/^\d+\.\s*/', '', $questionName);;
                                echo "<tr>";
                                echo "<td>$srNo</td>";
                                echo "<td id='question'>$questionName</td>";
                                echo "<td>$question[2]</td>";
                                echo "<td>$question[1]</td>";
                                echo "<td>$question[averageScore]</td>";
                                echo "</tr>";
                                $srNo++;
                            }  
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="report">
            <!-- Aspects Results Score -->
            <h1>Feedback Report</h1>
            <table border='1'>
                <thead>
                    <tr>
                        <th>Aspect</th>
                        <th>Questions</th>
                        <th>Average Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $filteredAspectsAverage = [];
                        foreach($aspectsValues as $feedbackId => $aspectsArray) {
                            foreach($aspectsArray as $aspects => $details) {
                                echo "<tr>";
                                if(is_array($details) && count($details) > 0) {
                                    $flag = false;
                                    foreach ($details as $key => $value) {
                                        if (strpos($value, '%') !== false) {
                                            $details[$key] = (floatval($value) / 100) * 5;
                                            $flag = true;
                                        }
                                    }
                                    if(!$flag)
                                        $aspectAverage = number_format(array_sum($details) / count($details), 2);
                                    else{
                                        $aspectAverage = number_format(array_sum($details), 2);
                                    }
                                    $questionNumbers = implode(", ", array_keys($details));
                                    switch($aspects) {
                                        case "AE" : echo "<td> Alumni Engagement </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>"; 
                                                    $filteredAspectsAverage['Alumni Engagement'] = $aspectAverage; break;
                                                    
                                        case "CA" : echo "<td> Curriculum </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Curriculum'] = $aspectAverage; break;

                                        case "TQ" : echo "<td> Teaching Quality </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Teaching Quality'] = $aspectAverage; break;
                                                    
                                        case "AA" : echo "<td> Assesment </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Assesment'] = $aspectAverage; break;

                                        case "FP" : echo "<td> Feedback Process </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Feedback Process'] = $aspectAverage; break;

                                        case "AP" : echo "<td> Admission Process </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Admission Process'] = $aspectAverage; break;

                                        case "SS" : echo "<td> Student Support </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Student Support'] = $aspectAverage; break;

                                        case "CC" : echo "<td> Campus Culture </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Campus Culture'] = $aspectAverage; break;

                                        case "INF": echo "<td> Infrastructure </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Infrastructure'] = $aspectAverage; break;

                                        case "CF" : echo "<td> Campus Facilities </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Campus Facilities'] = $aspectAverage; break;

                                        case "IA" : echo "<td> Institutional Awareness </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Institutional Awareness'] = $aspectAverage; break;

                                        case "CI" : echo "<td> Curriculum Improvement </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Curriculum Improvement'] = $aspectAverage; break;

                                        case "OS" : echo "<td> Overall Satisfaction </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Overall Satisfaction'] = $aspectAverage; break;

                                        case "GP" : echo "<td> Graduate Preparedness </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Graduate Preparedness'] = $aspectAverage; break;

                                        case "IE" : echo "<td> Industry Engagement </td>"; 
                                                    echo "<td> $questionNumbers </td>"; 
                                                    echo "<td> $aspectAverage </td>";
                                                    $filteredAspectsAverage['Industry Engagement'] = $aspectAverage; break;
                            
                                    }
                                }
                                echo "</tr>";
                            }
                        }

                    ?>
                </tbody>
            </table>
            <p>Overall Satisfaction Score : <span> <?php echo number_format($overallAverage/$totalQuestion, 2); ?></span></p>

            <div class="details">
                <table border='1' id='keyMatrics'>
                    <thead>
                        <tr>
                            <th colspan='2' id='heading'>Key Matrics</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php 
                            foreach($feedbackYesNoOptionsList as $feedbackId => $questions) {
                                foreach($questions as $questionId => $question) {
                                    $questionName = $conn->query("SELECT name FROM {$prefix}feedback_item WHERE id = $questionId")->fetch_assoc()['name'];
                                    $questionName = preg_replace('/^\d+\.\s*/', '', $questionName);;
                                    echo "<tr>";
                                    echo "<td id='question'>$questionName</td>";
                                    echo "<td>$question[averageScore]</td>";
                                    echo "</tr>";
                                }  
                            }
                        ?>
                        <tr>
                            <td>Overall Satisfaction</td>
                            <td><?php echo number_format((($overallAverage/$totalQuestion)/5)*100, 0) . "%"; ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php  asort($filteredAspectsAverage); ?>
                <table border='1' id='strengths'>
                    <thead>
                        <tr>
                            <th colspan='2' id='heading'>Strengths</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $top=1;
                            foreach($filteredAspectsAverage as $aspectName => $average) {
                                if($top > (count($filteredAspectsAverage) - 3)) {
                                    echo "<tr>";
                                    echo "<td>" . $aspectName . "</td>";
                                    echo "<td>" . $average . "</td>";
                                    echo "</tr>";
                                }
                                $top++;
                            }
                        ?>
                        
                    </tbody>
                </table>    
                <table border='1' id='areasOfImprovement'>
                    <thead>
                        <tr>
                            <th colspan='2' id='heading'>Areas of Improvement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $top=1;
                            foreach($filteredAspectsAverage as $aspectName => $average) {
                                if($top <=3) {
                                    echo "<tr>";
                                    echo "<td>" . $aspectName . "</td>";
                                    echo "<td>" . $average . "</td>";
                                    echo "</tr>";
                                }
                                $top++;
                            }
                        ?>
    
                    </tbody>
                </table>
            </div>

            <div class="recommendations">
                <h1>Recommendations</h1>

                <?php
                    foreach($textFields as $answers) {
                        echo "<p>" . $answers . "</p>";
                    }
                ?>

            </div>
        </div>
    </div>

    <script>
        function exportToExcelXLSX() {
            let feedbackName = <?php echo json_encode($feedbackName ?? 'Feedback'); ?>;
            let shortname = <?php echo json_encode($shortName ?? 'Feedback'); ?>;
            let recommendationsData = <?php echo json_encode($cleanedTextFields ?? []); ?>;

            let average = <?php 
                if($totalQuestion > 0) {
                    echo number_format($overallAverage/$totalQuestion, 2);
                } else{
                    echo "0";
                }
                ?>

            const table1 = document.querySelector("#questionTable"); 
            const table2 = document.querySelector("#yesNoQuestionTable");
            const table3 = document.querySelector("#areasOfImprovement");
            const table4 = document.querySelector("#strengths");
            const table5 = document.querySelector("#keyMatrics");
    
            const workbook = XLSX.utils.book_new();
            const worksheetData = [];

            let instituteName = document.getElementById('instituteName').textContent;
            let designation = document.getElementById('designation').textContent;
            let ay = document.getElementById('Ayear').textContent;
    
            worksheetData.push([]);
            worksheetData.push([instituteName]);
            worksheetData.push([designation]);
            worksheetData.push([feedbackName]);
            worksheetData.push([ay]);
            worksheetData.push([]);
            const table1Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table1), { header: 1 });
            worksheetData.push(...table1Data);
            worksheetData.push([]);
    
            worksheetData.push([]);
            const table2Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table2), { header: 1 });
            worksheetData.push(...table2Data);
            worksheetData.push([]);
            worksheetData.push(["Overall Satisfaction Score", average]);
    
            worksheetData.push([]);
            const table3Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table3), { header: 1 });
            worksheetData.push(...table3Data);
            worksheetData.push([]);
            worksheetData.push([]);
    
            const table4Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table4), { header: 1 });
            worksheetData.push(...table4Data);
            worksheetData.push([]);
            worksheetData.push([]);
    
            const table5Data = XLSX.utils.sheet_to_json(XLSX.utils.table_to_sheet(table5), { header: 1 });
            worksheetData.push(...table5Data);
            worksheetData.push([]);
            worksheetData.push(["Recommendations"]);
            
            worksheetData.push([]);
            let index = 1;
            for(let text of recommendationsData) {
                worksheetData.push([index + ")" + text]);
                index++;
    
            }
    
            const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
    
            XLSX.utils.book_append_sheet(workbook, worksheet, "Feedback Analysis");
    
            XLSX.writeFile(workbook, `${shortname} ${feedbackName}.xlsx`);
        }
        
        let userId = '<?php echo $userId; ?>';
        let templateFeedbackId = '<?php echo $templateFeedbackId; ?>';
        document.getElementById('Ayear').addEventListener('change', function() {

            let academicYear = this.value;

            const currentUrl = new URL(window.location.href);
            const params = new URLSearchParams(currentUrl.search);
            const paramValue = params.get('id');

            let form = document.getElementById('year');

            const input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'academicYear'; 
            input1.value = `${academicYear}`; 
            form.appendChild(input1);

    
            const input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'templateFeedbackId'; 
            input2.value = `${templateFeedbackId}`;
            form.appendChild(input2);

            document.body.appendChild(form);
            form.submit();
            
        })
    
    </script>
</body>
</html>


