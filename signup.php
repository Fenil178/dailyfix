<?php
// This PHP block should be at the very top of the file.
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/encryption.php";

// Fetch all available sub-services to create the checklist
// In signup.php, replace the sub_services query with this:
$groupedSubServices = [];
try {
    $stmt = $conn->query("
        SELECT 
            s.name AS service_name, 
            ss.id, 
            ss.name AS sub_service_name, 
            ss.icon
        FROM 
            public.sub_services ss
        JOIN 
            public.services s ON ss.service_id = s.id
        ORDER BY 
            s.name, ss.name
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $groupedSubServices[$row['service_name']][] = [
            'id' => $row['id'],
            'name' => $row['sub_service_name'],
            'icon' => $row['icon']
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch sub-services for setup page: " . $e->getMessage());
}

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
    
    // Worker-specific fields
    $bio = $_POST['bio'] ?? null;
    $experience_years = $_POST['experience_years'] ?? null;
    $hourly_rate = $_POST['hourly_rate'] ?? null;
    $selected_services = $_POST['services'] ?? [];

    // --- File Upload Handling ---
    if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
        $uploadSubDir = '';
        if ($role === 'worker') {
            $uploadSubDir = 'worker/uploads/';
        } elseif ($role === 'customer') {
            $uploadSubDir = 'customer/uploads/';
        } else {
            $response = ['status' => 'error', 'message' => 'Please select a role before uploading an image.'];
            echo json_encode($response);
            exit;
        }
        
        $uploadDir = __DIR__ . '/' . $uploadSubDir;
        
        if (!is_dir($uploadDir)) {
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
                $profile_imagePath = '/dailyfix/' . $uploadSubDir . $fileName;
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to upload profile image.'];
                echo json_encode($response);
                exit;
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Invalid file type. Please upload a JPG, JPEG, PNG, or GIF.'];
            echo json_encode($response);
            exit;
        }
    } elseif ($profile_image && $profile_image['error'] !== UPLOAD_ERR_OK && $profile_image['error'] !== UPLOAD_ERR_NO_FILE) {
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
        // Start a transaction
        $conn->beginTransaction();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM public.users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $conn->rollBack();
            $response = ['status' => 'error', 'message' => 'An account with this email already exists.'];
            echo json_encode($response);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO public.users (full_name, email, password, phone, role, profile_image, account_status) VALUES (?, ?, ?, ?, ?, ?, 'active') RETURNING id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$full_name, $email, $hashedPassword, $phone, $role, $profile_imagePath]);
        $new_user_id = $stmt->fetchColumn();

        if ($role === 'worker') {
            // Insert into worker_profiles
            $stmt_profile = $conn->prepare(
                "INSERT INTO public.worker_profiles (user_id, bio, experience_years, hourly_rate) VALUES (?, ?, ?, ?)"
            );
            $stmt_profile->execute([$new_user_id, $bio, $experience_years, $hourly_rate]);

            // Insert into worker_services
            if (!empty($selected_services)) {
                $sql_worker_services = "INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)";
                $stmt_services = $conn->prepare($sql_worker_services);
                foreach ($selected_services as $service_id) {
                    $stmt_services->execute([$new_user_id, $service_id]);
                }
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Set cookies after a successful commit
        setcookie("encrypted_user_id", encrypt_id($new_user_id), time() + 86400, "/");
        setcookie("encrypted_user_role", encrypt_id($role), time() + 86400, "/");
        setcookie("encrypted_user_name", encrypt_id($full_name), time() + 86400, "/");
        setcookie("encrypted_profile_image", encrypt_id($profile_imagePath ?? ''), time() + 86400, "/");

        $redirect_url = 'dashboard.php?action=new_user';
        $response = ['status' => 'success', 'message' => 'Account created! Redirecting...', 'redirect' => $redirect_url];

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
        $response = ['status' => 'error', 'message' => 'A database error occurred during registration.'];
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
                        <div id="step-1" data-step="1">
                            <div class="form-group">
                                <i class="fas fa-user form-icon"></i>
                                <input type="text" class="form-control-custom" name="full_name" placeholder="Full Name"
                                    required>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-envelope form-icon"></i>
                                <input type="email" class="form-control-custom" name="email" placeholder="Email Address"
                                    required>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-lock form-icon"></i>
                                <input type="password" class="form-control-custom" name="password" id="password"
                                    placeholder="Password" required>
                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-phone form-icon"></i>
                                <input type="tel" class="form-control-custom" name="phone" placeholder="Phone Number">
                            </div>
                            <div class="form-group">
                                <i class="fas fa-user-tie form-icon"></i>
                                <select class="form-control-custom" name="role" id="roleSelect" required>
                                    <option value="" disabled selected>Select a role...</option>
                                    <option value="customer">Customer (I need a service)</option>
                                    <option value="worker">Worker (I provide a service)</option>
                                </select>
                            </div>
                            <label for="profile_image" class="form-label">Profile Picture (Optional)</label>
                            <div class="file-drop-area mb-4">
                                <i class="fas fa-cloud-upload-alt file-icon"></i>
                                <span class="file-msg">Drag & drop a file or click to select</span>
                                <input type="file" class="file-input" id="profile_image" name="profile_image"
                                    accept="image/*">
                                <div class="file-preview-container" id="filePreviewContainer"></div>
                            </div>

                            <div class="d-grid">
                                <button type="button" class="btn btn-signup" id="nextButton">Next</button>
                            </div>
                            <div class="text-center mt-4">
                                <p>Already have an account? <a class="signup-link" href="/dailyfix/login.php">Log In</a>
                                </p>
                            </div>
                        </div>

                        <div id="step-2" data-step="2" style="display:none;">
                            <h3>Your Professional Details</h3>
                            <div class="form-group">
                                <i class="fas fa-briefcase form-icon"></i>
                                <label for="experience_years">Years of Experience</label>
                                <input type="number" class="form-control-custom" name="experience_years"
                                    id="experience_years" min="0" step="1" placeholder="e.g., 5">
                            </div>
                            <div class="form-group">
                                <i class="fas fa-dollar-sign form-icon"></i>
                                <label for="hourly_rate">Hourly Rate ($)</label>
                                <input type="number" class="form-control-custom" name="hourly_rate" id="hourly_rate"
                                    min="0" step="0.50" placeholder="e.g., 25.50">
                            </div>
                            <div class="form-group">
                                <i class="fas fa-pen form-icon"></i>
                                <label for="bio">Bio / Introduction</label>
                                <textarea class="form-control-custom" name="bio" id="bio" rows="5"
                                    placeholder="Tell customers about yourself, your skills, and your experience..."></textarea>
                            </div>
                            <div class="form-section">
                                <h3>Services You Offer</h3>
                                <p>Select all the services you are able to provide.</p>
                                <div class="services-category-list">
                                    <?php foreach ($groupedSubServices as $service_name => $sub_services_list): ?>
                                    <div class="service-category-group">
                                        <h4><i
                                                class="<?php echo htmlspecialchars($sub_services_list[0]['icon']); ?>"></i>
                                            <?php echo htmlspecialchars($service_name); ?></h4>
                                        <div class="services-checkbox-grid">
                                            <?php foreach ($sub_services_list as $sub): ?>
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="service-<?php echo $sub['id']; ?>"
                                                    name="services[]" value="<?php echo $sub['id']; ?>">
                                                <label for="service-<?php echo $sub['id']; ?>">
                                                    <?php echo htmlspecialchars($sub['name']); ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-signup" id="submitButton">Create Account</button>
                            </div>
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