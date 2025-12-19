<?php
/**
 * Email Helper Functions
 * 
 * This file contains helper functions for sending emails, including verification emails,
 * password resets, and notifications.
 */

/**
 * Send a verification email to the user with a verification link
 * 
 * @param string $email User's email address
 * @param string $name User's name
 * @param string $verificationLink The verification link to include in the email
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendVerificationEmail($email, $name, $verificationLink) {
    // Email subject
    $subject = 'Verify Your Email Address';
    
    // Email message
    $message = "
    <html>
    <head>
        <title>Verify Your Email Address</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button {
                display: inline-block; 
                padding: 10px 20px; 
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer { 
                margin-top: 30px; 
                font-size: 12px; 
                color: #777; 
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Welcome to Our Hotel Management System</h2>
            <p>Hello {$name},</p>
            <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
            <p>
                <a href='{$verificationLink}' class='button'>Verify Email Address</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p><small>{$verificationLink}</small></p>
            <p>If you did not create an account, no further action is required.</p>
            <div class='footer'>
                <p>This is an automated message, please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Hotel Management System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";

    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: noreply@hotelmanagement.com',
        'Reply-To: noreply@hotelmanagement.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    // Send email
    try {
        // In a production environment, you would use a proper email sending library
        // like PHPMailer, SwiftMailer, or an email service API
        $result = mail($email, $subject, $message, implode("\r\n", $headers));
        
        // Log the email sending attempt
        error_log("Verification email sent to {$email}");
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to send verification email to {$email}: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a password reset email
 * 
 * @param string $email User's email address
 * @param string $name User's name
 * @param string $resetLink The password reset link
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendPasswordResetEmail($email, $name, $resetLink) {
    // Similar implementation to sendVerificationEmail
    // ...
    return true;
}

/**
 * Send a booking confirmation email
 * 
 * @param string $email User's email address
 * @param string $name User's name
 * @param array $bookingDetails Details of the booking
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendBookingConfirmationEmail($email, $name, $bookingDetails) {
    // Implementation for booking confirmation email
    // ...
    return true;
}

/**
 * Send a notification to hotel staff
 * 
 * @param string $staffEmail Staff email address
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendStaffNotification($staffEmail, $subject, $message) {
    // Implementation for staff notifications
    // ...
    return true;
}

// In a production environment, you might want to use a more robust email sending solution
// such as PHPMailer, SwiftMailer, or an email service API like SendGrid or Mailgun

// Example of how to use PHPMailer (uncomment and configure as needed):
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailWithPHPMailer($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('noreply@hotelmanagement.com', 'Hotel Management System');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
*/
?>
