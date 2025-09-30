<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/encryption.php";

if (isset($_COOKIE['encrypted_user_role'])) {
    if (decrypt_id($_COOKIE['encrypted_user_role']) === 'admin') {
        header("Location: index.php");
        exit;
    }
}
$error = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, full_name, password, role, profile_image FROM public.users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $cookie_expiry = time() + 86400; // 1 day
            setcookie("encrypted_user_id", encrypt_id($admin['id']), $cookie_expiry, "/");
            setcookie("encrypted_user_role", encrypt_id($admin['role']), $cookie_expiry, "/");
            setcookie("encrypted_user_name", encrypt_id($admin['full_name']), $cookie_expiry, "/");
            setcookie("encrypted_profile_image", encrypt_id($admin['profile_image'] ?? ''), $cookie_expiry, "/");

            header("Location: index.php");
            exit;
        } else {
            $error = "Login failed. Check credentials.";
        }
    } catch (PDOException $e) {
        $error = "A system error occurred.";
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DailyFix - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo">
                    <div class="logo-inner">
                        <img src="../assets/images/logo.png" alt="DailyFix Logo">
                    </div>
                </div>
                <h1 class="login-title">Administrator Login</h1>
                <p class="login-subtitle">Enter your admin credentials to continue.</p>
            </div>

            <div id="login-alert-placeholder">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <form id="loginForm" method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-envelope"></i>
                        <input type="email" id="email" class="form-control" name="email" placeholder="you@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="input-icon fas fa-lock"></i>
                        <input type="password" id="password" class="form-control" name="password" placeholder="••••••••" required>
                        <i class="password-toggle fas fa-eye" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="../forgot_password.php?from=admin">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Log In</button>
            </form>
        </div>
    </div>

    <script>
        // Simple password visibility toggle script
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>