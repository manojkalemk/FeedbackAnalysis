<?php

class ProgramWiseCourses {
    private $courseInProgram = [];
    private $programWiseCourseDetails = [];
    

    function __construct($conn, $prefix, $coursesInAcademicYear) {

        // foreach($coursesInAcademicYear as $academicYear => $courseList) {
            foreach($coursesInAcademicYear as $courseId => $courseDetails) {
                $program = $conn->query("SELECT 
                cat1.id as pid,
                cat1.name AS PgmName,
                cat2.name AS Batch,
                cat3.name AS Semester,
                cat4.name AS Specialization,
                c.fullname AS Subject,
                c.id as c_id
                FROM  " . $prefix . "course_categories cat1
                LEFT JOIN  " . $prefix . "course_categories cat2 ON cat1.id = cat2.parent
                LEFT JOIN  " . $prefix . "course_categories cat3 ON cat2.id = cat3.parent
                LEFT JOIN  " . $prefix . "course_categories cat4 ON cat3.id = cat4.parent
                LEFT JOIN  " . $prefix . "course_categories cat5 ON cat4.id = cat5.parent
                LEFT JOIN  " . $prefix . "course c ON (c.category=cat4.id OR c.category=cat5.id OR c.category=cat1.id OR c.category=cat2.id OR c.category=cat3.id )
                WHERE cat1.parent = 0 and c.id is not null and c.id = $courseId AND cat1.name NOT IN ('Course Templates','VAC-HPM') ORDER BY c.fullname");

                while($row = mysqli_fetch_assoc($program)) {
                    $this -> programWiseCourseDetails[$row['PgmName']][$courseId] = $courseDetails;
                }

            }
        // }
    }

    function getProgramWiseData() {
        return $this->programWiseCourseDetails;
    }
}

?>