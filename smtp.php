<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $attachment = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true; 
        $mail->Username = 'surajraut347@gmail.com'; 
        $mail->Password = 'yune ggqr rtaq jssw'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = 587; 


        $mail->setFrom($email, $fullname); 
        $mail->addAddress('surajraut347@gmail.com', 'Suraj Raut'); 
 
        $mail->isHTML(true);
        $mail->Subject = $subject;
        // $mail->Body    = "Full Name: $fullname<br>Email: $email<br>Subject: $subject<br>Message: $message";
        $mail->Body    = "Dear Team, <br>" . "$message";

        if ($attachment && $attachment['error'] == UPLOAD_ERR_OK) {
            $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
        }

        $mail->send();
        echo 'Your feedback has been sent successfully!';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
