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
            $cookie_expiry = time() + 86400;
            setcookie("encrypted_user_id", encrypt_id($admin['id']), $cookie_expiry, "/");
            setcookie("encrypted_user_role", encrypt_id($admin['role']), $cookie_expiry, "/");
            setcookie("encrypted_user_name", encrypt_id($admin['full_name']), $cookie_expiry, "/");
            setcookie("encrypted_profile_image", encrypt_id($admin['profile_image'] ?? ''), $cookie_expiry, "/");

            header("Location: index.php");
            exit;
        } else {
            $error = "Login failed. Check credentials or you may not be an admin.";
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
</head>
<body>
    <div class="login-wrapper">
        <div class="row g-0">
            <div class="col-lg-5 d-none d-lg-flex login-branding-panel">
                <div class="branding-content">
                    <img src="../assets/images/logo.png" alt="DailyFix Logo" class="branding-logo">
                    <h1>Admin Panel</h1>
                    <p>Log in to manage users, bookings, and site operations.</p>
                </div>
            </div>
            <div class="col-12 col-lg-7 login-form-panel">
                <div class="login-form-container">
                    <h2>Administrator Login</h2>
                    <p class="subtitle">Enter your admin credentials to continue.</p>
                    <div id="login-alert-placeholder">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                    </div>
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

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-custom-login">Log In</button>
                        </div>

                        <div class="text-center mt-1">
                            <a href="/dailyfix/forgot_password_page.php" class="forgot-password-link">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Password visibility toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>