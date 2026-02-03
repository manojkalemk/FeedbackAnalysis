<<<<<<< HEAD
# FeedbackAnalysis
=======
Program wise Analysis : 
Calculated Average of those course that should Teaching faculty is enrolled, If group available then that specific should group should be assigned.
Last, Student Feedback - Faculty Feedback Should be present with its faculty name and groups if available.'

Note : Consider Courses coming from the selected Academic Year, If any perticular course is not coming means, 
 - That course does not lies between academic year duration,
 - No faculty enrolled to the course,
 - No student feedback faculty feedback is present in a course


1) facultyCourseWiseAverageArray {
    the structure of the array is

    facultyId -> enrolledCourse (There can be multiple course) -> feedbackId(Student Feedback Faculty) -> question wise Weightage (Also includes groupName=>important part, to know which feedback Group is exactly)

    Inside the feedbackId array includes :  Current Feedback Weightage (By sum of all the question Weighted Score)

    Inside the enrolledCourse Array : You will get facultyCourseWeightage includes (Sum of Each feedback weightage from the feedbackId Array / Total no of feedback available in to the course)

    Inside the facultyId array : You will get FacultyWeightage key includes (Sum of facultyCourseWeightage of each enrolled course / Total no of courses)
}

2) GroupsArray : {

    The stucture of array is 
    facultyId => courseId => [
        (If course not having any group then 'NA') or 
        (If course have an group but not assigned to the faculty then 'NGA') 
        (IF course have an group and assiged to teaching faculty then) => [groupNames]
}

3) academicYearWithCoursesWithFaculty : {

    The structure of array is
    academic year from 2022 to the current year => [courseID => courseName]
}
>>>>>>> 843e05d (Initial commit)
