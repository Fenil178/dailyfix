<?php
$serviceSlug = $_GET['service'] ?? ''; // Get the service slug from the URL

// Determine the correct back link URL and text
if (!empty($serviceSlug)) {
    $backLink = "/dailyfix/customer/find_workers.php?service=" . htmlspecialchars($serviceSlug);
    $backLinkText = "Back to Workers";
} else {
    $backLink = "/dailyfix/customer/services.php";
    $backLinkText = "Back to Services";
}

include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$workerId = $_GET['id'];
$worker = null;
$customer_lat = null;
$customer_lon = null;
$customer_address_string = ''; // NEW: Variable for customer's full address

// Get customer's location and full address
try {
    // NEW: Fetch all address fields for the customer
    $stmt = $conn->prepare("SELECT latitude, longitude, address_line1, address_line2, city, state, pincode FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $customer_location = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer_location) {
        $customer_lat = $customer_location['latitude'];
        $customer_lon = $customer_location['longitude'];
        // NEW: Construct the full address string for auto-filling the form
        $customer_address_string = htmlspecialchars(implode(', ', array_filter([
            $customer_location['address_line1'],
            $customer_location['address_line2'],
            $customer_location['city'],
            $customer_location['state'],
            $customer_location['pincode']
        ])));
    }
} catch (PDOException $e) {
    error_log("Book Worker Page Error: " . $e->getMessage());
}

try {
    // NEW: Fetch all address fields for the worker to display them
    $sql = "
        SELECT u.id, u.full_name, u.profile_image, u.latitude, u.longitude, 
               u.address_line1, u.address_line2, u.city, u.state, u.pincode,
               wp.bio, wp.experience_years, wp.hourly_rate";
    
    if ($customer_lat && $customer_lon) {
        $sql .= ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance";
    }
    
    $sql .= "
        FROM public.users u
        JOIN public.worker_profiles wp ON u.id = wp.user_id
        WHERE u.id = ? AND u.role = 'worker'
    ";

    $stmt = $conn->prepare($sql);
    
    if ($customer_lat && $customer_lon) {
        $stmt->execute([$customer_lat, $customer_lon, $customer_lat, $workerId]);
    } else {
        $stmt->execute([$workerId]);
    }

    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Book Worker Page Error: " . $e->getMessage());
}

if (!$worker) {
    echo "Worker not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book <?php echo htmlspecialchars($worker['full_name']); ?></title>
    
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/book_worker.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <script defer src="/dailyfix/assets/js/book_worker_availability.js"></script>

</head>
<body>
    <main class="page-content">  
        <div class="page-header" style="max-width: 1100px; margin: 2rem auto 1rem auto; padding: 0 1rem;">
            <a href="<?php echo $backLink; ?>" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo $backLinkText; ?></a>        
        </div>
        <div class="booking-container">
            <div class="worker-profile-panel">
                <img src="<?php echo htmlspecialchars($worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="profile-avatar-custom">
                <h1><?php echo htmlspecialchars($worker['full_name']); ?></h1>
                <div class="profile-meta">
                    <span><i class="fas fa-star"></i> 4.8 Stars</span>
                    <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                    <?php if (isset($worker['distance'])): ?>
                        <span><i class="fas fa-map-pin"></i> <?php echo round($worker['distance'], 2); ?> km away</span>
                    <?php endif; ?>
                </div>
                
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($worker['bio'])); ?></p>

                <div class="profile-meta" style="margin-top: 1.5rem; justify-content: flex-start;">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>
                        <?php echo htmlspecialchars(implode(', ', array_filter([
                            $worker['address_line1'],
                            $worker['address_line2'],
                            $worker['city'],
                            $worker['state']
                        ]))); ?>
                    </span>
                </div>

                <?php if ($customer_lat && $customer_lon && $worker['latitude'] && $worker['longitude']): ?>
                    <div id="map" style="height: 200px; margin-top: 1rem; border-radius: 8px;"></div>
                <?php endif; ?>
            </div>

            <div class="booking-form-panel">
                <h2>Book This Worker</h2>
                <form id="booking-form" action="/dailyfix/api/create_booking.php" method="POST" data-worker-id="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $userId; ?>">
                    <input type="hidden" id="booking_date" name="booking_date">
                    <input type="hidden" id="booking_time_combined" name="booking_time">

                    <div class="form-group">
                        <label for="service_details">Describe the work needed</label>
                        <textarea id="service_details" name="service_details" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Select a Date</label>
                        <div id="calendar-container">
                            <div id="calendar-days" class="calendar-grid"></div>
                        </div>
                    </div>

                    <div class="form-group" id="time-slot-container" style="display: none;">
                        <label>Select a Time for <span id="selected-date-text"></span></label>
                        <div id="slots-grid" class="slots-grid"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Your Address</label>
                        <input type="text" id="address" name="address" required value="<?php echo $customer_address_string; ?>">
                    </div>
                    <button type="submit" class="submit-btn" id="submit-booking-btn">Send Booking Request</button>
                </form>
            </div>

        </div>
    </main>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>

    <?php if ($customer_lat && $customer_lon && $worker['latitude'] && $worker['longitude']): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = document.body.classList.contains('dark-mode');

            const lightTileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            const lightAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
            
            const darkTileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            const darkAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';

            const tileUrl = isDarkMode ? darkTileUrl : lightTileUrl;
            const attribution = isDarkMode ? darkAttribution : lightAttribution;

            var map = L.map('map').setView([<?php echo $customer_lat; ?>, <?php echo $customer_lon; ?>], 13);

            L.tileLayer(tileUrl, { attribution: attribution }).addTo(map);

            L.marker([<?php echo $customer_lat; ?>, <?php echo $customer_lon; ?>]).addTo(map)
                .bindPopup('Your Location');

            L.marker([<?php echo $worker['latitude']; ?>, <?php echo $worker['longitude']; ?>]).addTo(map)
                .bindPopup('<?php echo htmlspecialchars($worker['full_name']); ?>\'s Location');
        });
    </script>
    <?php endif; ?>
</body>
</html>