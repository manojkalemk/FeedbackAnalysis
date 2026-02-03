<?php
// Getting Groups Assigned to the Faculty in a Enrolled Course
$groupsArray = $feedbackAvailability -> getGroupsArray();

// Getting Faculty Enrolled Courses in an Selected Academic Year from FacultyData class
$academicYearWithCoursesWithFaculty = $feedbackAvailability -> getCourseWithAcademicYear();

$inHouseFacultyData = [];
$visitingFacultyData = [];
$fullTimeFacultyAverage = 0.0;
$vistingFacultyAverage = 0.0;
foreach($academicYearWithCoursesWithFaculty[$academicYearListSelect[0]] as $facultyId => $enrolledCourses) {

    $facultyQuery = $conn->query("SELECT username as EmployeeCode, concat(firstname, ' ', lastname) FullName from {$prefix}user where id = $facultyId");

    $details = $facultyQuery->fetch_assoc();
    $employeeCode = $details['EmployeeCode'];
    $employeeName = $details['FullName'];

    if(is_array($enrolledCourses)) {

        if(strpos($employeeCode, 't') !== false && strpos($employeeCode, '@') == false && strpos($employeeCode, '.') == false && strpos($employeeCode, 'vf') == false && strpos($employeeCode, '_') == false) {
            $inHouseFacultyData[$facultyId]['employeeCode'] = $employeeCode;
            $inHouseFacultyData[$facultyId]['fullName'] = $employeeName;
    
            foreach($enrolledCourses as $courseId => $courseName) {
                if(isset($groupsArray[$facultyId][$courseId])) {
                    if(is_array($groupsArray[$facultyId][$courseId])) {
                        foreach($groupsArray[$facultyId][$courseId] as $index => $groupName) {
                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId])) {
                                foreach($facultyCourseWiseAverageArray[$facultyId][$courseId] as $feedbackId => $feedbackDetails)  {
                                    if(isset($feedbackDetails['groupName']) && strtolower(str_replace(' ', '', $groupName)) === strtolower(str_replace(' ', '', $feedbackDetails['groupName']))) {
                                        $inHouseFacultyData[$facultyId]['courses'][$courseId][$groupName] = $feedbackDetails['weightage'];
                                    }   
                                }
                            } else{
                                $inHouseFacultyData[$facultyId]['courses'][$courseId][$groupName] = 'No Feedback Available';
                                
                            }
                        }
                    } else{
                        if($groupsArray[$facultyId][$courseId] == 'NA') {
                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId])) {
                                foreach($facultyCourseWiseAverageArray[$facultyId][$courseId] as $feedbackId => $feedbackDetails)  {
                                    if(isset($feedbackDetails['groupName'])) {
                                        $inHouseFacultyData[$facultyId]['courses'][$courseId] = $feedbackDetails['weightage'];
                                    }   
                                }
                            } else{
                                $inHouseFacultyData[$facultyId]['courses'][$courseId] = 'NA';
                            }
                        } else if($groupsArray[$facultyId][$courseId] == 'NGA') {
                            $inHouseFacultyData[$facultyId]['courses'][$courseId] = 'NGA';
                        }
                    }
                } 
            }
            if(isset($facultyWithAverage[$facultyId])) {
                $fullTimeFacultyAverage += $facultyWithAverage[$facultyId];
            }
        } else{
            $visitingFacultyData[$facultyId]['employeeCode'] = $employeeCode;
            $visitingFacultyData[$facultyId]['fullName'] = $employeeName;
    
            foreach($enrolledCourses as $courseId => $courseName) {
                if(isset($groupsArray[$facultyId][$courseId])) {
                    if(is_array($groupsArray[$facultyId][$courseId])) {
                        foreach($groupsArray[$facultyId][$courseId] as $groupName) {
                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId])) {
                                foreach($facultyCourseWiseAverageArray[$facultyId][$courseId] as $feedbackId => $feedbackDetails)  {
                                    if(isset($feedbackDetails['groupName']) && strtolower(str_replace(' ', '', $groupName)) === strtolower(str_replace(' ', '', $feedbackDetails['groupName']))) {
                                        $visitingFacultyData[$facultyId]['courses'][$courseId][$groupName] = $feedbackDetails['weightage'];
                                    }
                                }
                            } else{
                                $visitingFacultyData[$facultyId]['courses'][$courseId][$groupName] = 'No Feedback Available';
                            }
                        }
                    } else{
                        if($groupsArray[$facultyId][$courseId] == 'NA') {
                            if(isset($facultyCourseWiseAverageArray[$facultyId][$courseId])) {
                                foreach($facultyCourseWiseAverageArray[$facultyId][$courseId] as $feedbackId => $feedbackDetails)  {
                                    if(isset($feedbackDetails['groupName'])) {
                                        $visitingFacultyData[$facultyId]['courses'][$courseId] = $feedbackDetails['weightage'];
                                    }   
                                }
                            } else{
                                $visitingFacultyData[$facultyId]['courses'][$courseId] = 'NA';
                            }
                        } else if($groupsArray[$facultyId][$courseId] == 'NGA') {
                            $visitingFacultyData[$facultyId]['courses'][$courseId] = 'NGA';
                        }
                    }
                } 
            }
            if(isset($facultyWithAverage[$facultyId])) {
                $vistingFacultyAverage += $facultyWithAverage[$facultyId];
            }
        }
    }

}

?>