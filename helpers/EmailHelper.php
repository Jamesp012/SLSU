<?php
// helpers/EmailHelper.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if vendor/autoload.php exists (for Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class EmailHelper {
    /**
     * Sends a temporary password to a student via PHPMailer
     */
    public static function sendTempPassword($toEmail, $firstName, $tempPassword) {
        // Ensure PHPMailer classes are available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer not found. Please run: composer require phpmailer/phpmailer");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output to avoid breaking JSON responses
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'studentslsu3@gmail.com'; 
            $mail->Password   = 'khdk hpxa hycc nhwy'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = 587; 

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('studentslsu3@gmail.com', 'SLSU Entrance Exam Portal');
            $mail->addAddress($toEmail, $firstName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Student Account - Temporary Password';
            
            $mail->Body    = "
                <h3>Welcome to SLSU Entrance Exam Portal, $firstName!</h3>
                <p>Your student account has been successfully created by the administrator.</p>
                <p><b>Login Details:</b></p>
                <ul>
                    <li><b>Email:</b> $toEmail</li>
                    <li><b>Temporary Password:</b> $tempPassword</li>
                </ul>
                <p>Please log in and complete your profile onboarding to proceed.</p>
                <br>
                <p>Best Regards,<br>SLSU Administration</p>
            ";
            
            $mail->AltBody = "Hello $firstName,\n\nYour student account has been created.\nEmail: $toEmail\nTemporary Password: $tempPassword\n\nPlease log in to complete your profile.\n\nBest Regards,\nSLSU Administration";

            $sent = $mail->send();
            return $sent;
        } catch (Exception $e) {
            $errorLog = date('Y-m-d H:i:s') . " - PHPMailer Error: {$mail->ErrorInfo}\n";
            @file_put_contents(__DIR__ . '/../logs/debug.log', $errorLog, FILE_APPEND);
            return false;
        }
    }
}
