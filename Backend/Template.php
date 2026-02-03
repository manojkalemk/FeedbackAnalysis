<?php
include "../db.php";

class Template {

    private $feedbackTemplateList = [];
    private $feedbackTemplateCourseId;
    public $academicYearList = [];
    public $selectedAcademicYear;

    function __construct($conn, $prefix) {
        
        $this->selectedAcademicYear = $_SESSION['selectedAcademicYear'] ?? '2024 - 2025';
        
        $result = $conn->query("SELECT id, fullname from {$prefix}course where fullname like '%template%'");

        if ($result->num_rows <= 0) {
            throw new Exception("Error : In Template.php file : Feedback Template Course Not Found in Course Template Category");
        }
        // Getting Feedback Template Course by filtering the name, Removing space and to lowercase
        // It is mandatory to have course name like 'Feedback Template';
        while($courseNames = mysqli_fetch_assoc($result)) {
            if(strpos(strtolower(str_replace(' ' , '' , $courseNames['fullname'])), "feedbacktemplate") !== false && strpos(strtolower(str_replace(' ' , '' , $courseNames['fullname'])), "old") == false) {
                $this->feedbackTemplateCourseId = $courseNames['id'];
            }
        }
        //$this -> academicYearList = $this -> getAcademicYear();
    }

    public function getFeedbackTemplateList($conn, $prefix) {

        $feedback = $conn ->query("SELECT id, name from {$prefix}feedback where course = {$this->feedbackTemplateCourseId}");
        if($feedback->num_rows<=0) {
            throw new Exception("Error : In Template.php file : Feedback Template Course ID Not found");
        }
        if($feedback -> num_rows > 0) {
            while($list = mysqli_fetch_assoc($feedback)) {
                $this->feedbackTemplateList[$list['id']] = $list['name'];
            }
        } 
        
        if($debugging) {
            echo "<pre> Feedback Template LIst : ";
            print_r($this->feedbackTemplateList);
            echo "</pre><br>";
        }
        
        return $this->feedbackTemplateList;
    }

    // public function getAcademicYear() {
    //     $currentYear = date('Y');
    //     $startYear = "2022";
        
    //     // $academicYear = "2024 - 2025";
    //     // $this->academicYearList[] = $academicYear;

    //     while ($startYear <= ($currentYear)) { 
    //         $academicYear = $startYear . " - " . ($startYear+1) ;
    //         $this->academicYearList[] = $academicYear;
    //         $startYear ++;
    //     }
    //     return $this->academicYearList;
    // }
    
    public function getAcademicYear() {
        // Always return only ONE selected year
        $this->academicYearList = [$this->selectedAcademicYear];
    
        return $this->academicYearList;
    }

    public function getColor($average) {
        if($average >= 4.0) {
            $color = "#7ff67f";
        } else if($average < 4.0 && $average >= 2.5) {
            $color = "#ffff009e";
        } else{
            $color = "#ff7676";
        }

        return $color;
    }
} 


include "FacultyData.php";
include "FeedbackAvailability.php";
include "CalculateAverage.php";


$temp = new Template($conn, $prefix);
$academicYearList = $temp-> getAcademicYear();


