<?php
ob_start();
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
            $successMessage = 'Services and prices updated successfully!';
            break;
        case 'availability_updated': // Added this case
            $successMessage = 'Availability updated successfully!';
            break;
        // Add success message for offers if needed
        case 'offer_created':
             $successMessage = 'Offer created successfully!';
             break;
         case 'offer_updated':
             $successMessage = 'Offer status updated successfully!';
             break;
         case 'offer_deleted':
              $successMessage = 'Offer deleted successfully!';
              break;
    }
}
// Handle error messages from URL (e.g., from offer management)
$errorMessage = '';
 if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'offer_error':
            $errorMessage = 'An error occurred while managing offers. Please try again.';
            break;
        // Add other error cases if needed
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
$workerItemPrices = [];
$reviews = [];
$avg_rating = 0;

try {
    // Handle form submissions (Details & Professional Profile - no change needed here)
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
        $subServiceItems = $conn->query("SELECT id, sub_service_id, name, icon FROM public.sub_service_items ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch worker's selected sub-services
        $stmt = $conn->prepare("SELECT sub_service_id FROM public.worker_services WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerServiceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch worker's selected sub-service items AND their custom price
        $stmt = $conn->prepare("SELECT sub_service_item_id, price FROM public.worker_sub_service_items WHERE user_id = ?");
        $stmt->execute([$userId]);

        $itemResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $workerSubServiceItemIds = array_column($itemResults, 'sub_service_item_id');

        // Map item IDs to their prices for easy lookup in JS
        $workerItemPrices = array_column($itemResults, 'price', 'sub_service_item_id');

        // Fetch worker reviews and average rating
        $stmt = $conn->prepare("SELECT r.*, c.full_name as customer_name, c.profile_image as customer_avatar FROM public.reviews r JOIN public.users c ON r.reviewer_id = c.id WHERE r.worker_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$userId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($reviews) > 0) {
            $total_rating = array_sum(array_column($reviews, 'rating'));
            $avg_rating = $total_rating / count($reviews);
        }
    }

} catch (PDOException $e) {
    error_log("Profile page error: " . $e->getMessage());
    $errorMessage = "Could not load some profile data."; // Set general error
}

// Data for State and City Dropdowns (same as before)
$indian_states_cities = [ /* ... Full list as provided before ... */
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
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style> /* Added styles for offers section */
        #offers .form-card {
            background-color: var(--background-secondary);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        #offers-list-container .offer-item {
            background-color: var(--background-secondary);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 1rem;
        }
         .offer-details { flex-grow: 1; }
         .offer-details strong { font-size: 1.1em; color: var(--primary-color); }
         .offer-details p { font-size: 0.9em; color: var(--text-color-light); margin: 0.2rem 0; }
         .offer-actions { display: flex; gap: 0.5rem; }
         .offer-actions button { /* Style toggle/delete buttons */
            padding: 5px 10px; font-size: 0.8em; cursor: pointer; border-radius: 5px; border: 1px solid; background: none; transition: background-color 0.2s, color 0.2s;
         }
         
         #offer-form-message.success { color: var(--success-color); font-weight: 500;}
         #offer-form-message.error { color: var(--danger-color); font-weight: 500;}

        input[type=datetime-local] {
           padding: 11px; /* Adjust if needed based on other inputs */
           line-height: normal; /* Ensure consistent line height */
        }
        /* Basic alert styling */
        .form-error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1.5rem;
        }
        body.dark-mode .form-error-message {
            background-color: #721c24;
            color: #f8d7da;
            border-color: #842029;
        }
    </style>
</head>

