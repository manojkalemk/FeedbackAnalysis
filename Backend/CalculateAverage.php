<?php

class CalculateAverage {
    private $courseAverage = [];
    private $totalFacultyInCourseMapping = [];

    // Preloaded caches (populated in constructor)
    private $preloadedTotalResponses = []; // feedbackId => totalResponses
    private $preloadedQuestions = [];      // feedbackId => [ [id, question, options], ... ]
    private $preloadedAnswers = [];        // itemId => [ value1, value2, ... ]

    function __construct($conn, $prefix, $facultyFeedbackDetails) {
        // 1) Collect all feedback IDs from the input structure
        $allFeedbackIds = [];
        foreach ($facultyFeedbackDetails as $facultyId => $courseArray) {
            if (!is_array($courseArray)) continue;
            foreach ($courseArray as $courseId => $feedbackDetails) {
                if (!is_array($feedbackDetails)) continue;
                foreach ($feedbackDetails as $groupName => $feedbackId) {
                    $allFeedbackIds[(int)$feedbackId] = true;
                }
            }
        }

        // nothing to do
        if (empty($allFeedbackIds)) {
            return;
        }

        $feedbackIdList = implode(',', array_map('intval', array_keys($allFeedbackIds)));
        
        // echo "<br><hr> <pre>All feedbackIdList : ";
        // print_r($feedbackIdList);
        // echo "</pre> <br>";

        // 2) Bulk fetch totalResponses for these feedback ids
        $sqlTotalResponses = "
            SELECT feedback AS feedbackId, COUNT(userid) AS totalResponses
            FROM {$prefix}feedback_completed
            WHERE feedback IN ($feedbackIdList)
            GROUP BY feedback ";
        // echo "<br><pre>sqlTotalResponses Query : SELECT feedback AS feedbackId, COUNT(userid) AS totalResponses
        //                 FROM {$prefix}feedback_completed
        //                 WHERE feedback IN ($feedbackIdList)
        //                 GROUP BY feedback
        //         </pre><br>";
        $rs = $conn->query($sqlTotalResponses);
        if ($rs) {
            while ($row = mysqli_fetch_assoc($rs)) {
                $this->preloadedTotalResponses[(int)$row['feedbackId']] = (int)$row['totalResponses'];
            }
        }

        // 3) Bulk fetch feedback questions for these feedback ids
        $sqlQuestions = "
            SELECT id, feedback AS feedbackId, name AS question, presentation AS options
            FROM {$prefix}feedback_item
            WHERE feedback IN ($feedbackIdList)
            ORDER BY feedback, id ";
            
        // echo "<br><pre>sqlQuestions Query : SELECT id, feedback AS feedbackId, name AS question, presentation AS options
        //                                             FROM {$prefix}feedback_item
        //                                             WHERE feedback IN ($feedbackIdList)
        //                                             ORDER BY feedback, id
        //         </pre><br>";
            
        $rsQ = $conn->query($sqlQuestions);
        $allItemIds = [];
        if ($rsQ) {
            while ($row = mysqli_fetch_assoc($rsQ)) {
                $fid = (int)$row['feedbackId'];
                $iid = (int)$row['id'];
                if (!isset($this->preloadedQuestions[$fid])) $this->preloadedQuestions[$fid] = [];
                $this->preloadedQuestions[$fid][] = [
                    'id' => $iid,
                    'question' => $row['question'],
                    'options' => $row['options']
                ];
                $allItemIds[$iid] = true;
            }
        }

        // 4) Bulk fetch all answers for the collected item ids
        if (!empty($allItemIds)) {
            $itemIdList = implode(',', array_map('intval', array_keys($allItemIds)));
            $sqlAnswers = "
                SELECT item AS itemId, value
                FROM {$prefix}feedback_value
                WHERE item IN ($itemIdList) ";
        
            //  echo "<br><pre>sqlAnswers Query : SELECT item AS itemId, value
            //                                     FROM {$prefix}feedback_value
            //                                     WHERE item IN ($itemIdList)
            //     </pre><br><hr>";        
        
            $rsA = $conn->query($sqlAnswers);
            if ($rsA) {
                while ($row = mysqli_fetch_assoc($rsA)) {
                    $iid = (int)$row['itemId'];
                    if (!isset($this->preloadedAnswers[$iid])) $this->preloadedAnswers[$iid] = [];
                    // store as string (original values are strings "1".."5")
                    $this->preloadedAnswers[$iid][] = (string)$row['value'];
                }
            }
        }

        //
        // 5) Now iterate exactly like before but using preloaded data
        //
        foreach ($facultyFeedbackDetails as $facultyId => $courseArray) {
            if (!is_array($courseArray) || count($courseArray) == 0) {
                continue;
            }

            $sumofFacultyWeightage = 0;
            foreach ($courseArray as $courseId => $feedbackDetails) {
                if (!is_array($feedbackDetails) || count($feedbackDetails) == 0) {
                    continue;
                }

                $sumOfFeedbackWeightage = 0;

                foreach ($feedbackDetails as $groupName => $feedbackIdRaw) {
                    $feedbackId = (int)$feedbackIdRaw;

                    // fetch totalResponses from preloaded map (default 0)
                    $totalResponses = isset($this->preloadedTotalResponses[$feedbackId]) ? (int)$this->preloadedTotalResponses[$feedbackId] : 0;

                    // get preloaded questions (array) for this feedbackId
                    $feedbackQuestionsArr = isset($this->preloadedQuestions[$feedbackId]) ? $this->preloadedQuestions[$feedbackId] : [];

                    // preserve expected structure
                    $this->courseAverage[$facultyId][$courseId][$feedbackId] = [];

                    if (!empty($feedbackQuestionsArr)) {
                        // call setAveragePerQuestion - it will use $this->preloadedAnswers internally
                        $this->setAveragePerQuestion($conn, $prefix, $feedbackQuestionsArr, $totalResponses, $this->courseAverage[$facultyId][$courseId][$feedbackId], $groupName);
                        // ensure weightage exists before adding
                        $sumOfFeedbackWeightage += isset($this->courseAverage[$facultyId][$courseId][$feedbackId]['weightage']) ? $this->courseAverage[$facultyId][$courseId][$feedbackId]['weightage'] : 0;
                    }
                }

                // compute facultyCourseWeightage — avoid division by zero
                $numFeedbacks = count($feedbackDetails);
                if ($numFeedbacks > 0) {
                    $this->courseAverage[$facultyId][$courseId]['facultyCourseWeightage'] = number_format($sumOfFeedbackWeightage / $numFeedbacks, 2);
                } else {
                    $this->courseAverage[$facultyId][$courseId]['facultyCourseWeightage'] = number_format(0, 2);
                }
                $sumofFacultyWeightage += (float)$this->courseAverage[$facultyId][$courseId]['facultyCourseWeightage'];
            }

            $numCourses = count($courseArray);
            if ($numCourses > 0) {
                $this->courseAverage[$facultyId]['facultyWeightage'] = number_format($sumofFacultyWeightage / $numCourses, 2);
            } else {
                $this->courseAverage[$facultyId]['facultyWeightage'] = number_format(0, 2);
            }
        }
    }

