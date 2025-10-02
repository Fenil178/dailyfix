<?php
// Determine the correct login path based on the 'from' URL parameter
$from = $_GET['from'] ?? 'user'; // Default to 'user' if not specified
$login_path = ($from === 'admin') ? '/dailyfix/admin/login.php' : '/dailyfix/login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DailyFix - Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <style>
        /* Using the same styles from your new login.php for consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #1F2334; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; position: relative; overflow: hidden; }
        body::before, body::after { content: ''; position: absolute; background: rgba(59, 130, 246, 0.15); border-radius: 50%; animation: float 8s ease-in-out infinite; }
        body::before { width: 400px; height: 400px; top: -200px; left: -200px; animation-duration: 6s; }
        body::after { width: 300px; height: 300px; bottom: -150px; right: -150px; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(20px); } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .form-container { width: 100%; max-width: 440px; position: relative; z-index: 1; animation: slideIn 0.6s ease-out; }
        .form-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 24px; padding: 48px 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.2); }
        .logo-container { text-align: center; margin-bottom: 32px; }
        .logo { width: 80px; height: 80px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 16px; padding: 2px; display: inline-block; margin-bottom: 16px; box-shadow: 0 8px 20px rgba(30, 58, 138, 0.4); }
        .logo-inner { width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.95); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .logo-inner img { width: 50px; height: auto; }
        .form-title { font-size: 28px; font-weight: 700; color: #1a202c; margin-bottom: 8px; letter-spacing: -0.5px; }
        .form-subtitle { font-size: 15px; color: #718096; margin-bottom: 0; }
        .form-group { margin-bottom: 24px; position: relative; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #2d3748; margin-bottom: 8px; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 16px; transition: color 0.3s; }
        .form-control { width: 100%; padding: 14px 16px 14px 48px; font-size: 15px; border: 2px solid #e2e8f0; border-radius: 12px; background: #f7fafc; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .form-control:focus { outline: none; border-color: #3b82f6; background: white; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .form-control:focus ~ .input-icon { color: #3b82f6; }
        .btn-submit { width: 100%; padding: 14px; font-size: 16px; font-weight: 600; color: white; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(30, 58, 138, 0.4); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(30, 58, 138, 0.5); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .back-link { text-align: center; margin-top: 24px; font-size: 14px; }
        .back-link a { color: #3b82f6; text-decoration: none; font-weight: 600; }
        .alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; border: none; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .step { display: none; }
        .step.active { display: block; animation: slideIn 0.5s ease-out; }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-card">
            <div class="logo-container">
                <div class="logo"><div class="logo-inner"><img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo"></div></div>
                <h1 class="form-title" id="form-title">Forgot Password</h1>
                <p class="form-subtitle" id="form-subtitle">Enter your email to get an OTP</p>
            </div>

            <div id="alert-placeholder"></div>

            <div id="email-step" class="step active">
                <form id="email-form">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Send OTP</button>
                </form>
            </div>

            <div id="otp-step" class="step">
                <form id="otp-form">
                    <input type="hidden" name="email" id="otp-email">
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="text" class="form-control" name="otp" placeholder="Enter 6-digit OTP" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" placeholder="Enter new password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Reset Password</button>
                </form>
            </div>

            <div id="success-step" class="step" style="text-align: center;">
                <h1 class="form-title">Success!</h1>
                <p class="form-subtitle">Your password has been reset successfully.</p>
                <div class="back-link" style="margin-top: 2rem;">
                    <a href="<?php echo htmlspecialchars($login_path); ?>" style="text-decoration: none;">Back to Login</a>
                </div>
            </div>

            <div class="back-link" id="main-back-link">
                 <a href="<?php echo htmlspecialchars($login_path); ?>">← Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailStep = document.getElementById('email-step');
            const otpStep = document.getElementById('otp-step');
            const successStep = document.getElementById('success-step');

            const emailForm = document.getElementById('email-form');
            const otpForm = document.getElementById('otp-form');
            
            const formTitle = document.getElementById('form-title');
            const formSubtitle = document.getElementById('form-subtitle');
            const alertPlaceholder = document.getElementById('alert-placeholder');
            const mainBackLink = document.getElementById('main-back-link');

            function showAlert(message, type = 'danger') {
                alertPlaceholder.innerHTML = `<div class="alert alert-${type}"><span>${message}</span></div>`;
            }

            // Handle Step 1: Send OTP
            emailForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const submitButton = this.querySelector('button');
                submitButton.disabled = true;
                submitButton.textContent = 'Sending...';

                const formData = new FormData();
                formData.append('email', email);

                fetch('api/send_otp.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('otp-email').value = email;
                            emailStep.classList.remove('active');
                            otpStep.classList.add('active');
                            formTitle.textContent = 'Verify Your Account';
                            formSubtitle.textContent = `An OTP has been sent to ${email}`;
                            alertPlaceholder.innerHTML = '';
                        } else {
                            showAlert(data.message);
                        }
                    })
                    .catch(() => showAlert('An network error occurred.'))
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Send OTP';
                    });
            });

            // Handle Step 2: Reset Password
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitButton = this.querySelector('button');
                submitButton.disabled = true;
                submitButton.textContent = 'Resetting...';

                fetch('api/reset_password.php', { method: 'POST', body: new FormData(this) })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            otpStep.classList.remove('active');
                            successStep.classList.add('active');
                            mainBackLink.style.display = 'none'; // Hide back link on success
                            formTitle.style.display = 'none';
                            formSubtitle.style.display = 'none';
                            alertPlaceholder.innerHTML = '';
                        } else {
                            showAlert(data.message);
                        }
                    })
                    .catch(() => showAlert('An network error occurred.'))
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Reset Password';
                    });
            });
        });
    </script>
</body>
</html>