<body>
   
    <main class="page-content">
        <div class="profile-page-container">
            <div class="profile-header">
                <?php
                    $profileAvatar = $userData['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png';
                    if ($userData['profile_image'] && strpos($userData['profile_image'], '/') !== 0) {
                        $profileAvatar = '/dailyfix/' . $userData['profile_image'];
                    }
                ?>
                <img src="<?php echo htmlspecialchars($profileAvatar); ?>" alt="Profile Avatar" class="profile-header-avatar">
                <h1><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                <p><?php echo htmlspecialchars(ucfirst($role)); ?></p>
            </div>

             <?php if ($errorMessage): ?>
                <div class="form-error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="tab-nav">
                <button class="tab-link active" data-tab="details">My Details</button>
                <?php if ($role === 'worker'): ?>
                <button class="tab-link" data-tab="professional">Professional Profile</button>
                <button class="tab-link" data-tab="availability">My Availability</button>
                <button class="tab-link" data-tab="reviews">Reviews</button>
                <button class="tab-link" data-tab="services">My Services</button>
                <button class="tab-link" data-tab="offers">My Offers</button>
                <?php else: // Customer ?>
                <button class="tab-link" data-tab="history">Booking History</button>
                <?php endif; ?>
                <button class="tab-link" data-tab="location">Location</button>
            </div>

            <div id="details" class="tab-content active">
                <div class="form-section">
                    <?php if ($successMessage && (isset($_GET['success']) && $_GET['success'] === 'details_updated')): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>Personal Information</h3>
                    <form action="profile.php#details" method="POST">
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
                     <?php if ($successMessage && (isset($_GET['success']) && $_GET['success'] === 'professional_updated')): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>Professional Profile</h3>
                    <form action="profile.php#professional" method="POST">
                        <input type="hidden" name="update_worker_profile" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="experience_years">Years of Experience</label>
                                <input type="number" id="experience_years" name="experience_years"
                                    value="<?php echo htmlspecialchars($workerProfile['experience_years'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate (₹)</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" step="0.50"
                                    value="<?php echo htmlspecialchars($workerProfile['hourly_rate'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bio">Bio / Introduction</label>
                            <textarea id="bio" name="bio"
                                rows="6"><?php echo htmlspecialchars($workerProfile['bio'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Save Professional Info</button>
                    </form>
                </div>
            </div>

            <div id="availability" class="tab-content">
                <div class="form-section">
                    <?php if ($successMessage && (isset($_GET['success']) && $_GET['success'] === 'availability_updated')): ?>
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
                        <button class="submit-btn" id="save-final-btn" style="width: auto; margin-top: 2rem;">Save Availability</button>
                    </div>
                </div>
            </div>

            <div id="reviews" class="tab-content">
                <div class="form-section">
                    <h2>Your Reviews</h2>
                    <div class="rating-summary-header">
                        <i class="fas fa-star"></i>
                        <strong><?php echo number_format($avg_rating, 1); ?></strong>
                        <span> based on <?php echo count($reviews); ?> reviews</span>
                    </div>

                    <?php if (count($reviews) > 0) : ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $review) : ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <?php
                                            $reviewAvatarPath = '/dailyfix/assets/images/default-avatar.png';
                                            if (!empty($review['customer_avatar'])) {
                                                if (strpos($review['customer_avatar'], '/') === 0) {
                                                    $reviewAvatarPath = $review['customer_avatar'];
                                                } else {
                                                    $reviewAvatarPath = '/dailyfix/' . $review['customer_avatar'];
                                                }
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($reviewAvatarPath); ?>" alt="Customer" class="review-avatar">
                                        <div class="review-customer-info">
                                            <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                            <span><?php echo (new DateTime($review['created_at']))->format('M j, Y'); ?></span>
                                        </div>
                                        <div class="rating">
                                            <?php for ($i = 0; $i < 5; $i++) : ?>
                                                <i class="fas fa-star <?php echo $i < $review['rating'] ? 'selected' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="no-reviews">You have not received any reviews yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="services" class="tab-content">
                <div class="form-section">
                    <?php if ($successMessage && (isset($_GET['success']) && $_GET['success'] === 'services_updated')): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>My Services</h3>
                    <form id="service-selection-form" action="/dailyfix/api/update_worker_profile_services.php" method="POST">

                        <div id="service-step-1" class="step active">
                            <h4>1. Main Services</h4>
                            <p>Select the main categories you specialize in.</p>
                            <div id="main-services-container" class="service-selection-grid">
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
                            <h4>3. Service Items & Pricing</h4>
                            <p>Select the specific tasks you can perform and set a default price for each.</p>

                            <div id="sub-service-items-and-price-container"></div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary back-btn" data-prev-step="service-step-2">Back</button>
                                <button type="submit" class="btn btn-primary">Save Services & Prices</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div id="offers" class="tab-content">
                <div class="form-section">
                     <?php if ($successMessage && (isset($_GET['success']) && strpos($_GET['success'], 'offer_') === 0)): ?>
                        <div id="success-alert" class="form-success-message">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                     <?php if ($errorMessage && (isset($_GET['error']) && strpos($_GET['error'], 'offer_') === 0)): ?>
                        <div class="form-error-message">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <h3>Manage Your Offers</h3>
                    <div class="form-card" style="margin-bottom: 2rem;">
                         <h4><i class="fas fa-plus-circle"></i> Create New Offer</h4>
                         <form id="create-offer-form">
                             <input type="hidden" name="action" value="create">
                             <div class="form-group">
                                 <label for="coupon_code">Coupon Code (e.g., SAVE10)</label>
                                 <input type="text" id="coupon_code" name="coupon_code" required maxlength="50" style="text-transform:uppercase">
                             </div>
                             <div class="form-grid">
                                <div class="form-group">
                                    <label for="discount_type">Discount Type</label>
                                    <select id="discount_type" name="discount_type" required>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount (₹)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="discount_value">Discount Value</label>
                                    <input type="number" id="discount_value" name="discount_value" step="0.01" min="0.01" required placeholder="e.g., 10 or 50.00">
                                </div>
                             </div>
                             <div class="form-grid">
                                 <div class="form-group">
                                     <label for="min_booking_amount">Minimum Booking Amount (₹, optional)</label>
                                     <input type="number" id="min_booking_amount" name="min_booking_amount" step="0.01" min="0.00" placeholder="0.00">
                                 </div>
                                 <div class="form-group">
                                     <label for="valid_until">Valid Until (Optional)</label>
                                     <input type="datetime-local" id="valid_until" name="valid_until">
                                 </div>
                             </div>
                              <div class="form-group">
                                 <label for="max_uses">Maximum Total Uses (Optional)</label>
                                 <input type="number" id="max_uses" name="max_uses" min="1" placeholder="Leave blank for unlimited">
                             </div>
                             <button type="submit" class="submit-btn">Create Offer</button>
                         </form>
                         <div id="offer-form-message" style="margin-top: 1rem;"></div>
                    </div>

                    <h4><i class="fas fa-list"></i> Your Current Offers</h4>
                    <div id="offers-list-container">
                        <p>Loading offers...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'customer'): ?>
            <div id="history" class="tab-content">
                <div class="form-section">
                    <h3>Booking History</h3>
                    <p>A summary of your recent bookings. For more details, visit the "My Bookings" page.</p>
                    <a href="/dailyfix/customer/bookings.php" class="submit-btn"
                        style="text-align: center; text-decoration: none; display: inline-block; width: auto;">View All My Bookings</a>
                </div>
            </div>
            <?php endif; ?>

            <div id="location" class="tab-content">
                <div class="form-section">
                     <?php if ($successMessage && (isset($_GET['success']) && $_GET['success'] === 'location_updated')): ?>
                    <div id="success-alert" class="form-success-message">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                    <?php endif; ?>
                    <h3>My Location</h3>
                    <form action="/dailyfix/api/update_location.php" method="POST">
                        <div id="map"></div>
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($userData['latitude'] ?? ''); ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($userData['longitude'] ?? ''); ?>">

                        <div class="address-fields-container">
                            <div class="form-group">
                                <label for="address_line1">Address Line 1</label>
                                <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($userData['address_line1'] ?? ''); ?>" placeholder="House No. & Building Name">
                            </div>
                            <div class="form-group">
                                <label for="address_line2">Address Line 2</label>
                                <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($userData['address_line2'] ?? ''); ?>" placeholder="Road, Area, Colony">
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <select id="state" name="state">
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?php echo htmlspecialchars($state); ?>" <?php if (($userData['state'] ?? '') === $state) echo 'selected'; ?>><?php echo htmlspecialchars($state); ?></option>
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
                                <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($userData['pincode'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Save Location</button>
                    </form>
                </div>
            </div>

        </div>
    </main>
    <script>
        // --- Pass PHP data to JS ---
        const citiesByState = <?php echo json_encode($indian_states_cities); ?>;
        const userData = <?php echo json_encode($userData); ?>;
        const isWorker = <?php echo json_encode($role === 'worker'); ?>; // Flag for worker-specific JS

        // PHP data for services (only if worker)
        const allServices = isWorker ? <?php echo json_encode($services); ?> : [];
        const allSubServices = isWorker ? <?php echo json_encode($subServices); ?> : [];
        const allSubServiceItems = isWorker ? <?php echo json_encode($subServiceItems); ?> : [];
        const workerServiceIds = isWorker ? <?php echo json_encode($workerServiceIds); ?>.map(id => parseInt(id)) : [];
        const workerSubServiceItemIds = isWorker ? <?php echo json_encode($workerSubServiceItemIds); ?>.map(id => parseInt(id)) : [];
        const workerItemPrices = isWorker ? <?php echo json_encode($workerItemPrices); ?> : {};

        // Helper function (if needed, otherwise remove)
        function getSubServiceById(id) { return isWorker ? allSubServices.find(sub => sub.id === id) : null; }
        function getServiceItemById(id) { return isWorker ? allSubServiceItems.find(item => item.id === id) : null; }

        // --- Main DOMContentLoaded ---
        document.addEventListener('DOMContentLoaded', function () {
            // JS for auto-dismissing the success message
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(() => {
                    if (successAlert) { // Check again if it wasn't removed by tab switching
                      successAlert.style.transition = 'opacity 0.5s ease-out';
                      successAlert.style.opacity = '0';
                      setTimeout(() => successAlert.remove(), 500);
                    }
                }, 5000); // 5 seconds
            }

            // --- Get DOM Elements ---
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            const stateDropdown = document.getElementById('state');
            const cityDropdown = document.getElementById('city');
            const address1Input = document.getElementById('address_line1');
            const address2Input = document.getElementById('address_line2');
            const pincodeInput = document.getElementById('pincode');
            const latInput = document.getElementById('latitude');
            const lonInput = document.getElementById('longitude');
            const mapElement = document.getElementById('map'); // Map container

            // Worker specific elements
            const servicesTab = document.getElementById('services');
            const offersTab = document.getElementById('offers'); // Get offers tab
            const createOfferForm = document.getElementById('create-offer-form');
            const offersListContainer = document.getElementById('offers-list-container');
            const offerFormMessage = document.getElementById('offer-form-message');


            // Service Step elements (only if worker)
            const serviceStep1 = servicesTab ? document.getElementById('service-step-1') : null;
            const serviceStep2 = servicesTab ? document.getElementById('service-step-2') : null;
            const serviceStep3 = servicesTab ? document.getElementById('service-step-3') : null;
            const nextBtns = servicesTab ? servicesTab.querySelectorAll('.next-btn') : [];
            const backBtns = servicesTab ? servicesTab.querySelectorAll('.back-btn') : [];
            const mainServicesContainer = servicesTab ? document.getElementById('main-services-container') : null;
            const subServicesContainer = servicesTab ? document.getElementById('sub-services-container') : null;
            const subServiceItemsAndPriceContainer = servicesTab ? document.getElementById('sub-service-items-and-price-container') : null;


            // Map variables
            let map, marker;
            let geocodeTimeout;

            // --- FUNCTION DEFINITIONS ---

             // Function to fetch and display offers
            function loadOffers() {
                if (!offersListContainer) return;
                offersListContainer.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Loading offers...</p>';
                fetch('/dailyfix/api/manage_worker_offers.php?action=list')
                    .then(res => res.json())
                    .then(result => {
                        if (result.status === 'success' && result.data) {
                            renderOffers(result.data);
                        } else {
                            offersListContainer.innerHTML = '<p style="color: red;">Could not load offers: '+(result.message || 'Unknown error')+'.</p>';
                        }
                    })
                    .catch(() => offersListContainer.innerHTML = '<p style="color: red;">Error fetching offers.</p>');
            }

            // Function to render the list of offers
            function renderOffers(offers) {
                 if (!offersListContainer) return;
                if (offers.length === 0) {
                    offersListContainer.innerHTML = '<p>You haven\'t created any offers yet.</p>';
                    return;
                }
                offersListContainer.innerHTML = ''; // Clear loading/previous
                offers.forEach(offer => {
                    const offerDiv = document.createElement('div');
                    offerDiv.className = 'offer-item';
                    const isActive = offer.is_active; // Directly use boolean
                    const statusText = isActive ? 'Active' : 'Inactive';
                    const toggleBtnText = isActive ? 'Deactivate' : 'Activate';
                    const toggleBtnClass = isActive ? 'active' : 'inactive';

                    let details = `<strong>${offer.coupon_code}</strong>: `;
                    if(offer.discount_type === 'percentage') {
                        details += `${parseFloat(offer.discount_value)}% off`;
                    } else {
                        details += `₹${parseFloat(offer.discount_value).toFixed(2)} off`;
                    }
                    details += ` (Status: ${statusText})`;

                    let validity = '';
                     if (offer.valid_until_local) { // Use the pre-formatted local time
                        validity = ` | Expires: ${offer.valid_until_local.replace(' ', ' at ')}`;
                    }

                     let usage = ` | Used: ${offer.uses_count}`;
                    if (offer.max_uses !== null) { // Check for null explicitely
                        usage += ` / ${offer.max_uses}`;
                    }
                    let minAmount = '';
                     if (parseFloat(offer.min_booking_amount) > 0) {
                        minAmount = ` | Min: ₹${parseFloat(offer.min_booking_amount).toFixed(2)}`;
                    }


                    offerDiv.innerHTML = `
                        <div class="offer-details">
                            <p>${details}</p>
                            <p style="font-size: 0.8em;">${minAmount}${validity}${usage}</p>
                        </div>
                        <div class="offer-actions">
                            <button class="toggle-btn ${toggleBtnClass}" data-offer-id="${offer.id}">${toggleBtnText}</button>
                            <button class="delete-btn" data-offer-id="${offer.id}">Delete</button>
                        </div>
                    `;
                    offersListContainer.appendChild(offerDiv);
                });
            }


            function showServiceStep(stepElement) {
                if (!servicesTab) return;
                const steps = servicesTab.querySelectorAll('.step');
                steps.forEach(step => step.classList.remove('active'));
                if(stepElement) stepElement.classList.add('active');
            }

            function populateMainServices() {
                if (!mainServicesContainer) return;
                mainServicesContainer.className = 'service-selection-grid';
                mainServicesContainer.innerHTML = '';

                // Determine initially checked main services based on selected sub-services
                 const workerMainServiceIds = [...new Set(
                    allSubServices
                        .filter(sub => workerServiceIds.includes(parseInt(sub.id))) // Ensure comparison is number vs number
                        .map(sub => parseInt(sub.service_id))
                )];


                allServices.forEach(service => {
                    const serviceIdInt = parseInt(service.id);
                    const isChecked = workerMainServiceIds.includes(serviceIdInt) ? 'checked' : '';
                    const serviceHtml = `
                        <div class="service-card-selectable">
                            <input type="checkbox" id="main-service-${service.id}" name="main_services[]" value="${service.id}" ${isChecked}>
                            <label for="main-service-${service.id}">
                                <i class="${service.icon}"></i>
                                <span>${service.name}</span>
                            </label>
                        </div>
                    `;
                    mainServicesContainer.innerHTML += serviceHtml;
                });

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
                if (!subServicesContainer || !serviceStep1) return;
                const selectedServiceIds = Array.from(serviceStep1.querySelectorAll('input[type="checkbox"]:checked')).map(cb => parseInt(cb.value));
                subServicesContainer.innerHTML = '';

                let content = '';
                allServices.forEach(service => {
                     const serviceIdInt = parseInt(service.id);
                    if (selectedServiceIds.includes(serviceIdInt)) {
                        const relatedSubServices = allSubServices.filter(sub => parseInt(sub.service_id) === serviceIdInt);
                        if (relatedSubServices.length > 0) {
                            content += `
                                <div class="services-category-group">
                                    <h4 class="service-category-title"><i class="${service.icon}"></i> ${service.name}</h4>
                                    <div class="service-selection-grid">
                            `;
                            relatedSubServices.forEach(sub => {
                                 const subIdInt = parseInt(sub.id);
                                const isChecked = workerServiceIds.includes(subIdInt) ? 'checked' : '';
                                content += `
                                    <div class="service-card-selectable">
                                        <input type="checkbox" id="sub-service-${sub.id}" name="services[]" value="${sub.id}" ${isChecked}>
                                        <label for="sub-service-${sub.id}">
                                            <i class="${sub.icon}"></i>
                                            <span>${sub.name}</span>
                                        </label>
                                    </div>
                                `;
                            });
                            content += `</div></div>`;
                        }
                    }
                });
                subServicesContainer.innerHTML = content || '<p>No sub-services found for selected categories.</p>';

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

            function populateSubServiceItemsAndPrice() {
                if (!subServiceItemsAndPriceContainer || !serviceStep2) return;
                const selectedSubServiceIds = Array.from(serviceStep2.querySelectorAll('input[name="services[]"]:checked')).map(cb => parseInt(cb.value));
                subServiceItemsAndPriceContainer.innerHTML = '';

                let content = '';
                let renderedItems = new Set();

                selectedSubServiceIds.forEach(subServiceId => {
                     const subIdInt = parseInt(subServiceId);
                    const subService = getSubServiceById(subIdInt);
                    if (!subService) return;

                    const relatedItems = allSubServiceItems.filter(item => parseInt(item.sub_service_id) === subIdInt);

                    if (relatedItems.length > 0) {
                        content += `
                            <div class="services-category-group">
                                <h4 class="service-category-title"><i class="${subService.icon}"></i> ${subService.name}</h4>
                                <div class="sub-service-items-grid">
                        `;
                        relatedItems.forEach(item => {
                            const itemIdInt = parseInt(item.id);
                            if (renderedItems.has(itemIdInt)) return;
                            renderedItems.add(itemIdInt);

                            const isChecked = workerSubServiceItemIds.includes(itemIdInt) ? 'checked' : '';
                            // Use bracket notation for workerItemPrices as keys are numbers
                            const currentPrice = workerItemPrices[itemIdInt] !== undefined ? workerItemPrices[itemIdInt] : 0.00;


                            content += `
                                <div class="item-and-price-group">
                                    <div class="service-card-selectable" style="margin-bottom: 0.5rem;">
                                        <input type="checkbox" id="item-${item.id}" name="sub_service_items[]" value="${item.id}" ${isChecked}>
                                        <label for="item-${item.id}" style="height: 100%;">
                                            <span>${item.name}</span>
                                        </label>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="price-${item.id}" style="font-size: 0.85rem;">Price (₹)</label>
                                        <input type="number" id="price-${item.id}" name="prices[${item.id}]" step="0.01" min="0.00"
                                               value="${parseFloat(currentPrice).toFixed(2)}" placeholder="0.00" required>
                                    </div>
                                </div>
                            `;
                        });
                        content += `</div></div>`;
                    }
                });

                subServiceItemsAndPriceContainer.innerHTML = content || '<p>No service items found for the selected sub-services. Please go back.</p>';

                // Add event listeners to require price >= 0 if item is checked
                const itemCheckboxes = subServiceItemsAndPriceContainer.querySelectorAll('input[name="sub_service_items[]"]');
                itemCheckboxes.forEach(checkbox => {
                    const itemId = checkbox.value;
                    const priceInput = document.getElementById(`price-${itemId}`);

                    const togglePriceRequirement = () => {
                         // Price is required IF the checkbox is checked
                         priceInput.required = checkbox.checked;
                         // Set min to 0.01 only if checked, otherwise allow 0 (or technically doesn't matter if not required)
                         priceInput.min = checkbox.checked ? '0.01' : '0.00';
                         if (!checkbox.checked) {
                            // Optionally reset price to 0 if unchecked, or leave as is
                            // priceInput.value = '0.00';
                         }
                    };

                    togglePriceRequirement(); // Initial setup
                    checkbox.addEventListener('change', togglePriceRequirement);

                     // Ensure initial state for price requirement based on checked status
                     if (checkbox.checked) {
                        priceInput.required = true;
                        priceInput.min = '0.01';
                     } else {
                         priceInput.required = false;
                         priceInput.min = '0.00';
                     }
                });
            }


            // Map Functions
            function initializeMap() {
                 if (!mapElement) return; // Exit if map element doesn't exist
                const lat = parseFloat(userData.latitude) || 21.1702; // Default to Surat if no user data
                const lon = parseFloat(userData.longitude) || 72.8311;
                map = L.map(mapElement).setView([lat, lon], 15);
                 const tileUrl = document.body.classList.contains('dark-mode')
                    ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                    : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                 const attribution = document.body.classList.contains('dark-mode')
                     ? '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                     : '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

                L.tileLayer(tileUrl, { attribution: attribution }).addTo(map);
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);

                // Update lat/lon inputs when marker is dragged
                 marker.on('dragend', function(event) {
                    const position = marker.getLatLng();
                    latInput.value = position.lat.toFixed(6);
                    lonInput.value = position.lng.toFixed(6);
                    reverseGeocode(position.lat, position.lng); // Update address fields too
                });

                 // Update marker when map is clicked
                 map.on('click', function(e) {
                     const latlng = e.latlng;
                     latInput.value = latlng.lat.toFixed(6);
                     lonInput.value = latlng.lng.toFixed(6);
                     marker.setLatLng(latlng);
                     reverseGeocode(latlng.lat, latlng.lng);
                });
            }

            function updateCities() {
                if (!stateDropdown || !cityDropdown) return;
                const selectedState = stateDropdown.value;
                const currentCity = userData.city || ''; // Use user's current city for default selection
                cityDropdown.innerHTML = '<option value="">Select City</option>'; // Reset cities
                if (selectedState && citiesByState[selectedState]) {
                    citiesByState[selectedState].forEach(city => {
                        const option = document.createElement('option');
                        option.value = city;
                        option.textContent = city;
                        // Pre-select the city if it matches the user's data OR if it's the only city for the state
                        if (city === currentCity || citiesByState[selectedState].length === 1) {
                            option.selected = true;
                             // Trigger change event if city is pre-selected
                             if (city === currentCity) {
                                cityDropdown.value = city; // Explicitly set value
                                // Optionally trigger geocode if needed:
                                // clearTimeout(geocodeTimeout);
                                // geocodeTimeout = setTimeout(geocodeAddress, 500);
                             }
                        }
                        cityDropdown.appendChild(option);
                    });
                     cityDropdown.disabled = false;
                } else {
                     cityDropdown.disabled = true;
                }
            }

            async function reverseGeocode(lat, lng) {
                 const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1`;
                 try {
                     const response = await fetch(url);
                     const data = await response.json();
                     if (data && data.address) {
                         const addr = data.address;
                         // Populate fields carefully, using fallbacks
                         address1Input.value = addr.house_number || addr.building || '';
                         address2Input.value = [addr.road, addr.neighbourhood, addr.suburb, addr.village].filter(Boolean).join(', ');
                         pincodeInput.value = addr.postcode || '';

                         // Update State dropdown
                         if (addr.state && stateDropdown) {
                            // Find the option whose text matches the state name (case-insensitive)
                            const stateOption = Array.from(stateDropdown.options).find(opt => opt.text.toLowerCase() === addr.state.toLowerCase());
                            if (stateOption) {
                                stateDropdown.value = stateOption.value; // Set the dropdown value
                                userData.city = addr.city || addr.town || addr.village || addr.county || ''; // Update userData temporarily for city selection
                                updateCities(); // Update city dropdown based on the new state
                            }
                         } else {
                              // If state not found in dropdown, maybe clear city?
                              cityDropdown.innerHTML = '<option value="">Select City</option>';
                              cityDropdown.disabled = true;
                         }

                         // City is handled within updateCities after state is set
                     }
                 } catch (error) {
                     console.error("Reverse geocoding error:", error);
                 }
            }


            async function geocodeAddress() {
                 if (!stateDropdown.value || !cityDropdown.value || !pincodeInput.value || (!address1Input.value && !address2Input.value)) {
                    // console.log("Skipping geocode: Insufficient address info");
                    return; // Avoid unnecessary API calls if essential info is missing
                 }

                // Construct a query prioritizing specific fields if available
                 let queryParts = [];
                 if (address1Input.value) queryParts.push(address1Input.value);
                 if (address2Input.value) queryParts.push(address2Input.value);
                 if (cityDropdown.value) queryParts.push(cityDropdown.value);
                 if (stateDropdown.value) queryParts.push(stateDropdown.value);
                 if (pincodeInput.value) queryParts.push(pincodeInput.value);
                 queryParts.push("India"); // Add country for better accuracy

                const query = encodeURIComponent(queryParts.join(', '));
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${query}&countrycodes=in&limit=1`; // Limit to 1 result

                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);

                        latInput.value = lat.toFixed(6);
                        lonInput.value = lon.toFixed(6);

                        if (map && marker) {
                            const newLatLng = new L.LatLng(lat, lon);
                            map.setView(newLatLng, 15); // Zoom in closer on geocode result
                            marker.setLatLng(newLatLng);
                        }
                    } else {
                         console.log("Geocoding could not find coordinates for the address.");
                    }
                } catch (error) {
                    console.error("Geocoding error:", error);
                }
            }


            // --- EVENT LISTENERS ---

            // Tab Clicking
            tabLinks.forEach(link => {
                link.addEventListener('click', () => {
                    const tabId = link.getAttribute('data-tab');
                    activateTab(tabId);
                    // Update URL hash without causing page jump if history API is supported
                    if (history.pushState) {
                        history.pushState(null, null, `#${tabId}`);
                    } else {
                        window.location.hash = tabId;
                    }
                     // Remove success message when switching tabs
                     const existingAlert = document.getElementById('success-alert');
                     if (existingAlert) existingAlert.remove();
                });
            });

            // Service Step Navigation (only if worker)
            if (isWorker && servicesTab) {
                nextBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        const nextStepId = btn.dataset.nextStep;
                        if (nextStepId === 'service-step-2') {
                            populateSubServices();
                        } else if (nextStepId === 'service-step-3') {
                            populateSubServiceItemsAndPrice();
                        }
                        const nextStepElement = document.getElementById(nextStepId);
                         if (nextStepElement) {
                             showServiceStep(nextStepElement);
                         } else {
                             console.error("Next step element not found:", nextStepId);
                         }
                    });
                });

                backBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                         if (btn.disabled) return;
                        const prevStepId = btn.dataset.prevStep;
                         const prevStepElement = document.getElementById(prevStepId);
                         if (prevStepElement) {
                             showServiceStep(prevStepElement);
                         } else {
                             console.error("Previous step element not found:", prevStepId);
                         }
                    });
                });

                 // Event delegation for dynamically added checkboxes in service steps
                 servicesTab.addEventListener('change', function(e) {
                     if (e.target.matches('#main-services-container input[type="checkbox"]') && serviceStep1) {
                        const checkboxes = mainServicesContainer.querySelectorAll('input[type="checkbox"]');
                        const nextBtn = serviceStep1.querySelector('.next-btn');
                        const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                        nextBtn.disabled = !isAnyChecked;
                        nextBtn.classList.toggle('disabled', !isAnyChecked);
                     } else if (e.target.matches('#sub-services-container input[type="checkbox"]') && serviceStep2) {
                        const checkboxes = subServicesContainer.querySelectorAll('input[type="checkbox"]');
                        const nextBtn = serviceStep2.querySelector('.next-btn');
                        const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
                        nextBtn.disabled = !isAnyChecked;
                        nextBtn.classList.toggle('disabled', !isAnyChecked);
                     }
                      // Price requirement logic is already inside populateSubServiceItemsAndPrice
                 });
            }


            // Offer Management (only if worker)
            if (isWorker && offersTab) {
                // Handle Create Offer Form Submission
                if (createOfferForm) {
                    createOfferForm.addEventListener('submit', function(e) {
                         e.preventDefault();
                         const formData = new FormData(this);
                         const submitBtn = this.querySelector('button[type="submit"]');
                         submitBtn.disabled = true;
                         submitBtn.textContent = 'Creating...';
                         offerFormMessage.textContent = '';
                         offerFormMessage.className = '';


                         fetch('/dailyfix/api/manage_worker_offers.php', { method: 'POST', body: formData })
                             .then(res => res.json())
                             .then(result => {
                                 if (result.status === 'success') {
                                     offerFormMessage.textContent = result.message;
                                     offerFormMessage.className = 'success';
                                     this.reset(); // Clear form
                                     loadOffers(); // Refresh list
                                 } else {
                                     offerFormMessage.textContent = result.message;
                                     offerFormMessage.className = 'error';
                                 }
                             })
                             .catch(() => {
                                  offerFormMessage.textContent = 'An error occurred.';
                                  offerFormMessage.className = 'error';
                             })
                             .finally(() => {
                                  submitBtn.disabled = false;
                                  submitBtn.textContent = 'Create Offer';
                             });
                     });
                 }

                 // Handle Toggle Active and Delete Buttons (Event Delegation)
                 if (offersListContainer) {
                    offersListContainer.addEventListener('click', function(e) {
                        const target = e.target;
                        const offerId = target.dataset.offerId;
                        let action = '';
                         let confirmationMessage = '';


                        if (target.classList.contains('toggle-btn')) {
                            action = 'toggle_active';
                             confirmationMessage = `Are you sure you want to ${target.textContent.toLowerCase()} this offer?`;

                        } else if (target.classList.contains('delete-btn')) {
                            action = 'delete';
                             confirmationMessage = 'Are you sure you want to permanently delete this offer? This cannot be undone.';
                        } else {
                            return; // Click wasn't on a button we care about
                        }

                         if (!confirm(confirmationMessage)) {
                            return;
                        }


                        if (offerId && action) {
                            const formData = new FormData();
                            formData.append('action', action);
                            formData.append('offer_id', offerId);

                            target.disabled = true; // Disable button during request
                            target.textContent = '...';


                             fetch('/dailyfix/api/manage_worker_offers.php', { method: 'POST', body: formData })
                                .then(res => res.json())
                                .then(result => {
                                    if (result.status === 'success') {
                                        loadOffers(); // Refresh the list on success
                                         // Optionally show a temporary success message somewhere
                                    } else {
                                        alert('Error: ' + result.message);
                                         // Re-enable button on error - need original text
                                         // This part is tricky without storing original text; loadOffers() handles repaint
                                    }
                                })
                                .catch(() => {
                                     alert('An error occurred.');
                                      // Re-enable button on error
                                });
                        }
                    });
                }
            }


            // Location Fields Logic
            if (stateDropdown) {
              stateDropdown.addEventListener('change', updateCities);
            }
             if (address1Input && address2Input && cityDropdown && stateDropdown && pincodeInput) {
                [address1Input, address2Input, cityDropdown, stateDropdown, pincodeInput].forEach(el => {
                    el.addEventListener('input', () => { // Use 'input' for faster feedback than 'change'
                        clearTimeout(geocodeTimeout);
                        geocodeTimeout = setTimeout(geocodeAddress, 1200); // Increased delay
                    });
                });
            }


            // --- INITIALIZATION ---
            function activateTab(tabId) {
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                const linkToActivate = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
                const contentToActivate = document.getElementById(tabId);

                if (linkToActivate && contentToActivate) {
                    linkToActivate.classList.add('active');
                    contentToActivate.classList.add('active');

                    // Initialize map only when location tab is activated and map exists
                    if (tabId === 'location' && mapElement && !map) {
                        initializeMap();
                        // Delay invalidateSize slightly to ensure container is visible
                        setTimeout(() => { if (map) map.invalidateSize(); }, 50);
                    } else if (map && L.DomUtil.isProperlyVisible(mapElement)) {
                        // If map already initialized and tab becomes visible again, refresh size
                         setTimeout(() => { if (map) map.invalidateSize(); }, 50);
                    }


                    // Initialize services tab (if worker)
                    if (tabId === 'services' && isWorker && serviceStep1) {
                        populateMainServices();
                        showServiceStep(serviceStep1); // Reset to step 1
                    }

                    // Initialize offers tab (if worker)
                    if (tabId === 'offers' && isWorker && offersTab) {
                        loadOffers();
                    }
                } else {
                     console.warn(`Tab or content not found for ID: ${tabId}`);
                     // Fallback to default tab if requested tab not found
                     if (tabId !== 'details') {
                         activateTab('details');
                     }
                }
            }

            // --- Initial Setup ---
            updateCities(); // Populate cities based on initial state

            // Activate tab based on URL hash or default
            const currentHash = window.location.hash.substring(1);
            const initialTabId = currentHash || 'details';
            // Ensure the default tab exists before activating
             if (document.getElementById(initialTabId)) {
                activateTab(initialTabId);
            } else {
                 activateTab('details'); // Fallback safety
            }

        });
    </script>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>
<?php ob_end_flush(); ?>