
<table border='1' id='programTable'>
    <thead>
        <tr>
            <th>Program Name</th>
            <th>Total No. of Courses</th>
            <th>Feedback Received Courses</th>
            <th>Total No. of Teacher</th>
            <th>Average Score</th>
        </tr>
    </thead>
    <tbody>
        
        <form action='ProgramAnalysis.php' method='post' id='hiddenForm' target="_blank">
            <input type="hidden" name='facultyCourseAverage' value=" <?php echo htmlspecialchars(json_encode($facultyCourseWiseAverageArray), ENT_QUOTES, 'UTF-8'); ?>" id='facultyCourseAvergae'>

            <input type="hidden" name='courseFacultyAverage' value=" <?php echo htmlspecialchars(json_encode($courseFacultyAverages), ENT_QUOTES, 'UTF-8'); ?>" id='courseFaculty'>
            
            <input type="hidden" name="courseInProgram" value="" id='courseInProgram'>
            <input type="hidden" name="programName" value="" id='programName'>
            <input type="hidden" name="feedbackType" value="" id='feedbackType'>
            <input type="hidden" name="selectedAcademicYear" value="<?php echo $selectedAcademicYear;?>" id='selectedAcademicYear'>
        </form>

        <?php 
            // echo "<pre>";
            // print_r($programWiseCourse);
            // echo "</pre>";


            foreach($programWiseCourse as $programName => $courseInPrograms) { ?>
            <tr>
            <td id="programId" class="programName" onClick="
                submitform('<?php echo htmlspecialchars(json_encode($courseInPrograms), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $programName; ?>')"
            > 
                    <?php echo $programName; ?>
            </td>
            <?php 

                // Considering the count of Courses on which Student Feedback Faculty is Present
                echo "<td style='text-align: center;'>" . count($courseInPrograms) . "</td>";
                $coursesHavingData = 0;
                $courseAverage = 0;
                $totalFaultyCount = 0;
                $facultyInProgram = [];

                foreach($courseInPrograms as $courseId => $courseDetails) {
                    if(is_array($courseDetails)) {
                        $coursesHavingData++;

                        if($courseRankingAnalysis['HSC'] < $courseDetails['courseWeightage']) {
                            $courseRankingAnalysis['HSC'] = $courseDetails['courseWeightage'];
                            $highestScoringCourseId = $courseId;
                        }

                        if($courseDetails['courseWeightage'] > 0 && $courseRankingAnalysis['LSC'] > $courseDetails['courseWeightage']) {
                            $courseRankingAnalysis['LSC'] = $courseDetails['courseWeightage'];
                            $lowestScoringCourseId = $courseId;
                        }
                        // $courseRankingAnalysis['HSC'] = max($courseRankingAnalysis['HSC'], $courseDetails['courseWeightage']);
                        // $courseRankingAnalysis['LSC'] = min($courseRankingAnalysis['LSC'], $courseDetails['courseWeightage']);
                        if($courseDetails['courseWeightage'] >= 4.5) {
                            $courseRankingAnalysis['A4.5']++;
                        }
                        if($courseDetails['courseWeightage'] >= 4.0 && $courseDetails['courseWeightage'] < 4.5) {
                            $courseRankingAnalysis['B4.0TO4.5']++;
                        }
                        if($courseDetails['courseWeightage'] >= 3.5 && $courseDetails['courseWeightage'] < 4.0) {
                            $courseRankingAnalysis['B3.5TO4']++;
                        }
                        if($courseDetails['courseWeightage'] < 3.5) {
                            $courseRankingAnalysis['B3.5']++;
                        }
                        
                        $courseAverage += $courseDetails['courseWeightage'];

                        foreach($courseDetails['faculty'] as $facultyId => $facultyAverage) {
                            if(!isset($facultyInProgram[$facultyId])) {
                                $facultyInProgram[$facultyId] = $facultyId;
                            }
                        }
                        
                    }
                }
                
                $instituteTotalCourses += $coursesHavingData;
                $instituteTotalFaculty += count($facultyInProgram);
                echo "<td style='text-align: center;' >" . $coursesHavingData . "</td>";
                echo "<td style='text-align: center;' >" . count($facultyInProgram) . "</td>";
                if($coursesHavingData > 0) {
                    $programHaveAnAverage++;
                    $sumOfAverageOfProgram += number_format($courseAverage / $coursesHavingData, 2 );

                    $average = number_format($courseAverage / $coursesHavingData, 2 );
                    $color = $feedbackAvailability->getColor($average);

                    echo "<td style='text-align: center;' ><span style='background-color: $color; padding: 0.2em 0.4em; border-radius: 5px;'>" . number_format($courseAverage / $coursesHavingData, 2 ) . "</span></td>";
                } else{
                    echo "<td style='text-align: center;' > <span style='background-color: #ff7676; padding: 0.2em 0.4em; border-radius: 5px;'>0.00</span></td>";

                }
                echo "</tr>";
            }
        ?>
       
        
    </tbody>
</table>