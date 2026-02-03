<?php

class FeedbackAvailability extends FacultyData{
    private $enrolledCourses;
    private $groupsArray;
    private $facultyFeedbackList = [];

    // Those Courses have faculty enrolled, Groups Assigned and Student Faculty feedback Available.
    private $validCourses = [];

    function __construct($conn, $prefix, $academicYearList, $type) {
        
        // echo "<hr>FeedbackAvailability class starts from here ...<hr><br>";

        // Calling Parent Class Constructor: Template Class Constructor to find the Feedback Template Course
        parent::__construct($conn, $prefix, $academicYearList);

        // get feedback module id
        $feedbackModuleidRs = $conn->query("SELECT id FROM {$prefix}modules WHERE name='feedback'");
        if ($feedbackModuleidRs->num_rows <= 0) {
            throw new Exception("In FeedbackAvailability.php : Feedback Module Not Found");
        }
        $feedbackModuleid = $feedbackModuleidRs->fetch_assoc()['id'];

        // From FacultyData Class, Getting Faculty Enrolled Courses under the Selected Academic Year
        $this->enrolledCourses = $this->getCourseWithAcademicYear();

        // From FacultyData Class, Getting Faculty Enrolled Groups to the Courses
        $this->groupsArray = $this->getGroupsArray();

        //
        // ---- OPTIMIZATION A: CACHE FACULTY NAMES ----
        //
        // $facultyNameCache = [];
        // if (!empty($this->enrolledCourses) && is_array($this->enrolledCourses)) {
        //     foreach ($this->enrolledCourses as $year => $faculties) {
        //         if (!is_array($faculties)) continue;
        //         foreach ($faculties as $facultyId => $courses) {
        //             // normalize and cache faculty name once
        //             if (!isset($facultyNameCache[$facultyId])) {
        //                 $nameRow = $conn->query("SELECT CONCAT(firstname,' ',lastname) AS fullName FROM {$prefix}user WHERE id=" . intval($facultyId));
        //                 $fullName = '';
        //                 if ($nameRow && $nameRow->num_rows > 0) {
        //                     $fullName = $nameRow->fetch_assoc()['fullName'];
        //                 }
        //                 // store already-normalized name (lowercase + remove spaces) to avoid repeating
        //                 $facultyNameCache[$facultyId] = strtolower(str_replace(' ', '', (string)$fullName));
        //             }
        //         }
        //     }
        // }
        
        //
        // ---- OPTIMIZATION A (Improved): BULK FETCH FACULTY NAMES ----
        //
        
        // 1. Collect all faculty IDs in one flat array
        $allFacultyIds = [];
        
        foreach ($this->enrolledCourses as $year => $faculties) {
            if (!is_array($faculties)) continue;
            foreach ($faculties as $facultyId => $courses) {
                $allFacultyIds[$facultyId] = true; // use associative to avoid duplicates
            }
        }
        
        $facultyIds = array_keys($allFacultyIds);
        
        $facultyNameCache = [];
        
        if (!empty($facultyIds)) {
        
            // Convert ids to comma-separated string
            $idsList = implode(',', array_map('intval', $facultyIds));
            // echo "<br><pre> idsList : ";
            // print_r($idsList);
            // echo "</pre><br>";
        
            // 2. Fetch all names in one query
            $sql = "
                SELECT id, CONCAT(firstname,' ',lastname) AS fullName
                FROM {$prefix}user
                WHERE id IN ($idsList)
            ";
            // echo "<br>SELECT id, CONCAT(firstname,' ',lastname) AS fullName
            //     FROM {$prefix}user
            //     WHERE id IN ($idsList)<br>";
            $result = $conn->query($sql);
        
            // 3. Build cache
            while ($row = $result->fetch_assoc()) {
                $facultyNameCache[$row['id']] = strtolower(
                    str_replace(' ', '', $row['fullName'])
                );
            }
        }

        
        // echo "<br><pre>facultyNameCache : ";
        // print_r($facultyNameCache);
        // echo "</pre><br>";
        

        //
        // ---- OPTIMIZATION B: PRELOAD ALL FEEDBACKS (grouped by course) IN ONE QUERY ----
        //
        $feedbackCache = [];
        $feedbackSql = "
            SELECT f.id AS feedbackId, f.name AS feedbackName, f.course AS courseId
            FROM {$prefix}feedback f
            JOIN {$prefix}course_modules cm ON cm.instance = f.id
            WHERE cm.deletioninprogress = 0 AND (f.name like '%Student%' AND f.name like '%Feedback%' AND f.name like '%Faculty%') AND cm.module = " . intval($feedbackModuleid) . "
        ";
        $allFeedbackRs = $conn->query($feedbackSql);
        if ($allFeedbackRs && $allFeedbackRs->num_rows > 0) {
            while ($row = mysqli_fetch_assoc($allFeedbackRs)) {
                $cid = (int)$row['courseId'];
                $feedbackCache[$cid][] = $row;
            }
        }
        
        // echo "<br><pre> feedbackCache : "; 
        // print_r($feedbackCache);
        // echo"</pre><br>";

        //
        // ---- MAIN LOOP: use caches instead of DB calls inside nested loops ----
        //
        
        // echo "Faculty array : ";
        // print_r($this->enrolledCourses);
        
        foreach ($this->enrolledCourses as $academicYear => $facultyArray) {
            if (!is_array($facultyArray)) continue;
            foreach ($facultyArray as $facultyId => $courseArray) {
                if (!is_array($courseArray)) continue;

                // load normalized faculty name from cache (fallback to empty string)
                $facultyNameNormalized = isset($facultyNameCache[$facultyId]) ? $facultyNameCache[$facultyId] : '';

                foreach ($courseArray as $courseid => $courseName) {
                    $courseid = (int)$courseid;

                    // if no feedbacks for this course, skip
                    if (!isset($feedbackCache[$courseid]) || !is_array($feedbackCache[$courseid])) {
                        continue;
                    }

                    // iterate preloaded feedbacks for the course
                    foreach ($feedbackCache[$courseid] as $feedback) {
                        // normalize feedback name in the same way as previous code
                        $feedbackName = strtolower(str_replace(' ', '', $feedback['feedbackName']));

                        // Checking Group Available to Course and the faculty
                        if (isset($this->groupsArray[$facultyId][$courseid]) && is_array($this->groupsArray[$facultyId][$courseid])) {
                            foreach ($this->groupsArray[$facultyId][$courseid] as $groupName) {
                                $groupNameNormalized = strtolower(str_replace(' ', '', $groupName));
                                
                                //NEW CODE -> because some feedbacks are not visible like this : Student Feedback- Faculty ( : FACLUTY NAME) - FINAL : because this is not contains the group names.
                                $groupMatch = $this->isGroupMatched($feedback['feedbackName'], $groupNameNormalized);
                                if (strpos($feedbackName, $facultyNameNormalized) !== false
                                    && strpos($feedbackName, 'studentfeedback-faculty') !== false
                                    && strpos($feedbackName, $type) === false
                                ) {
                                    // CASE 1: Group exists and matches
                                    if ($groupMatch === true) {
                                        $this->facultyFeedbackList[$facultyId][$courseid][$groupNameNormalized] = $feedback['feedbackId'];
                                    }
                                
                                    // CASE 2: Feedback has NO group → treat as "No Group"
                                    elseif ($groupMatch === null) {
                                        $this->facultyFeedbackList[$facultyId][$courseid]['No Group'] = $feedback['feedbackId'];
                                    }
                                }

                                // isGroupMatched searches exact group Name present in feedback name
                                // OLD CODE 
                                // if (strpos($feedbackName, $facultyNameNormalized) !== false
                                //     && $this->isGroupMatched($feedback['feedbackName'], $groupNameNormalized) !== false
                                //     && strpos($feedbackName, 'studentfeedback-faculty') !== false
                                //     && strpos($feedbackName, $type) === false) {

                                //     $this->facultyFeedbackList[$facultyId][$courseid][$groupNameNormalized] = $feedback['feedbackId'];
                                // }
                                
                            }
                        } else {
                            // No groups assigned for this faculty/course: keep 'No Group' as earlier
                            if (strpos($feedbackName, $facultyNameNormalized) !== false
                                && strpos($feedbackName, 'studentfeedback-faculty') !== false
                                && strpos($feedbackName, $type) === false) {

                                $this->facultyFeedbackList[$facultyId][$courseid]['No Group'] = $feedback['feedbackId'];
                            }
                        }

                        // mark this course as valid (at least one feedback exists for the course)
                        if (!isset($this->validCourses[$courseid])) {
                            $this->validCourses[$courseid] = $courseid;
                        }
                    } // end foreach feedback
                } // end foreach course
            } // end foreach faculty
        } // end foreach academicYear
    } // end constructor

