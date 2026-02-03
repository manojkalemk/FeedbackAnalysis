<?php

    foreach($facultyCourseWiseAverageArray[$facultyId] as $courseId => $feedbackDetails) {
        if($courseId != 'facultyWeightage') {
            $courseName=$conn->query("SELECT fullname from {$prefix}course where id=$courseId")->fetch_assoc()['fullname'];
            echo "<div class='enrolledCourseContainer'>";
            echo "<div class='feedback-questions-container' id='$courseName'>";
            echo "    <div class='details'>";
            echo "        <h2>$courseName</h2> ";
            echo "        <p>Average:" .  $feedbackDetails['facultyCourseWeightage'] . "</p>";
            echo "    </div>";
            echo "    <img src='arrow.png' alt='loading' >";
            echo "</div>";
            echo "<div class='group-feedback' id='$courseName-question'>";
            foreach($feedbackDetails as $feedbackId => $questionInfo) {
                if($feedbackId != 'facultyCourseWeightage') {
                    $sr_no = 1;
                    echo "<div class='questions-container'>";
                    echo "<h1>Section : " . $questionInfo['groupName'] . "</h1>";
                    echo "    <table border='1'>";
                    echo "        <thead>";
                    echo "            <tr>";
                    echo "                <th>Sr. No</th>";
                    echo "                <th>Question</th>";
                    echo "                <th>Weightage</th>";
                    echo "                <th>Mean</th>";
                    echo "                <th>Median</th>";
                    echo "                <th>Standard Deviation</th>";
                    echo "                <th>Weightage Score</th>";
                    echo "            </tr>";
                    echo "        </thead>";
                    echo "        <tbody>";
                    foreach($questionInfo as $itemId => $itemDetails) {
                        if($itemId !='weightage' && $itemId !='groupName') {
                            $question=$conn->query("SELECT name as question from {$prefix}feedback_item where id=$itemId")->fetch_assoc()['question'];
                            $question = str_replace($sr_no . '.', '', $question);
                            $question = str_replace('12.', '', $question);
                            $question = str_replace('11.', '', $question);
                            echo "            <tr>";
                            echo "                <td>$sr_no</td>";
                            echo "                <td id='question'>$question</td>";
                            echo "                <td>" . $itemDetails['weightage'] . "</td>";
                            echo "                <td>" . $itemDetails['mean'] . "</td>";
                            echo "                <td>" . $itemDetails['median'] . "</td>";
                            echo "                <td>" . number_format($itemDetails['deviation'], 2) . "</td>";
                            echo "                <td>" . $itemDetails['weightedScore'] . "</td>";
                            echo "            </tr>";
                            $sr_no++;
                        }
                    }
                    echo "        </tbody>";
                    echo "    </table>";
                    echo "</div>";
                    echo "<hr>";
                }
            }
            echo "</div>";
            echo "</div>";
        }
        
    }
    
?>
