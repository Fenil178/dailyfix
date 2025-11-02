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
    $selected_services = $_POST['services'] ?? [];
    $item_prices = $_POST['prices'] ?? []; // Array of prices for sub-services
    $selected_sub_service_items = $_POST['sub_service_items'] ?? [];

    // --- Location Fields ---
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $address_line1 = $_POST['address_line1'] ?? null;
    $address_line2 = $_POST['address_line2'] ?? null;
    $city = $_POST['city'] ?? null;
    $pincode = $_POST['pincode'] ?? null;
    $state = $_POST['state'] ?? null;

    // --- Validation ---
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }
    if ($role === 'worker' && empty($worker_key)) {
        echo json_encode(['status' => 'error', 'message' => 'Worker key is missing. Please go back and re-verify.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // --- File Upload Handling ---
        if ($profile_image && $profile_image['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/uploads/profile_images/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExtension;
            $profile_imagePath = "uploads/profile_images/" . $newFileName;
            
            if (!move_uploaded_file($profile_image['tmp_name'], $uploadDir . $newFileName)) {
                $conn->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload profile image. Please check server permissions.']);
                exit;
            }
        }

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
        $sql = "INSERT INTO public.users (full_name, email, password, phone, role, profile_image, account_status, latitude, longitude, address_line1, address_line2, city, pincode, state) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?) RETURNING id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$full_name, $email, $hashedPassword, $phone, $role, $profile_imagePath, $latitude, $longitude, $address_line1, $address_line2, $city, $pincode, $state]);
        $new_user_id = $stmt->fetchColumn();

        if ($role === 'worker') {
            // Insert worker profile details
            $stmt_profile = $conn->prepare("INSERT INTO public.worker_profiles (user_id, bio, experience_years) VALUES (?, ?, ?)");
            $stmt_profile->execute([$new_user_id, $bio, $experience_years]);
            
            // Mark the key as used
            $stmt_key_update = $conn->prepare("UPDATE public.worker_keys SET is_used = true, used_by_worker_id = ? WHERE access_key = ?");
            $stmt_key_update->execute([$new_user_id, $worker_key]);

            // Link services to worker
            if (!empty($selected_services)) {
                $sql_worker_services = "INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)";
                $stmt_services = $conn->prepare($sql_worker_services);
                foreach ($selected_services as $service_id) {
                    $stmt_services->execute([$new_user_id, $service_id]);
                }
            }
            
            // Link sub-service items to worker with their prices
            if (!empty($selected_sub_service_items)) {
                $sql_worker_items = "INSERT INTO public.worker_sub_service_items (user_id, sub_service_item_id, price) VALUES (?, ?, ?)";
                $stmt_items = $conn->prepare($sql_worker_items);
                foreach ($selected_sub_service_items as $item_id) {
                    $price = $item_prices[$item_id] ?? 0.00;
                    $stmt_items->execute([$new_user_id, $item_id, $price]);
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

// Data for State and City Dropdowns
$indian_states_cities = [ "Gujarat" => ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar"]];
$states = array_keys($indian_states_cities);
sort($states);

// PHP to fetch services for the form
$mainServices = []; $groupedSubServices = []; $subServiceItems = [];
try {
    $stmt = $conn->query("SELECT id, name, icon FROM public.services ORDER BY name");
    $mainServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->query("SELECT s.id AS main_service_id, s.name AS service_name, ss.id, ss.name AS sub_service_name, ss.icon FROM public.sub_services ss JOIN public.services s ON ss.service_id = s.id ORDER BY s.name, ss.name");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) { $groupedSubServices[$row['service_name']][] = ['id' => $row['id'], 'name' => $row['sub_service_name'], 'icon' => $row['icon'], 'main_service_id' => $row['main_service_id']]; }
    $stmt = $conn->query("SELECT ssi.sub_service_id, ssi.id, ssi.name, ssi.icon FROM public.sub_service_items ssi JOIN public.sub_services ss ON ssi.sub_service_id = ss.id ORDER BY ss.name, ssi.name");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) { $subServiceItems[$row['sub_service_id']][] = ['id' => $row['id'], 'name' => $row['name'], 'icon' => $row['icon']]; }
} catch (PDOException $e) { error_log("Failed to fetch services for setup page: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Create Your Account - DailyFix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link href="/dailyfix/assets/css/signup.css" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container" id="main-logo-container">
                <div class="logo">
                    <div class="logo-inner"><img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo"></div>
                </div>
                <h1 class="login-title">Create an Account</h1>
                <p class="login-subtitle">Join DailyFix to manage your services with ease.</p>
            </div>
            
            <div id="signup-alert-placeholder"></div>

            <div class="step-indicator-wrapper" id="worker-indicator" style="display: none;">
                <ul class="step-indicator">
                    <li class="step-indicator-item" data-step-target="step-key"><span>1</span>Verification</li>
                    <li class="step-indicator-item" data-step-target="step-register-part1"><span>2</span>Account</li>
                    <li class="step-indicator-item" data-step-target="step-register-part2"><span>3</span>Details</li>
                    <li class="step-indicator-item" data-step-target="step-main-services"><span>4</span>Services</li>
                    <li class="step-indicator-item" data-step-target="step-sub-services"><span>5</span>Sub-Services</li>
                    <li class="step-indicator-item" data-step-target="step-sub-service-items"><span>6</span>Sub-Items</li>
                    <li class="step-indicator-item" data-step-target="step-set-prices"><span>7</span>Set Prices</li>
                    <li class="step-indicator-item" data-step-target="step-location"><span>8</span>Location</li>
                </ul>
            </div>
            
            <div class="step-indicator-wrapper" id="customer-indicator" style="display: none;">
                <ul class="step-indicator">
                    <li class="step-indicator-item" data-step-target="step-register-part1"><span>1</span>Account</li>
                    <li class="step-indicator-item" data-step-target="step-location"><span>2</span>Location</li>
                </ul>
            </div>
            
            <div class="step active" id="step-role">
                <h2 class="step-title">Join as a Customer or Worker</h2>
                <p class="step-subtitle">First, tell us who you are.</p>
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
            </div>
            
            <form id="signupForm" method="POST" enctype="multipart/form-data" novalidate>
                <div class="step" id="step-key">
                    <button type="button" class="back-btn" data-target="step-role"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Worker Verification</h2>
                    <p class="step-subtitle">Please enter the 8-character key provided by your administrator.</p>
                    <div class="form-group">
                        <label class="form-label">Worker Key</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key input-icon"></i>
                            <input type="text" class="form-control" name="worker_key_verify" id="worker_key_input" placeholder="e.g., S9M1-17FR" required>
                        </div>
                    </div>
                    <button type="button" class="btn-login" id="verifyKeyBtn">Verify Key</button>
                </div>
                
                <div class="step" id="step-register-part1">
                    <button type="button" class="back-btn" id="register-back-btn"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Create Your <span id="role-title"></span> Account</h2>
                    <p class="step-subtitle">Please fill in your details to get started.</p>
                    
                    <input type="hidden" name="role" id="role-hidden-input">
                    <input type="hidden" name="worker_key" id="worker-key-hidden-input">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Full Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Email Address" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" class="form-control" name="phone" id="phone" placeholder="Phone Number" required>
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                        <div class="file-drop-area">
                            <i class="fas fa-cloud-upload-alt file-icon"></i>
                            <span class="file-msg">Drag & drop your image here, or click to browse.</span>
                            <input type="file" class="file-input" name="profile_image" id="profile_image" accept="image/*">
                            <div id="filePreviewContainer" class="file-preview-container"></div>
                        </div>
                    </div>
                    <button type="button" class="btn-login next-btn mt-3" data-target="step-register-part2" id="account-details-next-btn">Next</button>
                </div>

                <div class="step" id="step-register-part2">
                     <button type="button" class="back-btn" data-target="step-register-part1"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Your Professional Details</h2>
                    <p class="step-subtitle">Tell us more about your expertise to help customers find you.</p>
                    
                    <div class="form-group">
                        <label class="form-label" for="bio">Professional Bio</label>
                        <textarea class="form-control" name="bio" id="bio" rows="3" placeholder="Describe your skills and services..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label" for="experience_years">Years of Experience</label>
                                <input type="number" class="form-control" name="experience_years" id="experience_years" placeholder="e.g., 5" required>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-login next-btn mt-3" data-target="step-main-services">Next</button>
                </div>

                <div class="step" id="step-main-services">
                    <button type="button" class="back-btn" data-target="step-register-part2"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Main Services</h2>
                    <p class="step-subtitle">Select the main categories of services you offer.</p>
                    <div class="services-category-list">
                        <?php foreach ($mainServices as $service): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="main-service-<?= $service['id'] ?>" name="main_services[]" value="<?= $service['id'] ?>">
                                <label for="main-service-<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-login next-btn mt-3" data-target="step-sub-services">Next</button>
                </div>

                <div class="step" id="step-sub-services">
                    <button type="button" class="back-btn" data-target="step-main-services"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Sub Services</h2>
                    <p class="step-subtitle">Select the specific services you offer.</p>
                    <div id="sub-services-container"></div>
                     <button type="button" class="btn-login next-btn mt-3" data-target="step-sub-service-items">Next</button>
                </div>
                
                <div class="step" id="step-sub-service-items">
                    <button type="button" class="back-btn" data-target="step-sub-services"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Service Items</h2>
                    <p class="step-subtitle">Select the specific tasks you are able to perform.</p>
                    <div id="sub-service-items-container"></div>
                     <button type="button" class="btn-login next-btn mt-3" data-target="step-set-prices">Next</button>
                </div>

                <div class="step" id="step-set-prices">
                    <button type="button" class="back-btn" data-target="step-sub-service-items"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Set Your Prices</h2>
                    <p class="step-subtitle">Set a price for each sub-service you offer.</p>
                    <div id="price-setting-container"></div>
                    <button type="button" class="btn-login next-btn mt-3" data-target="step-location">Next</button>
                </div>

                <div class="step" id="step-location">
                    <button type="button" class="back-btn" id="location-back-btn" data-target="step-set-prices"><i class="fas fa-arrow-left"></i> Back</button>
                    <h2 class="step-title">Set Your Location</h2>
                    <p class="step-subtitle">Drag the marker or type your pincode to begin.</p>
                    
                    <div id="map"></div>
                    
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="address_line1" id="address_line1" placeholder="House No. & Building Name" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <input type="text" class="form-control" name="address_line2" id="address_line2" placeholder="Road, Area, Colony" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <select class="form-control" name="state" id="state" required>
                                    <option value="" disabled selected>Select State</option>
                                    <?php foreach ($states as $state): ?>
                                        <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <select class="form-control" name="city" id="city" required>
                                    <option value="" disabled selected>Select State First</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <input type="text" class="form-control" name="pincode" id="pincode" placeholder="Pincode" required>
                                    <div id="pincode-spinner" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none; position: absolute; right: 10px; top: 12px;">
                                      <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-login mt-2">Create Account</button>
                </div>
            </form>

            <div id="bottom-link-container">
                <div class="divider">
                    <span>Already have an account?</span>
                </div>
                <div class="signup-text">
                    <a href="/dailyfix/login.php">Log In Instead →</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        const mainServices = <?php echo json_encode($mainServices); ?>;
        const groupedSubServices = <?php echo json_encode($groupedSubServices); ?>;
        const subServiceItems = <?php echo json_encode($subServiceItems); ?>;
        const citiesByState = <?php echo json_encode($indian_states_cities); ?>;
document.addEventListener('DOMContentLoaded', () => {
    const stepIndicatorWrapper = document.querySelector('.step-indicator-wrapper');

    function scrollToActiveStep() {
        if (!stepIndicatorWrapper) return;

        const activeStep = stepIndicatorWrapper.querySelector('.step-indicator-item.active');

        if (activeStep) {
            // Calculate the left position of the active step relative to the wrapper
            const activeStepLeft = activeStep.offsetLeft;
            const activeStepWidth = activeStep.offsetWidth;
            const wrapperWidth = stepIndicatorWrapper.offsetWidth;

            // Calculate the scroll position to center the active step (approximately)
            const scrollPosition = activeStepLeft - (wrapperWidth / 2) + (activeStepWidth / 2);

            // Scroll the wrapper smoothly
            stepIndicatorWrapper.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });
        }
    }

    // Call it initially in case a step is active on page load
    scrollToActiveStep();

    // You'll need to call this function whenever your active step changes.
    // For example, if you have a 'next' button:
    // const nextButton = document.getElementById('next-step-button'); // Replace with your button ID
    // nextButton.addEventListener('click', () => {
    //     // ... logic to change active step ...
    //     scrollToActiveStep(); // Call after updating the active class
    // });

    // Or, if you have a general function to update steps:
    // function updateStep(newStepIndex) {
    //    // ... remove 'active' from old step, add 'active' to new step ...
    //    scrollToActiveStep();
    // }
});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/dailyfix/assets/js/signup.js"></script>
</body>

</html>