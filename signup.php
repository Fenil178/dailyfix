<?php
// This PHP block should be at the very top of the file.
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/encryption.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $response = [];

    // Retrieve data from POST request
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? '';
    $profile_image = $_FILES['profile_image'] ?? null;
    $profile_imagePath = null;

   // --- File Upload Handling ---
if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/customer/uploads/';
    if (!is_dir($uploadDir)) {
        // Attempt to create the directory if it doesn't exist
        if (!mkdir($uploadDir, 0755, true)) {
            $response = ['status' => 'error', 'message' => 'Failed to create upload directory. Check server permissions.'];
            echo json_encode($response);
            exit;
        }
    }
    
    $imageFileType = strtolower(pathinfo($profile_image['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowedExtensions)) {
        $fileName = uniqid('', true) . '.' . $imageFileType;
        $uploadFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($profile_image['tmp_name'], $uploadFilePath)) {
            // Success! Set the path for the database.
            $profile_imagePath = '/dailyfix/customer/uploads/' . $fileName;
        } else {
            // Failed to move file, send a specific error message.
            $uploadError = error_get_last(); // Get the underlying system error
            $response = ['status' => 'error', 'message' => 'Failed to upload profile image. Error: ' . ($uploadError['message'] ?? 'Unknown error.')];
            echo json_encode($response);
            exit;
        }
    } else {
        // Invalid file type
        $response = ['status' => 'error', 'message' => 'Invalid file type. Please upload a JPG, JPEG, PNG, or GIF.'];
        echo json_encode($response);
        exit;
    }
} elseif ($profile_image && $profile_image['error'] !== UPLOAD_ERR_OK) {
    // Handle other potential upload errors
    $response = ['status' => 'error', 'message' => 'There was an error with the file upload. Code: ' . $profile_image['error']];
    echo json_encode($response);
    exit;
}

    // Simple validation
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $response = ['status' => 'error', 'message' => 'Please fill all required fields.'];
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['status' => 'error', 'message' => 'Invalid email format.'];
        echo json_encode($response);
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM dailyfix.users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response = ['status' => 'error', 'message' => 'An account with this email already exists.'];
            echo json_encode($response);
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $accountStatus = 'active';

        // Insert new user
        $sql = "INSERT INTO dailyfix.users (full_name, email, password, phone, role, profile_image, account_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$full_name, $email, $hashedPassword, $phone, $role, $profile_imagePath, $accountStatus])) {
            // ---- START: NEW LOGIN LOGIC ----

            // Get the ID of the user we just created
            $new_user_id = $conn->lastInsertId();

            // Set the exact same cookies that login.php sets to create the session
            setcookie("encrypted_user_id", encrypt_id($new_user_id), time() + 86400, "/");
            setcookie("encrypted_user_role", encrypt_id($role), time() + 86400, "/");
            setcookie("encrypted_user_name", encrypt_id($full_name), time() + 86400, "/");
            setcookie("encrypted_profile_image", encrypt_id($profile_imagePath ?? ''), time() + 86400, "/");

            // Update the response to redirect to the dashboard
            $response = ['status' => 'success', 'message' => 'Account created! Redirecting to your dashboard...', 'redirect' => 'dashboard.php?action=new_user'];

            // ---- END: NEW LOGIN LOGIC ----
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to create account. Please try again.'];
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $response = ['status' => 'error', 'message' => 'A database error occurred.'];
    }

    echo json_encode($response);
    $conn = null;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Create Your Account - DailyFix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/signup.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="row g-0">
            <div class="col-lg-5 d-none d-lg-flex login-branding-panel">
            <div class="branding-content">
                <img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo" class="branding-logo">
                <h1>Start Your Journey</h1>
                <p>Create an account to join the DailyFix community and manage your services with ease.</p>
            </div>
            </div>
            
            <div class="col-12 col-lg-7 login-form-panel">
                <div class="login-form-container">
                    <h2>Create Account</h2>
                    <p class="subtitle">Join the DailyFix community to get started.</p>
                    
                    <div id="signup-alert-placeholder"></div>

                    <form id="signupForm" method="POST" action="signup.php" enctype="multipart/form-data" novalidate>
                        <div class="form-group">
                            <i class="fas fa-user form-icon"></i>
                            <input type="text" class="form-control-custom" name="full_name" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-envelope form-icon"></i>
                            <input type="email" class="form-control-custom" name="email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-lock form-icon"></i>
                            <input type="password" class="form-control-custom" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <div class="form-group">
                            <i class="fas fa-phone form-icon"></i>
                            <input type="tel" class="form-control-custom" name="phone" placeholder="Phone Number">
                        </div>
                        <div class="form-group">
                             <i class="fas fa-user-tie form-icon"></i>
                             <select class="form-control-custom" name="role" required>
                               <option value="" disabled selected>Select a role...</option>
                               <option value="customer">Customer (I need a service)</option>
                               <option value="worker">Worker (I provide a service)</option>
                           </select>
                       </div>
                       
                        <label for="profile_image" class="form-label">Profile Picture (Optional)</label>
                        <div class="file-drop-area mb-4">
                            <i class="fas fa-cloud-upload-alt file-icon"></i>
                            <span class="file-msg">Drag & drop a file or click to select</span>
                            <input type="file" class="file-input" id="profile_image" name="profile_image" accept="image/*">
                            <div class="file-preview-container" id="filePreviewContainer"></div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-signup">Create Account</button>
                        </div>
                        <div class="text-center mt-4">
                            <p>Already have an account? <a class="signup-link" href="/dailyfix/login.php">Log In</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dailyfix/assets/js/signup.js"></script>
</body>
</html>