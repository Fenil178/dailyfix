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
        $sql = "INSERT INTO public.users (full_name, email, password, phone, role, profile_image, account_status, latitude, longitude, address_line1, address_line2, city, pincode, state) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?) RETURNING id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$full_name, $email, $hashedPassword, $phone, $role, $profile_imagePath, $latitude, $longitude, $address_line1, $address_line2, $city, $pincode, $state]);
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
            
            // Link sub-service items to worker (if any)
            if (!empty($selected_sub_service_items)) {
                $sql_worker_items = "INSERT INTO public.worker_sub_service_items (user_id, sub_service_item_id) VALUES (?, ?)";
                $stmt_items = $conn->prepare($sql_worker_items);
                foreach ($selected_sub_service_items as $item_id) {
                    $stmt_items->execute([$new_user_id, $item_id]);
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
$indian_states_cities = [
    "Andaman and Nicobar Islands" => ["Port Blair"],
    "Andhra Pradesh" => ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool"],
    "Arunachal Pradesh" => ["Itanagar", "Tawang"],
    "Assam" => ["Guwahati", "Dibrugarh", "Silchar"],
    "Bihar" => ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur"],
    "Chandigarh" => ["Chandigarh"],
    "Chhattisgarh" => ["Raipur", "Bhilai", "Bilaspur"],
    "Dadra and Nagar Haveli and Daman and Diu" => ["Daman", "Silvassa"],
    "Delhi" => ["New Delhi", "North Delhi", "South Delhi", "West Delhi", "East Delhi"],
    "Goa" => ["Panaji", "Margao", "Vasco da Gama"],
    "Gujarat" => ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar"],
    "Haryana" => ["Faridabad", "Gurugram", "Panipat", "Ambala"],
    "Himachal Pradesh" => ["Shimla", "Manali", "Dharamshala"],
    "Jammu and Kashmir" => ["Srinagar", "Jammu", "Anantnag"],
    "Jharkhand" => ["Ranchi", "Jamshedpur", "Dhanbad"],
    "Karnataka" => ["Bengaluru", "Mysuru", "Hubballi-Dharwad", "Mangaluru"],
    "Kerala" => ["Thiruvananthapuram", "Kochi", "Kozhikode", "Thrissur"],
    "Ladakh" => ["Leh", "Kargil"],
    "Lakshadweep" => ["Kavaratti"],
    "Madhya Pradesh" => ["Indore", "Bhopal", "Jabalpur", "Gwalior"],
    "Maharashtra" => ["Mumbai", "Pune", "Nagpur", "Thane", "Nashik"],
    "Manipur" => ["Imphal"],
    "Meghalaya" => ["Shillong"],
    "Mizoram" => ["Aizawl"],
    "Nagaland" => ["Kohima", "Dimapur"],
    "Odisha" => ["Bhubaneswar", "Cuttack", "Rourkela"],
    "Puducherry" => ["Puducherry"],
    "Punjab" => ["Ludhiana", "Amritsar", "Jalandhar"],
    "Rajasthan" => ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Bikaner"],
    "Sikkim" => ["Gangtok"],
    "Tamil Nadu" => ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli"],
    "Telangana" => ["Hyderabad", "Warangal", "Nizamabad"],
    "Tripura" => ["Agartala"],
    "Uttar Pradesh" => ["Lucknow", "Kanpur", "Ghaziabad", "Agra", "Varanasi"],
    "Uttarakhand" => ["Dehradun", "Haridwar", "Roorkee"],
    "West Bengal" => ["Kolkata", "Asansol", "Siliguri", "Durgapur"]
];
$states = array_keys($indian_states_cities);
sort($states);

// PHP to fetch services for the form
$mainServices = [];
$groupedSubServices = [];
$subServiceItems = [];
try {
    // Fetch main services
    $stmt = $conn->query("SELECT id, name, icon FROM public.services ORDER BY name");
    $mainServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch sub-services
    $stmt = $conn->query("SELECT s.id AS main_service_id, s.name AS service_name, ss.id, ss.name AS sub_service_name, ss.icon FROM public.sub_services ss JOIN public.services s ON ss.service_id = s.id ORDER BY s.name, ss.name");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $groupedSubServices[$row['service_name']][] = ['id' => $row['id'], 'name' => $row['sub_service_name'], 'icon' => $row['icon'], 'main_service_id' => $row['main_service_id']];
    }
    
    // Fetch sub-service items
    $stmt = $conn->query("SELECT ssi.sub_service_id, ssi.id, ssi.name, ssi.icon FROM public.sub_service_items ssi JOIN public.sub_services ss ON ssi.sub_service_id = ss.id ORDER BY ss.name, ssi.name");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $subServiceItems[$row['sub_service_id']][] = ['id' => $row['id'], 'name' => $row['name'], 'icon' => $row['icon']];
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
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

                    <div class="step-indicator-wrapper" id="worker-indicator" style="display: none;">
                        <ul class="step-indicator">
                            <li class="step-indicator-item" data-step-target="step-key"><span>1</span>Verification</li>
                            <li class="step-indicator-item" data-step-target="step-register-part1"><span>2</span>Account</li>
                            <li class="step-indicator-item" data-step-target="step-register-part2"><span>3</span>Details</li>
                            <li class="step-indicator-item" data-step-target="step-main-services"><span>4</span>Services</li>
                            <li class="step-indicator-item" data-step-target="step-sub-services"><span>5</span>Sub-Services</li>
                            <li class="step-indicator-item" data-step-target="step-sub-service-items"><span>6</span>Sub-Items</li>
                            <li class="step-indicator-item" data-step-target="step-location"><span>7</span>Location</li>
                        </ul>
                    </div>
                    
                    <div class="step-indicator-wrapper" id="customer-indicator" style="display: none;">
                        <ul class="step-indicator">
                            <li class="step-indicator-item" data-step-target="step-register-part1"><span>1</span>Account</li>
                            <li class="step-indicator-item" data-step-target="step-location"><span>2</span>Location</li>
                        </ul>
                    </div>

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
                    
                    <form id="signupForm" method="POST" enctype="multipart/form-data" novalidate>
                    <div class="step" id="step-key">
                        <button type="button" class="back-btn" data-target="step-role"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Worker Verification</h2>
                        <p class="subtitle">Please enter the 8-character key provided by your administrator.</p>
                        <div id="keyForm">
                            <div class="form-group">
                                <i class="fas fa-key form-icon"></i>
                                <input type="text" class="form-control-custom" name="worker_key_verify" id="worker_key_input" placeholder="e.g., S9M1-17FR" required>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-signup" id="verifyKeyBtn">Verify Key</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step" id="step-register-part1">
                        <button type="button" class="back-btn" id="register-back-btn"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Create Your <span id="role-title"></span> Account</h2>
                        <p class="subtitle">Please fill in your details to get started.</p>
                        
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
                            <button type="button" class="btn btn-signup next-btn" data-target="step-register-part2" id="account-details-next-btn">Next</button>
                        </div>
                    </div>

                    <div class="step" id="step-register-part2">
                        <button type="button" class="back-btn" data-target="step-register-part1"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Your Professional Details</h2>
                        <p class="subtitle">Tell us more about your expertise to help customers find you.</p>
                        
                        <div class="form-group">
                            <label for="bio">Professional Bio</label>
                            <textarea class="form-control-custom" name="bio" id="bio" rows="3" placeholder="Describe your skills and services..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="experience_years">Years of Experience</label>
                                    <input type="number" class="form-control-custom" name="experience_years" id="experience_years" placeholder="e.g., 5" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hourly_rate">Hourly Rate (₹)</label>
                                    <input type="number" step="0.01" class="form-control-custom" name="hourly_rate" id="hourly_rate" placeholder="e.g., 25.50" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="button" class="btn btn-signup next-btn" data-target="step-main-services">Next</button>
                        </div>
                    </div>

                    <div class="step" id="step-main-services">
                        <button type="button" class="back-btn" data-target="step-register-part2"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Main Services</h2>
                        <p class="subtitle">Select the main categories of services you offer.</p>
                        <div class="services-category-list">
                            <?php foreach ($mainServices as $service): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="main-service-<?= $service['id'] ?>" name="main_services[]" value="<?= $service['id'] ?>">
                                    <label for="main-service-<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="button" class="btn btn-signup next-btn" data-target="step-sub-services">Next</button>
                        </div>
                    </div>

                    <div class="step" id="step-sub-services">
                        <button type="button" class="back-btn" data-target="step-main-services"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Sub Services</h2>
                        <p class="subtitle">Select the specific services you offer.</p>
                        <div id="sub-services-container"></div>
                        <div class="d-grid mt-4">
                             <button type="button" class="btn btn-signup next-btn" data-target="step-sub-service-items">Next</button>
                        </div>
                    </div>
                    
                    <div class="step" id="step-sub-service-items">
                        <button type="button" class="back-btn" data-target="step-sub-services"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Service Items</h2>
                        <p class="subtitle">Select the specific tasks you are able to perform.</p>
                        <div id="sub-service-items-container"></div>
                        <div class="d-grid mt-4">
                             <button type="button" class="btn btn-signup next-btn" data-target="step-location">Next</button>
                        </div>
                    </div>

                    <div class="step" id="step-location">
                        <button type="button" class="back-btn" id="location-back-btn"><i class="fas fa-arrow-left"></i> Back</button>
                        <h2>Set Your Location</h2>
                        <p class="subtitle">Drag the marker or type your pincode to begin.</p>
                        
                        <div id="map"></div>
                        
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="text" class="form-control-custom" name="address_line1" id="address_line1" placeholder="House No. & Building Name" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="text" class="form-control-custom" name="address_line2" id="address_line2" placeholder="Road, Area, Colony" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <select class="form-control-custom" name="state" id="state" required>
                                        <option value="" disabled selected>Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <select class="form-control-custom" name="city" id="city" required>
                                        <option value="" disabled selected>Select State First</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="text" class="form-control-custom" name="pincode" id="pincode" placeholder="Pincode" required>
                                    <div id="pincode-spinner" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none; position: absolute; right: 10px; top: 12px;">
                                      <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-2">
                            <button type="submit" class="btn btn-signup">Create Account</button>
                        </div>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        const mainServices = <?php echo json_encode($mainServices); ?>;
        const groupedSubServices = <?php echo json_encode($groupedSubServices); ?>;
        const subServiceItems = <?php echo json_encode($subServiceItems); ?>;
        const citiesByState = <?php echo json_encode($indian_states_cities); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="/dailyfix/assets/js/signup.js"></script>
</body>
</html>