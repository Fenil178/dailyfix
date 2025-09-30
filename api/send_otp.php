<?php
header('Content-Type: application/json');

// --- IMPORTANT: Include PHPMailer's autoloader ---
require __DIR__ . '/../vendor/autoload.php';

include_once __DIR__ . "/connect.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    try {
        // 1. Check if user exists
        $stmt = $conn->prepare("SELECT id FROM public.users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            $response['message'] = 'No account found with that email address.';
            echo json_encode($response);
            exit;
        }

        // 2. Generate OTP and expiration time
        $otp = random_int(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // 3. Store OTP in the database
        $stmt = $conn->prepare("UPDATE public.users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
        $stmt->execute([$otp, $otp_expiry, $email]);

        // 4. Send OTP via email using PHPMailer
        $mail = new PHPMailer(true);
        
        // --- SERVER SETTINGS ---
        // $mail->SMTPDebug = 2; // THIS LINE IS NOW COMMENTED OUT - THIS IS THE FIX
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dailyfix041517@gmail.com'; // Your SMTP username
        $mail->Password   = 'garv sfae zotw tdfx'; // Your 16-character App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- RECIPIENTS ---
        $mail->setFrom('dailyfix041517@gmail.com', 'DailyFix Support');
        $mail->addAddress($email);

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = 'Your DailyFix Password Reset Code';
        $mail->Body    = "Your password reset code is: <b>$otp</b>. This code is valid for 10 minutes.";
        $mail->AltBody = "Your password reset code is: $otp. This code is valid for 10 minutes.";

        $mail->send();

        $response = ['status' => 'success', 'message' => 'OTP sent successfully.'];

    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo); 
        $response['message'] = 'Failed to send OTP email. Please check server logs or SMTP settings.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>