    /**
     * setAveragePerQuestion
     *
     * Note: $feedbackQuestions may be either:
     *  - a mysqli_result (legacy), OR
     *  - an array of question rows (id, question, options) as used by this optimized class.
     *
     * This function uses $this->preloadedAnswers (populated in constructor) to avoid DB queries.
     *
     * Parameters left as in original for compatibility.
     */
    public function setAveragePerQuestion($conn, $prefix, $feedbackQuestions, $totalResponses, &$courseFacultyFeedbackDetails, $groupName) {

        $feedbackQuestionsWeitage = [10, 5, 15, 15, 10, 10, 10, 10, 5, 5, 5];
        $sr_no = 0;

        $courseFacultyFeedbackDetails['weightage'] = 0;

        // Normalize $feedbackQuestions into an iterable array of question rows:
        $questionsList = [];

        if ($feedbackQuestions instanceof mysqli_result) {
            // backward compatibility: convert result to array
            while ($row = mysqli_fetch_assoc($feedbackQuestions)) {
                $questionsList[] = [
                    'id' => (int)$row['id'],
                    'question' => $row['question'],
                    'options' => $row['options']
                ];
            }
        } elseif (is_array($feedbackQuestions)) {
            $questionsList = $feedbackQuestions;
        } else {
            // nothing to do
            $questionsList = [];
        }

        foreach ($questionsList as $question) {
            // Skip if question structure unexpected
            if (!isset($question['id']) || !isset($question['question'])) continue;

            // Skipping Value Add Question As per the requirements
            if (strpos($question['question'], "Value Add:") === false) {

                $answerRatingSum = 0;

                // Use preloaded answers for this item id (array of string values "1".."5")
                $itemId = (int)$question['id'];
                $valuesArr = isset($this->preloadedAnswers[$itemId]) ? $this->preloadedAnswers[$itemId] : [];

                // Prepare counters for converted feedbackValues
                $feedbackValues = ["1" => 0, "2" => 0, "3" => 0, "4" => 0, "5" => 0];
                $totalAnswers = 0;

                if (!empty($valuesArr)) {
                    // Convert each raw answer to your weighted numeric system (same as before)
                    foreach ($valuesArr as $val) {
                        switch ((string)$val) {
                            case "1":
                                $answerRatingSum += 5;
                                $feedbackValues["5"]++;
                                $totalAnswers++;
                                break;
                            case "2":
                                $answerRatingSum += 4;
                                $feedbackValues["4"]++;
                                $totalAnswers++;
                                break;
                            case "3":
                                $answerRatingSum += 3;
                                $feedbackValues["3"]++;
                                $totalAnswers++;
                                break;
                            case "4":
                                $answerRatingSum += 2;
                                $feedbackValues["2"]++;
                                $totalAnswers++;
                                break;
                            case "5":
                                $answerRatingSum += 1;
                                $feedbackValues["1"]++;
                                $totalAnswers++;
                                break;
                            default:
                                // ignore unexpected values
                                break;
                        }
                    }
                }

                $statistics = [];
                if ($totalResponses > 0) {
                    $count = 0;
                    $half = $totalResponses / 2;
                    foreach ($feedbackValues as $key => $value) {
                        $count += $value;

                        if (!isset($statistics['median']) && $count >= $half) {
                            if ($totalAnswers % 2 != 0) {
                                $statistics['median'] = $key;
                            } else {
                                if ($count == $half) {
                                    $statistics['median'] = (int)(((int)$key + ((int)$key + 1)) / 2);
                                } else {
                                    $statistics['median'] = (int)$key;
                                }
                            }
                        }
                    }

                    // Avoid division by zero just in case
                    if ($totalResponses > 0) {
                        $statistics['mean'] = number_format(($answerRatingSum / $totalResponses), 2);
                    } else {
                        $statistics['mean'] = number_format(0, 2);
                    }

                    // Weighted score for this question
                    $weightVal = isset($feedbackQuestionsWeitage[$sr_no]) ? $feedbackQuestionsWeitage[$sr_no] : 0;
                    $statistics['weightedScore'] = number_format(($weightVal * $statistics['mean']) / 100, 2);

                    // standard deviation computation
                    $score = 1;
                    $sumOfScores = 0;
                    while ($score <= 5) {
                        $ans = "0";
                        switch ((string)$score) {
                            case "1":
                                $ans = "5";
                                break;
                            case "2":
                                $ans = "4";
                                break;
                            case "3":
                                $ans = "3";
                                break;
                            case "4":
                                $ans = "2";
                                break;
                            case "5":
                                $ans = "1";
                                break;
                        }
                        $meanVal = (float)$statistics['mean'];
                        $sumOfScores += $feedbackValues[$ans] * (($score - $meanVal) * ($score - $meanVal));
                        $score++;
                    }

                    if ($totalResponses > 1) {
                        $statistics['deviation'] = sqrt(($sumOfScores / ($totalResponses - 1)));
                    } else {
                        // when totalResponses is 0 or 1, fall back to division by totalResponses (if 0, deviation = 0)
                        if ($totalResponses > 0) {
                            $statistics['deviation'] = sqrt(($sumOfScores / $totalResponses));
                        } else {
                            $statistics['deviation'] = 0;
                        }
                    }
                } else {
                    // default values when no responses
                    $statistics['mean'] = 0;
                    $statistics['weightedScore'] = 0;
                    $statistics['deviation'] = 0;
                    $statistics['median'] = 0;
                }

                // set weight for this question (safe fallback)
                $statistics['weightage'] = isset($feedbackQuestionsWeitage[$sr_no]) ? $feedbackQuestionsWeitage[$sr_no] : 0;

                // assign stats to courseFacultyFeedbackDetails as original code did
                $courseFacultyFeedbackDetails[$question['id']] = $statistics;
                $courseFacultyFeedbackDetails['weightage'] += $statistics['weightedScore'];
                $courseFacultyFeedbackDetails['groupName'] = $groupName;

                $sr_no++;
            } // end skip Value Add
        } // end foreach question
    }

    public function getFeedbackQuestionsAverage() {
        return $this->courseAverage;
    }

}
?>