try {
    $feedbackAvailability = new FeedbackAvailability($conn, $prefix, $academicYearList, $type);
    $facultyFeedbackDetails = $feedbackAvailability -> getFacultyFeedbackList();
    //$validCourses = $feedbackAvailability -> getValidCourses();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
$facultyAndCourseWiseAverage = new CalculateAverage($conn, $prefix, $facultyFeedbackDetails);

// Contains Faculty -> courses > feedback Question statistics
$facultyCourseWiseAverageArray = $facultyAndCourseWiseAverage -> getFeedbackQuestionsAverage();
// echo "<pre>";
// print_r($facultyCourseWiseAverageArray);
// echo "</pre>";
$facultyRankingAnalysis = [
    "HSF" => 0,
    "LSF" => 10,
    "A4.5" => 0,    
    "B4.0TO4.5" => 0,
    "B3.5TO4" => 0,
    "B3.5" => 0
];

$courseFacultyAverages = [];
$facultyWithAverage = [];
$highestScoringFacultyId = 0;
$lowestScoringFacultyId = 0;
$highestScoringCourseId = 0;
$lowestScoringCourseId = 0;
$feedbackInCourse = [];

$enrolledStudentQuery = "SELECT COUNT(DISTINCT c.userid) as enrolledStudent 
        FROM {$prefix}course a 
        JOIN {$prefix}enrol b ON b.courseid = a.id
        JOIN {$prefix}user_enrolments c ON c.enrolid = b.id
        JOIN {$prefix}role_assignments d ON d.userid = c.userid
        JOIN {$prefix}user u ON u.id = d.userid
        WHERE b.courseid = ? 
        AND d.roleid = 5 
        AND u.username NOT LIKE 't%' 
        AND u.username NOT LIKE '%vf%' 
        AND u.username NOT LIKE '@'";
$preparedQueryEnrolledStudent = $conn->prepare($enrolledStudentQuery);

$feedbackModuleid = $conn -> query("SELECT id from {$prefix}modules where name='feedback'");
if($feedbackModuleid->num_rows<=0) {
    throw new Exception("In FeedbackAvailabilty.php : Feedback Module Not Found");
}
$feedbackModuleid = $feedbackModuleid -> fetch_assoc()['id'];

$feedAvailInCourse = "SELECT f.id as feedbackId from {$prefix}feedback f 
                join {$prefix}course_modules cm on cm.instance = f.id 
                where cm.deletioninprogress = 0 
                and cm.module = $feedbackModuleid 
                and f.course = ? 
                and (f.name like '%Student%' and f.name like '%Feedback%' and f.name like '%Faculty%')
                and f.name not like '%$type%'";

$preparedQueryFeedback = $conn->prepare($feedAvailInCourse);

// echo $feedAvailInCourse;


foreach($facultyCourseWiseAverageArray as $facultyId => $enrolledCourseDetails) {
    foreach($enrolledCourseDetails as $courseId => $availFeedbackDetails) {
        // As per the Array Structure
        if(isset($availFeedbackDetails['facultyCourseWeightage']) && $courseId !== 'facultyWeightage') {

            // Filtering Faculty Average in Course
            $courseFacultyAverages[$courseId]['faculty'][$facultyId] = $availFeedbackDetails['facultyCourseWeightage'];

            // Filtering Faculty Average for a Course
            $courseFacultyAverages[$courseId]['courseWeightage'] = number_format(array_sum($courseFacultyAverages[$courseId]['faculty']) / count($courseFacultyAverages[$courseId]['faculty']), 2);
       	    
	   // Collecting Feedback Available in Course, Enrolled Student to Course, Sum of Total responses per feedback in current course
            if(!isset($feedbackInCourse[$courseId])) {
                $preparedQueryEnrolledStudent->bind_param("i", $courseId);
                $preparedQueryEnrolledStudent->execute();
                $result = $preparedQueryEnrolledStudent->get_result();
                if($result -> num_rows > 0) {
                    $feedbackInCourse[$courseId]['enrolledStudent'] = $result->fetch_assoc()['enrolledStudent'];
                } else{
                    $feedbackInCourse[$courseId]['enrolledStudent'] = 0;
                }

                $preparedQueryFeedback->bind_param("i", $courseId);
                $preparedQueryFeedback->execute();
                $feedackResult = $preparedQueryFeedback->get_result();
                if($feedackResult -> num_rows > 0) {
                    $totalResponses = 0;
                    $feedbackCount=0;
                    while($row = mysqli_fetch_assoc($feedackResult)) {
                        $feedbackCount++;
                        $totalResponses += $conn->query("SELECT count(feedback) as totalResponses from {$prefix}feedback_completed where feedback = {$row['feedbackId']}")->fetch_assoc()['totalResponses'];
                    }
                    $feedbackInCourse[$courseId]['totalStudentFeedback'] = $feedbackCount;
                    $feedbackInCourse[$courseId]['totalResponses'] = $totalResponses;
                } else{
                    $feedbackInCourse[$courseId]['totalStudentFeedback'] = 0;
                    $feedbackInCourse[$courseId]['totalResponses'] = 0;
                }

            }
        }
    }

    // Calculating Highest Scoring Faculty and Lowest Scoring Faculty
    $facultyWithAverage[$facultyId] = $enrolledCourseDetails['facultyWeightage'];
    if($facultyRankingAnalysis['HSF'] < $enrolledCourseDetails['facultyWeightage']) {
        $facultyRankingAnalysis['HSF'] = $enrolledCourseDetails['facultyWeightage'];
        $highestScoringFacultyId = $facultyId;
    } 

    if($enrolledCourseDetails['facultyWeightage'] > 0 && $facultyRankingAnalysis['LSF'] > $enrolledCourseDetails['facultyWeightage']) {
        $facultyRankingAnalysis['LSF'] = $enrolledCourseDetails['facultyWeightage'];
        $lowestScoringFacultyId = $facultyId;
    }

    if($enrolledCourseDetails['facultyWeightage'] >= 4.5) {
        $facultyRankingAnalysis['A4.5']++;
    }
    if($enrolledCourseDetails['facultyWeightage'] >= 4.0 && $enrolledCourseDetails['facultyWeightage'] < 4.5) {
        $facultyRankingAnalysis['B4.0TO4.5']++;
    }
    if($enrolledCourseDetails['facultyWeightage'] >= 3.5 && $enrolledCourseDetails['facultyWeightage'] < 4.0) {
        $facultyRankingAnalysis['B3.5TO4']++;
    }
    if($enrolledCourseDetails['facultyWeightage'] < 3.5) {
        $facultyRankingAnalysis['B3.5']++;
    }

}

// Getting Only Courses in Academic Year from FacultyData Class
$academicYearWithCourses = $feedbackAvailability -> getAcademicYearCourse();

// Note : Note: Updating academicYearWithCoures Array with courseFacultyAverageData
foreach($courseFacultyAverages as $courseId => $courseDetails) {
    foreach($academicYearWithCourses as $academicYear => &$courseList) {
        if(isset($courseList[$courseId]) && !is_array($courseList[$courseId]) ) {
            $courseList[$courseId] = $courseFacultyAverages[$courseId];
            break;
        }
    }
    unset($courseList);
}


include "ProgramWiseCourses.php";
$programCoursesObject = new ProgramWiseCourses($conn, $prefix, $academicYearWithCourses[$academicYearListSelect[0]]);
$programWiseCourse = $programCoursesObject -> getProgramWiseData();

include "InHouseVisitingData.php";


// Collecting Data in Session for in-detail Analysis of Courses and Faculty
$_SESSION['inHouseFacultyData'] = json_encode($inHouseFacultyData);
$_SESSION['visitingFacultyData'] = json_encode($visitingFacultyData);
$_SESSION['facultyWithAverage'] = json_encode($facultyWithAverage);
$_SESSION['facultyCourseWiseAverageArray'] = json_encode($facultyCourseWiseAverageArray);
$_SESSION['courseFacultyAverages'] = json_encode($courseFacultyAverages);
$_SESSION['categoryFacultyAverage'] = json_encode(["fulltime" => $fullTimeFacultyAverage, "visiting" => $vistingFacultyAverage]);
$_SESSION['feedbackInCourse'] = json_encode($feedbackInCourse);
// $_SESSION['selectedAcademicYear'] = json_encode($academicYearListSelect[0]);
$_SESSION['feedbackType'] = $type;

?>