    function getFacultyFeedbackList() {
        return $this->facultyFeedbackList;
    }

    function getValidCourses() {
        return $this->validCourses;
    }

    // Group Name Always be present in between like : Student Feedback- Faculty ( groupName : Faculty Name)
    // Note: Accepts the original feedback string (un-normalized) to extract the group text using regex.
    
    
    //New Code 
    function isGroupMatched($string, $sectionName) {
        // Extract text inside ( ... :)
        $pattern = '/\((.*?)\s*:/';
        preg_match($pattern, $string, $matches);
    
        // CASE 1: Feedback HAS group
        if (!empty($matches[1])) {
            $groupName = trim($matches[1]);
            $groupNameNormalized = strtolower(str_replace(' ', '', $groupName));
            return $groupNameNormalized === $sectionName;
        } else {
            return null;
        }
    
        // CASE 2: Feedback has NO group at all
        return null; // important: distinguish from false
    }
    
    // OLD Code 
    // function isGroupMatched($string, $sectionName) {
    //     $pattern = '/\((.*?)\s*:/';

    //     // Trying to get Group Name between ( : )
    //     preg_match($pattern, $string, $matches);

    //     if (!empty($matches[1])) {
    //         $groupName = trim($matches[1]);

    //         // sectionName passed to this function is expected in normalized form (lowercase, no spaces).
    //         // Normalize extracted groupName similarly before comparison.
    //         $groupNameNormalized = strtolower(str_replace(' ', '', $groupName));

    //         return $groupNameNormalized === $sectionName;
    //     }

    //     return false;
    // }
    
    
    
}

?>