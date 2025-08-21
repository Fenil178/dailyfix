<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/encryption.php";

// This PHP block handles the final form submission from the wizard
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $response = [];

    // --- Standard Fields ---
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? '';
    $profile_image = $_FILES['profile_image'] ?? null;
    $profile_imagePath = null;
    
    // --- Worker-Specific Fields ---
    $worker_key = isset($_POST['worker_key']) ? trim(strtoupper(str_replace('-', '', $_POST['worker_key']))) : null;
    $bio = $_POST['bio'] ?? null;
    $experience_years = $_POST['experience_years'] ?? null;
    $hourly_rate = $_POST['hourly_rate'] ?? null;
    $selected_services = $_POST['services'] ?? [];

    // --- Validation ---
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }
    if ($role === 'worker' && empty($worker_key)) {
        echo json_encode(['status' => 'error', 'message' => 'Worker key is missing. Please go back and re-verify.']);
        exit;
    }

    // --- File Upload Handling ---
    if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/profile_images/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $profile_imagePath = "uploads/profile_images/" . $newFileName;
        if (!move_uploaded_file($profile_image['tmp_name'], __DIR__ . "/" . $profile_imagePath)) {
            $profile_imagePath = null; // Reset if upload fails
        }
    }

    try {
        $conn->beginTransaction();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM public.users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
            exit;
        }

        // If worker, re-validate the key
        if ($role === 'worker') {
            $key_stmt = $conn->prepare("SELECT id FROM public.worker_keys WHERE access_key = ? AND is_used = false FOR UPDATE");
            $key_stmt->execute([$worker_key]);
            if (!$key_stmt->fetch()) {
                $conn->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Worker key is invalid or was just used. Please try again.']);
                exit;
            }
        }

        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO public.users (full_name, email, password, phone, role, profile_image, account_status) VALUES (?, ?, ?, ?, ?, ?, 'active') RETURNING id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$full_name, $email, $hashedPassword, $phone, $role, $profile_imagePath]);
        $new_user_id = $stmt->fetchColumn();

        if ($role === 'worker') {
            // Insert worker profile details
            $stmt_profile = $conn->prepare("INSERT INTO public.worker_profiles (user_id, bio, experience_years, hourly_rate) VALUES (?, ?, ?, ?)");
            $stmt_profile->execute([$new_user_id, $bio, $experience_years, $hourly_rate]);
            
            // Mark the key as used
            $stmt_key_update = $conn->prepare("UPDATE public.worker_keys SET is_used = true, used_by_worker_id = ? WHERE access_key = ?");
            $stmt_key_update->execute([$new_user_id, $worker_key]);

            // Link services to worker (if any)
            if (!empty($selected_services)) {
                $sql_worker_services = "INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)";
                $stmt_services = $conn->prepare($sql_worker_services);
                foreach ($selected_services as $service_id) {
                    $stmt_services->execute([$new_user_id, $service_id]);
                }
            }
        }
        
        $conn->commit();
        
        // Set cookies and send success response
        setcookie("encrypted_user_id", encrypt_id($new_user_id), time() + 86400, "/");
        setcookie("encrypted_user_role", encrypt_id($role), time() + 86400, "/");
        setcookie("encrypted_user_name", encrypt_id($full_name), time() + 86400, "/");
        setcookie("encrypted_profile_image", encrypt_id($profile_imagePath ?? ''), time() + 86400, "/");

        $dashboard_path = 'dashboard.php';
        $response = ['status' => 'success', 'message' => 'Account created! Redirecting...', 'redirect' => $dashboard_path . '?action=new_user'];

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        $response = ['status' => 'error', 'message' => 'A database error occurred.'];
    }

    echo json_encode($response);
    exit;
}

