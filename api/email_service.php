<?php
// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// This works from both the web (e.g., /api/ file) and the terminal (cron job).
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends a pre-formatted HTML email.
 *
 * @param string $toEmail The recipient's email address.
 * @param string $toName The recipient's full name (for personalization).
 * @param string $subject The subject line of the email.
 * @param string $htmlBody The full HTML content of the email.
 * @return bool True on success, false on failure.
 */
function sendEmailNotification($toEmail, $toName, $subject, $htmlBody) {
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dailyfix041517@gmail.com'; // Your SMTP username
        $mail->Password   = 'garv sfae zotw tdfx'; // Your 16-character App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;                     // !! REPLACE (e.g., 465 if using SMTPS)

        // Sender & Recipient
        $mail->setFrom('dailyfix041517@gmail.com', 'DailyFix Notifications');
        $mail->addAddress($toEmail, $toName); // Add the recipient

        // Email Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody); // A plain-text version for non-HTML email clients

        // Send the email
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Log the error securely. Do not show it to the end-user.
        // This ensures that even if an email fails, the app doesn't crash.
        error_log("Email could not be sent to $toEmail. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}


/**
 * Builds a standardized HTML email template.
 *
 * @param string $name The user's name for a personal greeting.
 * @param string $message The core notification message.
 * @param string $link The absolute URL for the call-to-action button.
 * @return string The full HTML for the email body.
 */
function buildEmailTemplate($name, $message, $link) {
    $year = date("Y");
    // Use a fallback host in case $_SERVER['HTTP_HOST'] isn't available (e.g., in cron)
    $host = $_SERVER['HTTP_HOST'] ?? 'dailyfix.com'; 

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-B'>
        <title>DailyFix Notification</title>
        <style>
            body { margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; }
            .container { width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
            .header { background-color: #343a40; color: #ffffff; padding: 20px 30px; }
            .header h1 { margin: 0; font-size: 24px; color: #ffffff !important; text-decoration: none; }
            .content { padding: 30px; }
            .content p { margin-bottom: 20px; font-size: 16px; color: #333; }
            .message-box { background-color: #f8f9fa; padding: 20px; border-radius: 5px; border: 1px solid #eee; }
            .button-container { text-align: center; margin: 30px 0; }
            .button { background-color: #007bff; color: #ffffff !important; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; }
            .footer { background-color: #f8f9fa; color: #888; padding: 20px 30px; text-align: center; font-size: 12px; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1><a href='https://" . htmlspecialchars($host) . "' style='color:#ffffff; text-decoration:none;'>DailyFix</a></h1>
            </div>
            <div class='content'>
                <p>Hi " . htmlspecialchars($name) . ",</p>
                <p>You have a new notification:</p>
                
                <div class='message-box'>
                    <p style='margin:0;'><strong>" . htmlspecialchars($message) . "</strong></p>
                </div>

                <div class='button-container'>
                    <a href='" . htmlspecialchars($link) . "' class='button'>View Details</a>
                </div>

                <p>Thank you for using DailyFix!</p>
            </div>
            <div class='footer'>
                <p>&copy; $year DailyFix. All rights reserved.</p>
                <p>This is an automated notification. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>