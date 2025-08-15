<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/encryption.php";

// This block of PHP for user authentication is preserved
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    $response = [];

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare('SELECT id, password, role, full_name, profile_image, account_status FROM public.users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['account_status'] === 'suspended') {
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
            $response = ['status' => 'error', 'message' => 'Invalid email or password.'];
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="row g-0">
            <div class="col-lg-5 d-none d-lg-flex login-branding-panel">
            <div class="branding-content">
                <img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo" class="branding-logo">
                <h1>Welcome Back!</h1>
                <p>Log in to access your dashboard and manage your services.</p>
            </div>
            </div>
            <div class="col-12 col-lg-7 login-form-panel">
                <div class="login-form-container">
                    <h2>Login</h2>
                    <p class="subtitle">Enter your credentials to continue.</p>
                    <div id="login-alert-placeholder"></div>
                    <form id="loginForm" method="POST" action="login.php" novalidate>
                        <div class="form-group">
                            <i class="fas fa-envelope form-icon"></i>
                            <input type="email" class="form-control form-control-custom" name="email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control form-control-custom" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                         <div class="text-end mb-4">
                            <a href="/dailyfix/forgot_password_page.php" class="forgot-password-link">Forgot Password?</a>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-custom-login">Log In</button>
                        </div>
                        <div class="text-center mt-4">
                            <p>Don't have an account? <a class="signup-link" href="/dailyfix/signup.php">Sign Up</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>