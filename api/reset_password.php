<?php
header('Content-Type: application/json');

include_once __DIR__ . "/connect.php";

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($otp) || empty($password)) {
        $response['message'] = 'Please fill all fields.';
        echo json_encode($response);
        exit;
    }

    try {
        // 1. Find user and check OTP
        $stmt = $conn->prepare("SELECT otp_code, otp_expires_at FROM public.users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $response['message'] = 'Invalid request.';
        } elseif ($user['otp_code'] !== $otp) {
            $response['message'] = 'Invalid OTP code.';
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $response['message'] = 'OTP has expired. Please request a new one.';
        } else {
            // 2. All checks passed, update the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Set OTP fields to NULL for security
            $stmt = $conn->prepare("UPDATE public.users SET password = ?, otp_code = NULL, otp_expires_at = NULL WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);

            $response = ['status' => 'success', 'message' => 'Password reset successfully.'];
        }

    } catch (Exception $e) {
        error_log("Password Reset Error: " . $e->getMessage());
        $response['message'] = 'A database error occurred.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>