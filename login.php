<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/encryption.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    $response = [];

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (strpos($email, '@') === false) {
        $response = ['status' => 'error', 'message' => 'Invalid email format.'];
        echo json_encode($response);
        exit();
    }

    try {
        $stmt = $conn->prepare('SELECT id, password, role, full_name, profile_image, account_status FROM public.users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // ** THIS IS THE NEW SECURITY CHECK **
                if ($user['role'] === 'admin') {
                    $response = ['status' => 'error', 'message' => 'Admin accounts must use the admin login page.'];
                } elseif ($user['account_status'] === 'suspended') {
                    $response = ['status' => 'error', 'message' => 'Your account has been suspended.'];
                } else {
                    // Set cookies for the session
                    setcookie("encrypted_user_id", encrypt_id($user['id']), time() + 86400, "/");
                    setcookie("encrypted_user_role", encrypt_id($user['role']), time() + 86400, "/");
                    setcookie("encrypted_user_name", encrypt_id($user['full_name']), time() + 86400, "/");
                    setcookie("encrypted_profile_image", encrypt_id($user['profile_image'] ?? ''), time() + 86400, "/");

                    $response = ['status' => 'success', 'message' => 'Login successful! Redirecting...', 'redirect' => 'dashboard.php'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid password.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'An email doesn\'t exist. Please check it again or signup.'];
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response = ['status' => 'error', 'message' => 'A system error occurred.'];
    }

    echo json_encode($response);
    $conn = null;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DailyFix - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/dailyfix/assets/css/login.css" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo">
                    <div class="logo-inner">
                        <img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo">
                    </div>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to continue to DailyFix</p>
            </div>

            <div id="login-alert-placeholder"></div>

            <form id="loginForm" method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" name="email" placeholder="you@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" name="password" id="password" placeholder="••••••••" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="forgot_password.php?from=user">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Sign In</button>

                <div class="divider">
                    <span>New to DailyFix?</span>
                </div>

                <div class="signup-text">
                    <a href="/dailyfix/signup.php">Create an Account →</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>