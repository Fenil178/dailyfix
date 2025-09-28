<?php
include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

/**
 * @var string $role The user's role ('customer' or 'worker').
 * @var int    $userId The ID of the logged-in user.
 * @var string $userName The full name of the logged-in user.
 */

// Handle success messages from URL
$successMessage = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'details_updated':
            $successMessage = 'Personal details updated successfully!';
            break;
        case 'professional_updated':
            $successMessage = 'Professional profile updated successfully!';
            break;
        case 'location_updated':
            $successMessage = 'Location updated successfully!';
            break;
        case 'services_updated':
            $successMessage = 'Services updated successfully!';
            break;
        case 'availability_updated': // Added this case
            $successMessage = 'Availability updated successfully!';
            break;
    }
}

// Fetch user data based on role
$userData = null;
$workerProfile = null;
$services = [];
$subServices = [];
$subServiceItems = [];
$workerServiceIds = [];
$workerSubServiceItemIds = [];

try {
    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['update_user_details'])) {
            // Update personal information
            $stmt = $conn->prepare("UPDATE public.users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$_POST['full_name'], $_POST['phone'], $userId]);
            // Redirect with success message and hash
            header("Location: profile.php?success=details_updated#details");
            exit;
        }

        if (isset($_POST['update_worker_profile'])) {
            // Update professional profile
            $stmt = $conn->prepare("UPDATE public.worker_profiles SET bio = ?, experience_years = ?, hourly_rate = ? WHERE user_id = ?");
            $stmt->execute([$_POST['bio'], $_POST['experience_years'], $_POST['hourly_rate'], $userId]);
            // Redirect with success message and hash
            header("Location: profile.php?success=professional_updated#professional");
            exit;
        }
    }

    // Fetch basic user data
    $stmt = $conn->prepare("SELECT full_name, email, phone, profile_image, address_line1, address_line2, city, pincode, state, latitude, longitude FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user is a worker, fetch their specific profile and services
    if ($role === 'worker') {
        $stmt = $conn->prepare("SELECT bio, experience_years, hourly_rate FROM public.worker_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch all services
        $services = $conn->query("SELECT id, name, icon FROM public.services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all sub-services
        $subServices = $conn->query("SELECT id, service_id, name, icon FROM public.sub_services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all sub-service items
        $subServiceItems = $conn->query("SELECT id, sub_service_id, name FROM public.sub_service_items ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch worker's selected sub-services and items
        $stmt = $conn->prepare("SELECT sub_service_id FROM public.worker_services WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerServiceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $conn->prepare("SELECT sub_service_item_id FROM public.worker_sub_service_items WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerSubServiceItemIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    error_log("Profile page error: " . $e->getMessage());
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/profile.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/profile_location.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <script defer src="/dailyfix/assets/js/worker_availability.js"></script>
    <style>
        /* Additional CSS for the multi-step service form */
        .step { display: none; }
        .step.active { display: block; }
        .services-category-group { margin-bottom: 1.5rem; }
        .service-category-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .sub-service-group { margin-left: 1.5rem; }
        .sub-service-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        .form-actions .btn {
            width: 48%;
        }
        .services-list-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 1rem;
        }

        /* Improved Button Styles */
        .btn-primary.disabled,
        .btn-primary:disabled {
            background-color: #a0a0a0;
            cursor: not-allowed;
            color: #e0e0e0;
            box-shadow: none;
            transform: none;
        }

        .btn-primary.disabled:hover,
        .btn-primary:disabled:hover {
            background-color: #a0a0a0;
            color: #e0e0e0;
        }
    </style>
</head>

<body>
    <main class="page-content">
        <div class="profile-page-container">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($userData['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>"
                    alt="Profile Avatar" class="profile-header-avatar">
                <h1><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                <p><?php echo htmlspecialchars(ucfirst($role)); ?></p>
            </div>

            <div class="tab-nav">
                <button class="tab-link active" data-tab="details">My Details</button>
                <?php if ($role === 'worker'): ?>
                <button class="tab-link" data-tab="professional">Professional Profile</button>
                <button class="tab-link" data-tab="availability">My Availability</button>
                <button class="tab-link" data-tab="services">My Services</button>
                <?php else: // Customer ?>
                <button class="tab-link" data-tab="history">Booking History</button>
                <?php endif; ?>
                <button class="tab-link" data-tab="location">Location</button>
            </div>

            <div id="details" class="tab-content active">
                <div class="form-section">
                    <?php if ($successMessage && strpos($_GET['success'], 'details') !== false): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>Personal Information</h3>
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="update_user_details" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name"
                                    value="<?php echo htmlspecialchars($userData['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                    value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($userData['phone']); ?>">
                        </div>
                        <button type="submit" class="submit-btn">Save Personal Info</button>
                    </form>
                </div>
            </div>

            <?php if ($role === 'worker'): ?>
            <div id="professional" class="tab-content">
                <div class="form-section">
                    <?php if ($successMessage && strpos($_GET['success'], 'professional') !== false): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>Professional Profile</h3>
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="update_worker_profile" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="experience_years">Years of Experience</label>
                                <input type="number" id="experience_years" name="experience_years"
                                    value="<?php echo htmlspecialchars($workerProfile['experience_years']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate ($)</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" step="0.50"
                                    value="<?php echo htmlspecialchars($workerProfile['hourly_rate']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio / Introduction</label>
                            <textarea id="bio" name="bio"
                                rows="6"><?php echo htmlspecialchars($workerProfile['bio']); ?></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Save Professional Info</button>
                    </form>
                </div>
            </div>

            <div id="availability" class="tab-content">
                <div class="form-section">
                     <?php if ($successMessage && strpos($_GET['success'], 'availability') !== false): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>Set My Availability</h3>
                    <p>Click on a date to mark your available time slots.</p>
                    <div id="calendar-container">
                        <div id="calendar-days" class="calendar-grid"></div>
                    </div>

                    <div id="time-slot-container" style="display: none; margin-top: 2rem;">
                        <h4>Time Slots for <span id="selected-date-text"></span></h4>
                        <div id="slots-grid" class="slots-grid"></div>
                        <div class="toggle-control-container">
                            <label for="save-scope-toggle" class="toggle-label">
                                <span id="toggle-text">Save for this Day</span>
                            </label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="save-scope-toggle">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button class="submit-btn" id="save-final-btn" style="width: 30%; margin-top: 2rem;">Save
                            Availability</button>
                    </div>
                </div>
            </div>

            <div id="services" class="tab-content">
                <div class="form-section">
                    <?php if ($successMessage && strpos($_GET['success'], 'services') !== false): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>My Services</h3>
                    <form id="service-selection-form" action="/dailyfix/api/update_worker_profile_services.php" method="POST">
                        
                        <div id="service-step-1" class="step active">
                            <h4>1. Main Services</h4>
                            <p>Select the main categories you specialize in.</p>
                            <div id="main-services-container" class="services-checkbox-grid">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary back-btn disabled" disabled>Back</button>
                                <button type="button" class="btn btn-primary next-btn disabled" disabled data-next-step="service-step-2">Next</button>
                            </div>
                        </div>

                        <div id="service-step-2" class="step">
                            <h4>2. Sub-services</h4>
                            <p>Select the sub-services you offer within your chosen categories.</p>
                            <div id="sub-services-container" class="services-list-container"></div>
                             <div class="form-actions">
                                <button type="button" class="btn btn-secondary back-btn" data-prev-step="service-step-1">Back</button>
                                <button type="button" class="btn btn-primary next-btn disabled" disabled data-next-step="service-step-3">Next</button>
                            </div>
                        </div>

                        <div id="service-step-3" class="step">
                            <h4>3. Service Items</h4>
                            <p>Select the specific tasks you can perform.</p>
                            <div id="sub-service-items-container" class="services-list-container"></div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary back-btn" data-prev-step="service-step-2">Back</button>
                                <button type="submit" class="btn btn-primary">Save Selections</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'customer'): ?>
            <div id="history" class="tab-content">
                <div class="form-section">
                    <h3>Booking History</h3>
                    <p>A summary of your recent bookings. For more details, visit the "My Bookings" page.</p>
                    <a href="/dailyfix/customer/bookings.php" class="submit-btn"
                        style="text-align: center; text-decoration: none;">View All My Bookings</a>
                </div>
            </div>
            <?php endif; ?>

            <div id="location" class="tab-content">
                <div class="form-section">
                    <?php if ($successMessage && strpos($_GET['success'], 'location') !== false): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>My Location</h3>
                    <form action="/dailyfix/api/update_location.php" method="POST">
                        <div id="map"></div>
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($userData['latitude']); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($userData['longitude']); ?>">

                        <div class="address-fields-container">
                            <div class="form-group">
                                <label for="address_line1">Address Line 1</label>
                                <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($userData['address_line1']); ?>" placeholder="House No. & Building Name">
                            </div>
                            <div class="form-group">
                                <label for="address_line2">Address Line 2</label>
                                <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($userData['address_line2']); ?>" placeholder="Road, Area, Colony">
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <select id="state" name="state">
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?php echo htmlspecialchars($state); ?>" <?php if ($userData['state'] === $state) echo 'selected'; ?>><?php echo htmlspecialchars($state); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <select id="city" name="city">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="pincode">Pincode</label>
                                <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($userData['pincode']); ?>">
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Save Location</button>
                    </form>
                </div>
            </div>

        </div>
    </main>
    <script>
        const citiesByState = <?php echo json_encode($indian_states_cities); ?>;
        const userData = <?php echo json_encode($userData); ?>;

        // PHP data for services
        const allServices = <?php echo json_encode($services); ?>;
        const allSubServices = <?php echo json_encode($subServices); ?>;
        const allSubServiceItems = <?php echo json_encode($subServiceItems); ?>;
        const workerServiceIds = <?php echo json_encode($workerServiceIds); ?>.map(id => parseInt(id));
        const workerSubServiceItemIds = <?php echo json_encode($workerSubServiceItemIds); ?>.map(id => parseInt(id));


        document.addEventListener('DOMContentLoaded', function () {
            // JavaScript for auto-dismissing the success message
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.classList.add('fade-out');
                    setTimeout(() => {
                        successAlert.remove();
                    }, 500); 
                }, 5000);
            }

            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            const stateDropdown = document.getElementById('state');
            const cityDropdown = document.getElementById('city');
            const address1Input = document.getElementById('address_line1');
            const address2Input = document.getElementById('address_line2');
            const pincodeInput = document.getElementById('pincode');
            const latInput = document.getElementById('latitude');
            const lonInput = document.getElementById('longitude');
            const servicesTab = document.getElementById('services');
            
            // Service Step elements
            const serviceStep1 = document.getElementById('service-step-1');
            const serviceStep2 = document.getElementById('service-step-2');
            const serviceStep3 = document.getElementById('service-step-3');
            const nextBtns = servicesTab ? servicesTab.querySelectorAll('.next-btn') : [];
            const backBtns = servicesTab ? servicesTab.querySelectorAll('.back-btn') : [];
            const mainServicesContainer = servicesTab ? document.getElementById('main-services-container') : null;

            // Map variables
            let map, marker;
            let geocodeTimeout;

            // --- FUNCTION DEFINITIONS ---
            
            function showServiceStep(stepElement) {
                const steps = servicesTab.querySelectorAll('.step');
                steps.forEach(step => step.classList.remove('active'));
                stepElement.classList.add('active');
            }

            function populateMainServices() {
                if (!mainServicesContainer) return;
                mainServicesContainer.innerHTML = '';
                
                // Get the main service IDs corresponding to the worker's selected sub-services
                const workerMainServiceIds = [...new Set(allSubServices
                    .filter(sub => workerServiceIds.includes(sub.id))
                    .map(sub => sub.service_id))];

                allServices.forEach(service => {
                    const isChecked = workerMainServiceIds.includes(service.id) ? 'checked' : '';
                    const serviceHtml = `
                        <div class="checkbox-item">
                            <input type="checkbox" id="main-service-${service.id}" name="main_services[]" value="${service.id}" ${isChecked}>
                            <label for="main-service-${service.id}"><i class="${service.icon}"></i> ${service.name}</label>
                        </div>
                    `;
                    mainServicesContainer.innerHTML += serviceHtml;
                });
                
                // Attach event listener and update button state
                const checkboxes = mainServicesContainer.querySelectorAll('input[type="checkbox"]');
                const nextBtn = serviceStep1.querySelector('.next-btn');
                const updateButtonState = () => {
                    const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    nextBtn.disabled = !isAnyChecked;
                    nextBtn.classList.toggle('disabled', !isAnyChecked);
                };
                checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));
                updateButtonState();
            }

            function populateSubServices() {
                const selectedServiceIds = Array.from(serviceStep1.querySelectorAll('input[type="checkbox"]:checked')).map(cb => parseInt(cb.value));
                const subServicesContainer = document.getElementById('sub-services-container');
                subServicesContainer.innerHTML = '';

                let content = '';
                allServices.forEach(service => {
                    if (selectedServiceIds.includes(service.id)) {
                        const relatedSubServices = allSubServices.filter(sub => sub.service_id === service.id);
                        if (relatedSubServices.length > 0) {
                            content += `
                                <div class="services-category-group">
                                    <h4 class="service-category-title"><i class="${service.icon}"></i> ${service.name}</h4>
                                    <div class="services-checkbox-grid">
                            `;
                            relatedSubServices.forEach(sub => {
                                const isChecked = workerServiceIds.includes(sub.id) ? 'checked' : '';
                                content += `
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="sub-service-${sub.id}" name="services[]" value="${sub.id}" ${isChecked}>
                                        <label for="sub-service-${sub.id}"><i class="${sub.icon}"></i> ${sub.name}</label>
                                    </div>
                                `;
                            });
                            content += `</div></div>`;
                        }
                    }
                });
                subServicesContainer.innerHTML = content || '<p>No sub-services found for selected categories.</p>';
                 
                // Update button state for step 2
                const checkboxes = subServicesContainer.querySelectorAll('input[type="checkbox"]');
                const nextBtn = serviceStep2.querySelector('.next-btn');
                const updateButtonState = () => {
                    const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    nextBtn.disabled = !isAnyChecked;
                    nextBtn.classList.toggle('disabled', !isAnyChecked);
                };
                checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));
                updateButtonState();
            }

            function populateSubServiceItems() {
                const selectedSubServiceIds = Array.from(serviceStep2.querySelectorAll('input[name="services[]"]:checked')).map(cb => parseInt(cb.value));
                const subServiceItemsContainer = document.getElementById('sub-service-items-container');
                subServiceItemsContainer.innerHTML = '';
                
                let content = '';
                allSubServices.forEach(subService => {
                    if (selectedSubServiceIds.includes(subService.id)) {
                        const relatedItems = allSubServiceItems.filter(item => item.sub_service_id === subService.id);
                        if (relatedItems.length > 0) {
                             content += `
                                <div class="services-category-group">
                                    <h4 class="service-category-title"><i class="${subService.icon}"></i> ${subService.name}</h4>
                                    <div class="sub-service-items-grid">
                            `;
                            relatedItems.forEach(item => {
                                const isChecked = workerSubServiceItemIds.includes(item.id) ? 'checked' : '';
                                content += `
                                     <div class="checkbox-item">
                                        <input type="checkbox" id="item-${item.id}" name="sub_service_items[]" value="${item.id}" ${isChecked}>
                                        <label for="item-${item.id}">${item.name}</label>
                                    </div>
                                `;
                            });
                            content += `</div></div>`;
                        }
                    }
                });
                subServiceItemsContainer.innerHTML = content || '<p>No service items found for selected sub-services.</p>';
            }
            
            function initializeMap() {
                const lat = parseFloat(userData.latitude) || 21.1702;
                const lon = parseFloat(userData.longitude) || 72.8311;
                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
            }

            function updateCities() {
                const selectedState = stateDropdown.value;
                const selectedCity = userData.city;
                cityDropdown.innerHTML = '<option value="">Select City</option>';
                if (selectedState && citiesByState[selectedState]) {
                    citiesByState[selectedState].forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        if (selectedCity === city) {
                            option.selected = true;
                        }
                        cityDropdown.appendChild(option);
                    });
                }
            }

            async function geocodeAddress() {
                const address = `${address1Input.value}, ${address2Input.value}, ${cityDropdown.value}, ${stateDropdown.value}, ${pincodeInput.value}`;
                if (address.trim().length < 10) return;

                const query = encodeURIComponent(address);
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${query}&countrycodes=in`;
                
                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        
                        latInput.value = lat;
                        lonInput.value = lon;
                        
                        if (map && marker) {
                            const newLatLng = new L.LatLng(lat, lon);
                            map.setView(newLatLng, 15);
                            marker.setLatLng(newLatLng);
                        }
                    }
                } catch (error) {
                    console.error("Geocoding error:", error);
                }
            }
            
            // --- EVENT LISTENERS ---

            // Handle manual tab clicking
            tabLinks.forEach(link => {
                link.addEventListener('click', () => {
                    const tabId = link.getAttribute('data-tab');
                    activateTab(tabId);
                    // Update the URL hash for a better user experience, without reloading the page
                    history.pushState(null, null, `#${tabId}`);
                });
            });

            // Handle navigation between service steps
            if (servicesTab) {
                nextBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const nextStepId = btn.dataset.nextStep;
                        if (nextStepId === 'service-step-2') {
                            populateSubServices();
                        } else if (nextStepId === 'service-step-3') {
                            populateSubServiceItems();
                        }
                        showServiceStep(document.getElementById(nextStepId));
                    });
                });
                
                backBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const prevStepId = btn.dataset.prevStep;
                        showServiceStep(document.getElementById(prevStepId));
                    });
                });
            }


            // Handle initial state of "Next" button on step 1
            if (serviceStep1) {
                const checkboxes = serviceStep1.querySelectorAll('input[type="checkbox"]');
                const nextBtn = serviceStep1.querySelector('.next-btn');
                const updateButtonState = () => {
                    const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    nextBtn.disabled = !isAnyChecked;
                    nextBtn.classList.toggle('disabled', !isAnyChecked);
                };
                checkboxes.forEach(cb => cb.addEventListener('change', updateButtonState));
                updateButtonState(); // Set initial state
            }


            // Handle geocoding when address fields change
            [address1Input, address2Input, cityDropdown, stateDropdown, pincodeInput].forEach(el => {
                el.addEventListener('change', () => {
                    clearTimeout(geocodeTimeout);
                    geocodeTimeout = setTimeout(geocodeAddress, 1000);
                });
            });

            stateDropdown.addEventListener('change', updateCities);
            updateCities(); 

            // --- INITIALIZATION ---
            function activateTab(tabId) {
    tabLinks.forEach(l => l.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));

    const linkToActivate = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    const contentToActivate = document.getElementById(tabId);

    if (linkToActivate && contentToActivate) {
        linkToActivate.classList.add('active');
        contentToActivate.classList.add('active');

        if (tabId === 'location' && !map) {
            initializeMap();
            setTimeout(() => map.invalidateSize(), 10);
        } else if (tabId === 'services') { // Add this new condition
            populateMainServices();
        }
    }
}

            // Check for a hash in the URL on page load and activate the corresponding tab
            const currentHash = window.location.hash.substring(1); // Remove the '#'
            if (currentHash) {
                activateTab(currentHash);
            } else {
                activateTab('details');
            }
            
            // Initial population of main services when the page loads
            if (servicesTab && servicesTab.classList.contains('active')) {
                 populateMainServices();
            }
        });
    </script>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>