// PHP to fetch services for the form
$groupedSubServices = [];
try {
    $stmt = $conn->query("SELECT s.name AS service_name, ss.id, ss.name AS sub_service_name, ss.icon FROM public.sub_services ss JOIN public.services s ON ss.service_id = s.id ORDER BY s.name, ss.name");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $groupedSubServices[$row['service_name']][] = ['id' => $row['id'], 'name' => $row['sub_service_name'], 'icon' => $row['icon']];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch sub-services for setup page: " . $e->getMessage());
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
                    <div id="signup-alert-placeholder"></div>

                    <div class="step active" id="step-role">
                        <h2>Join as a Customer or Worker</h2>
                        <p class="subtitle">First, tell us who you are.</p>
                        <div class="role-selection">
                            <div class="role-card" data-role="customer">
                                <i class="fas fa-user"></i>
                               <h3>I'm a Customer</h3>
                               <p>I'm here to find and book services.</p>
                            </div>
                            <div class="role-card" data-role="worker">
                                <i class="fas fa-user-tie"></i>
                               <h3>I'm a Worker</h3>
                               <p>I'm here to offer my professional services.</p>
                            </div>
                        </div>
                         <div class="text-center mt-4">
                            <p>Already have an account? <a class="signup-link" href="/dailyfix/login.php">Log In</a></p>
                        </div>
                    </div>

                    <div class="step" id="step-key">
                        <button type="button" class="back-btn" data-target="step-role"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Worker Verification</h2>
                        <p class="subtitle">Please enter the 8-character key provided by your administrator.</p>
                        <form id="keyForm" novalidate>
                            <div class="form-group">
                                <i class="fas fa-key form-icon"></i>
                                <input type="text" class="form-control-custom" id="worker_key_input" name="worker_key" placeholder="e.g., S9M1-17FR" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-signup">Verify Key</button>
                            </div>
                        </form>
                    </div>

                    <div class="step" id="step-register-part1">
                        <button type="button" class="back-btn" id="register-back-btn"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Create Your <span id="role-title"></span> Account</h2>
                        <p class="subtitle">Please fill in your details to get started.</p>
                        
                        <div id="part1-form">
                            <input type="hidden" name="role" id="role-hidden-input">
                            <input type="hidden" name="worker_key" id="worker-key-hidden-input">
                            
                            <div class="form-group">
                                <i class="fas fa-user form-icon"></i>
                                <input type="text" class="form-control-custom" name="full_name" id="full_name" placeholder="Full Name" required>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-envelope form-icon"></i>
                                <input type="email" class="form-control-custom" name="email" id="email" placeholder="Email Address" required>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-lock form-icon"></i>
                                <input type="password" class="form-control-custom" name="password" id="password" placeholder="Password" required>
                                <span id="togglePassword" class="fas fa-eye password-toggle"></span>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-phone form-icon"></i>
                                <input type="tel" class="form-control-custom" name="phone" id="phone" placeholder="Phone Number" required>
                            </div>
                            <div class="form-group">
                                <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                                <div class="file-drop-area">
                                    <i class="fas fa-cloud-upload-alt file-icon"></i>
                                    <span class="file-msg">Drag & drop your profile image here, or click to browse.</span>
                                    <input type="file" class="file-input" name="profile_image" id="profile_image" accept="image/*">
                                    <div id="filePreviewContainer" class="file-preview-container"></div>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="button" class="btn btn-signup next-btn" data-target="step-register-part2">Next</button>
                            </div>
                        </div>
                    </div>

                    <div class="step" id="step-register-part2">
                        <button type="button" class="back-btn" data-target="step-register-part1"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Your Professional Details</h2>
                        <p class="subtitle">Tell us more about your expertise to help customers find you.</p>
                        <form id="signupForm" method="POST" enctype="multipart/form-data" novalidate>
                            <div id="worker-fields">
                                <div class="form-group">
                                    <label for="bio">Professional Bio</label>
                                    <textarea class="form-control-custom" id="bio" name="bio" rows="3" placeholder="Describe your skills and services..." required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="experience_years">Years of Experience</label>
                                            <input type="number" class="form-control-custom" id="experience_years" name="experience_years" placeholder="e.g., 5" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hourly_rate">Hourly Rate (₹)</label>
                                            <input type="number" step="0.01" class="form-control-custom" id="hourly_rate" name="hourly_rate" placeholder="e.g., 25.50" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-4">
                                    <label for="services">Select Services You Offer</label>
                                    <div class="services-category-list">
                                        <?php foreach ($groupedSubServices as $categoryName => $services): ?>
                                            <div class="service-category-group">
                                                <h4><i class="fas fa-wrench"></i> <?= htmlspecialchars($categoryName) ?></h4>
                                                <div class="services-checkbox-grid">
                                                    <?php foreach ($services as $service): ?>
                                                        <div class="checkbox-item">
                                                            <input type="checkbox" id="service-<?= $service['id'] ?>" name="services[]" value="<?= $service['id'] ?>">
                                                            <label for="service-<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-signup">Create Account</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dailyfix/assets/js/signup.js"></script>
</body>
</html>