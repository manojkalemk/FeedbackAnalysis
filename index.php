<?php
    require_once "db.php";
    require_once ('../../config.php');
  
    require_login(0, true);
    if (isloggedin()) {
      
      $fullName = $USER->firstname . " " . $USER->lastname; 
      $loggedUserDesignation = strtolower($USER->profile['designation']);
      $accessDesignationArray = ['director', 'deputy director', 'dy director', 'head', 'feedback incharge'];
      if(!in_array($loggedUserDesignation, $accessDesignationArray)) {
          echo "Access denied. Please check your designation. Only users with the 'Director' role can proceed. Ensure the designation is spelled correctly and is not left blank.";
          exit;
        }
    } else {
        redirect($CFG->wwwroot . '/login/index.php');
    }
    $userId = $USER->id;
    $domain = $_SERVER['HTTP_HOST']; // Automatically gets the current domain
    setcookie("userId", $userId, time() + (86400 * 30), "/", $domain, isset($_SERVER['HTTPS']), true);
    

    $feedbackModuleid = $conn -> query("SELECT id from {$prefix}modules where name='feedback'");
    $feedbackModuleid = $feedbackModuleid -> fetch_assoc()['id'];


    $templateCourseId = $conn->query("SELECT id FROM {$prefix}course where fullname = 'Feedback Templates' or fullname = 'Feedback Template'");
    $feedbackList = [];
    if($templateCourseId -> num_rows > 0) {
        $templateCourseId = $templateCourseId -> fetch_assoc()['id'];

        $feedbackQuery = $conn->query("SELECT f.name as name, f.id as id from {$prefix}feedback f 
        join {$prefix}course_modules cm on cm.instance = f.id 
        where f.course = $templateCourseId and (name like '%Student Feedback- Faculty%' or name like '%Alumni%' or name like '%industry%') and cm.deletioninprogress = 0 and cm.module = $feedbackModuleid");

        while($feedback = $feedbackQuery -> fetch_assoc()) {
            $feedbackList[$feedback['id']] = $feedback['name'];
        }
    } else{
        echo "Warning: Feedback Template Course not found in Course Template Category";
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institute Feedback Analysis</title>
    <link rel="stylesheet" href="index.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="assets/logo.png" type="image/x-icon">
</head>
<body>
    <div class="view-container">
        <img src="assets/chat1.png" alt="loading" id='helpimg'>

        <div class="navbar">
            <img src="assets/symlogo.png" alt="Institute Logo" id="logo">
            <h2>Institute Feedback Analysis</h2>
            <p id="download"><?php echo $fullName; ?></p>
        </div>
    
        <div class="home">
            <div class="header">
                <h1>
                    <?php 
                    // Fetching the institute name from the database
                    $instituteName = $conn->query("SELECT fullname FROM {$prefix}course WHERE id=1")->fetch_assoc()['fullname'];
                    echo $instituteName;
                    ?>
                </h1>
                <p><strong>Welcome to the Symbiosis Feedback Analysis Portal</strong><br>
                A platform for streamlined feedback collection and analysis from students, faculty, alumni, and industry experts. Empowering stakeholders with actionable insights for continuous improvement.</p>
            </div>
    
            <div class="select-container">
                <!-- <label for="select-course">Select Feedback</label> -->
                <select id="feedbackSelect" class="custom-select">
                    <option value="option1">Select Feedback</option>
                    <?php
                    foreach($feedbackList as $id => $name) {
                        echo "<option value='$id'>$name</option>";
                    }
                    ?>
                
                </select>
                <select id="type" class="custom-select">
                    <option value="">Select Type</option>
                    <!-- <option value="mid">Mid</option> -->
                    <option value="final">Final</option>
                </select>
                
                <select id="yearSelect" class="custom-select">
                    <option value="">Select Academic Year</option>
                    <option value="2022 - 2023">2022 - 2023</option>
                    <option value="2023 - 2024">2023 - 2024</option>
                    <option value="2024 - 2025">2024 - 2025</option>
                    <option value="2025 - 2026">2025 - 2026</option>
                </select>
                
                <button id='analysis'>View Analysis</button>
            </div>
        </div>
        <!-- <div class="help">
            <h1>We'd Love to Hear From You! <img src='assets/delete.png' alt='loading' id='exit'></h1>
            <p>Please fill in the form below, and we'll get back to you as soon as possible.</p>
    
            <form>
                <input type="text" id="fullname" name="fullname" placeholder="Fullname" class='userdetail' required>
    
                <input type="email" id="email" name="email" placeholder="Email" class='userdetail' required>
    
                <input type="text" id="subject" name="subject" placeholder="Subject" class='userdetail' required>
    
                <label for="feedback-category">Feedback Category:</label>
                <div class="feedback-category">
                    <?php 
                    // foreach($feedbackList as $id => $name) {
                    //     echo "<div class='checkboxes'>
                    //                 <input type='checkbox' id='feedback' name='feedback-category' value='$id' required>
                    //                 <label for='$id'>$name</label>
                    //             </div>";
                    // }

                    ?>               
                </div>
    
                <textarea id="message" name="message" placeholder="Describe the issue or provide your feedback" required></textarea>
    
                <label for="attachment">Attach a file (optional):</label>
                <input type="file" id="attachment" name="attachment">

                

                <button id="submit">Send Feedback</button>
            </form>
        </div> -->
    </div>
    <script>
        // document.querySelector('.help').style.display = 'none';
        // document.getElementById('helpimg').addEventListener('click', function() {
        //     document.querySelector('.help').style.display = 'block';
        // });
        // document.getElementById('exit').addEventListener('click', function() {
        //     if(confirm("Entered Data may be lost. Are you sure you still want to exit?")) {
        //         document.querySelector('.help').style.display = 'none';
        //     }
        // });

        // document.getElementById('submit').addEventListener('click', function() {
        //     $.ajax({
        //         url: 'smtp.php',
        //         type: 'POST',
        //         data: new FormData(document.querySelector('form')),
        //         contentType: false,
        //         processData: false,
        //         success: function(response) {
        //             alert(response);
        //             document.querySelector('form').reset();
        //         }
        //     });
        // });

       

        let yearSelect = document.getElementById('yearSelect');
        yearSelect.style.display = 'none';

        let feedbacktype= document.getElementById('type')

        feedbacktype.style.display='none';

        // document.getElementById('feedbackSelect').addEventListener('change', function() {
        //     var query = document.querySelector('.custom-select option:checked');
        //     feedbackName = query.textContent;
        //     feedbackName = feedbackName.replace(/\s+/g, '').toLowerCase().trim();
        //     if(feedbackName === 'studentfeedback-faculty') {
        //         feedbacktype.style.display='block'

        //     } else{
        //         let feedbackCourseId = query.value;
        //         feedbacktype.style.display='none'
        //     }
        //     console.log(feedbackName)

        // })
        
        document.getElementById('feedbackSelect').addEventListener('change', function() {
            var query = document.querySelector('#feedbackSelect option:checked');
            feedbackName = query.textContent.replace(/\s+/g, '').toLowerCase().trim();
        
            if (feedbackName === 'studentfeedback-faculty') {
                feedbacktype.style.display = 'block';
                yearSelect.style.display = 'block';
            } else {
                feedbacktype.style.display = 'none';
                yearSelect.style.display = 'none';
            }
        });

        // document.getElementById('analysis').addEventListener('click', function() {
        //     var query = document.querySelector('.custom-select option:checked');
        //     feedbackName = query.textContent;
        //     var FeedbackType = document.querySelector('#type option:checked').value


        //     // Removing all the possible cases of spaces, Give error only when spelling is not correct
        //     feedbackName = feedbackName.replace(/\s+/g, '').toLowerCase().trim();
        //     console.log(feedbackName);
        //     if(feedbackName == 'Select Feedback') {
        //         alert('Please select a feedback to view analysis');
        //     } else if(feedbackName === 'studentfeedback-faculty') {
        //         if(FeedbackType !== ''){
        //              window.open(`DirectorLevelView/Directorview.php?type=${FeedbackType}`, '_blank');
        //         }
        //         else{
        //             alert('Please Select the Type')
        //         }        
        //     } else{
        //         let feedbackCourseId = query.value;
        //         window.open(`StakeHolderFeedback/index.php?id=${feedbackCourseId}`, '_blank');
        //     }
        //   window.reload();
        // });
        
        document.getElementById('analysis').addEventListener('click', function() {

            var query = document.querySelector('#feedbackSelect option:checked');
            let feedbackName = query.textContent.replace(/\s+/g, '').toLowerCase().trim();
            let feedbackId = query.value;
        
            let type = document.querySelector('#type').value;
            let year = document.querySelector('#yearSelect').value;
        
            if (feedbackName === 'studentfeedback-faculty') {
                if (type === '' || year === '') {
                    alert("Please select both Type and Academic Year");
                    return;
                }
        
                window.open(
                    `DirectorLevelView/Directorview.php?type=${type}&year=${encodeURIComponent(year)}`,
                    '_blank'
                );
            } else {
                window.open(`StakeHolderFeedback/index.php?id=${feedbackId}`, '_blank');
            }
        });
        
        
    </script>
</body>
</html>
