<?php
    class FacultyData extends Template {
        private $facultyEnrolledCourses = [];
        private $groupsArray = [];
        public $academicYearList;
        private $courseList = [];
        private $coursesInAcdemicYear = [];
        // private $debugging = true;

        function __construct($conn, $prefix, $academicYearList) {

            // Calling Parent Class Construtor: Template Class Construtor to find the Feedback Template Course
            parent::__construct($conn, $prefix);

            // From Child Class FeedbackAvailability
            $this -> academicYearList = $academicYearList;

            // Collecting Courses coming under the Academic Year
            $this -> setCourseWithAcademicYear($conn, $prefix);
        }

        public function getCourseWithAcademicYear() {
            return $this -> facultyEnrolledCourses;
        }
        
        public function getGroupsArray() {
            return $this -> groupsArray;
        }

        public function getAcademicYearCourse() {
            return $this -> coursesInAcdemicYear;
        }
        
        public function setCourseWithAcademicYear($conn, $prefix) {
            
            // echo "<pre> FacultyData :  academicYear : ";
            // print_r($this -> academicYearList);
            // echo "</pre>";
            
            foreach($this -> academicYearList as $academicYear) {
                $filterYear = explode(" - ", $academicYear);

                $startYear = $filterYear[0];
                $endYear = $filterYear[1];
                // $resultSet = $conn->query("SELECT id, fullname from {$prefix}course where id != 1563 and id != 1912 and fullname NOT LIKE '%Vasudhaiva%' and startdate >= unix_timestamp('$startYear-06-01') and enddate <= unix_timestamp('$endYear-05-31')");
                
                // $resultSet = $conn->query("SELECT 
                //                             c.id AS id,
                //                             c.fullname AS fullname
                //                         FROM scmc_course c
                //                         JOIN scmc_feedback f
                //                             ON f.course = c.id
                //                         WHERE
                //                             (
                //                                 c.startdate <= UNIX_TIMESTAMP('$endYear-05-31')
                //                                 AND c.enddate >= UNIX_TIMESTAMP('$startYear-06-01')
                //                             )
                //                             OR
                //                             (
                //                                 f.timeopen <= UNIX_TIMESTAMP('$endYear-05-31')
                //                                 AND f.timeclose >= UNIX_TIMESTAMP('$startYear-06-01')
                //                             )
                //                         GROUP BY c.id, c.fullname
                //                         ORDER BY c.fullname;
                // ");
                
                $resultSet = $conn->query("SELECT 
                                            c.id AS id,
                                            c.fullname AS fullname
                                        FROM {$prefix}course c
                                        JOIN {$prefix}feedback f
                                            ON f.course = c.id
                                        WHERE
                                            (
                                                c.startdate <= UNIX_TIMESTAMP('$endYear-05-31 23:59:59')
                                                AND c.enddate >= UNIX_TIMESTAMP('$startYear-06-01 00:00:00')
                                            )
                                        GROUP BY c.id, c.fullname
                                        ORDER BY c.fullname;
                ");
                
                
                // echo "<br><pre>SELECT id, fullname from {$prefix}course where id != 1563 and id != 1912 and fullname NOT LIKE '%Vasudhaiva%' and startdate >= unix_timestamp('$startYear-06-01') and enddate <= unix_timestamp('$endYear-05-31') </pre><br>";
                
                if($resultSet -> num_rows > 0) {
                    
                    while($courses = mysqli_fetch_assoc($resultSet)) {

                        // Enrolled Faculty to the Courses
                        $enrolledFaculty = $conn->query("SELECT ra.roleid, ra.userid as userid, cc.id as courseid, cc.fullname from {$prefix}role_assignments ra 
                            join {$prefix}context con on con.id = ra.contextid
                            join {$prefix}course cc on cc.id = con.instanceid
                            where con.contextlevel = 50 and ra.roleid = 3 and cc.id={$courses['id']}");
                        
                        // echo "<pre>
                        //     enrolledFaculty : 
                        //     SELECT ra.roleid, ra.userid as userid, cc.id as courseid, cc.fullname from {$prefix}role_assignments ra 
                        //         join {$prefix}context con on con.id = ra.contextid
                        //         join {$prefix}course cc on cc.id = con.instanceid
                        //         where con.contextlevel = 50 and ra.roleid = 3 and cc.id={$courses['id']}
                        // </pre>";
                        
                        if($enrolledFaculty -> num_rows > 0) {
                            while($faculty = mysqli_fetch_assoc($enrolledFaculty)) {
                                $this -> facultyEnrolledCourses[$academicYear][$faculty['userid']][$faculty['courseid']] = $faculty['fullname'];

                                // To Avoid Dublicates Courseids in an Academic Year for different Faculties
                                if(!isset($this -> coursesInAcdemicYear[$academicYear][$courses['id']])) {
                                    $this -> coursesInAcdemicYear[$academicYear][$courses['id']] = $courses['fullname'];
                                }

                                // To Avoid Dublicate UserId in groups Array, Creating Group Array for each Faculty
                                if(!isset($this -> groupsArray[$faculty['userid']])) {
                                    $this -> groupsArray[$faculty['userid']] = [];
                                }

                                // Setting Groups Assigned to the Faculty in a groups Array, 
                                // Note: Groups Array Working as a Reference, while modifying in a below function.
                                $this -> setFacultyGroups($conn, $prefix, $faculty['userid'], $faculty['courseid'], $this -> groupsArray[$faculty['userid']]);
                            }
                        } else{
                            // $this -> facultyEnrolledCourses[$academicYear][$courses['id']] = "No Faculty Enrolled to course"; 
                            // if(!isset($this -> coursesInAcdemicYear[$academicYear][$courses['id']])) {
                            //     $this -> coursesInAcdemicYear[$academicYear][$courses['id']] = 'NFE';
                            // }
                            
                            // Skip storing courses with no faculty
                            error_log("NFE Course Skipped: {$courses['id']} - {$courses['fullname']}");
                            continue;
                            
                        }
                    }
                }             
            }
            
            // echo "<pre> facultyEnrolledCourses : ";
            // print_r($this -> facultyEnrolledCourses);
            // echo "<br>coursesInAcdemicYear : ";
            // print_r($this -> coursesInAcdemicYear);
            // echo "<br> groupsArray : ";
            // print_r($this -> groupsArray);
            // echo "</pre><br>";
            // exit;
        }

        public function setFacultyGroups($conn, $prefix, $facultyId, $courseId, &$groupsArray) {

            $isGroupAvailable = $conn -> query("SELECT id, name from {$prefix}groups where courseid=$courseId");
            if($isGroupAvailable -> num_rows > 0) {
                $groupQuery = $conn -> query("SELECT g.name as gname, g.id as id
                                FROM {$prefix}groups_members as gm
                                join {$prefix}groups as g on gm.groupid = g.id
                                where gm.userid=$facultyId and g.courseid=$courseId
                                group by g.id");
                                
                // echo "<pre> Group Query : SELECT g.name as gname, g.id as id
                //                 FROM {$prefix}groups_members as gm
                //                 join {$prefix}groups as g on gm.groupid = g.id
                //                 where gm.userid=$facultyId and g.courseid=$courseId
                //                 group by g.id </pre><br>";
                
                // To Avoid Duplicate Values of CourseId For Faculty
                if(!isset($this -> groupsArray[$facultyId][$courseId])) {
                    if($groupQuery -> num_rows > 0) {
                        while($facultyGroups = mysqli_fetch_assoc($groupQuery)) {
                            $groupsArray[$courseId][] = $facultyGroups['gname'];
                        }
                    } else{
                        // $groupsArray[$courseId] = 'NGA';
                        error_log("Faculty $facultyId has no group assigned in course $courseId (NGA Skipped)");
                        return;
                    }
                }
            } else{
                // $groupsArray[$courseId] = 'NA'; 
                error_log("Course has no groups table (NA Skipped): $courseId");
                return;
            }
            
            // echo "<pre> Group Query Array : ";
            // print_r($groupsArray);
            // echo "</pre><br>";
        }

    }
    // echo "<br> <hr> FacultyData class ends here. <hr>";
    // exit;
?>