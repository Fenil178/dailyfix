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

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['sub_service_id']) || !is_numeric($_GET['sub_service_id'])) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$workerId = $_GET['id'];
$subServiceId = $_GET['sub_service_id'];

$worker = null;
$customer_lat = null;
$customer_lon = null;
$customer_address_string = '';

// Get customer's location and full address
try {
    $stmt = $conn->prepare("SELECT latitude, longitude, address_line1, address_line2, city, state, pincode FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $customer_location = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer_location) {
        $customer_lat = $customer_location['latitude'];
        $customer_lon = $customer_location['longitude'];
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

$subServiceItems = [];
$subServiceName = '';

try {
    // 1. Get the sub-service name
    $stmt = $conn->prepare("SELECT name FROM public.sub_services WHERE id = ?");
    $stmt->execute([$subServiceId]);
    $subServiceName = $stmt->fetchColumn();

    // 2. Fetch the specific sub-service items AND THEIR PRICES for this worker
    // MODIFIED: Added wssi.price to the SELECT statement
    $stmt = $conn->prepare("
        SELECT ssi.id, ssi.name, ssi.icon, wssi.price
        FROM public.worker_sub_service_items wssi
        JOIN public.sub_service_items ssi ON wssi.sub_service_item_id = ssi.id
        WHERE wssi.user_id = ? AND ssi.sub_service_id = ?
        ORDER BY ssi.name
    ");
    $stmt->execute([$workerId, $subServiceId]);
    $subServiceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch sub-service items: " . $e->getMessage());
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
    <style>
        :root {
          /* === Light Mode Variables === */
          --primary-color: #3b82f6;
          --accent-color: #ffc107;
          --background-color-body: #f9f9f9;
          --background-color-card: #ffffff;
          --hover-color: #f0f0f0;
          --border-color: #e2e8f0;
          --text-color-dark: #1e293b;
          --text-color-light: #64748b;
          --text-color-white: #ffffff;
          --box-shadow: 0 4px 20px -8px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode {
          /* === Dark Mode Variables === */
          --primary-color: #fbbf24;
          --accent-color: #ffc107;
          --background-color-body: #121212;
          --background-color-card: #1f1f1f;
          --hover-color: #2c2c2c;
          --border-color: #334155;
          --text-color-dark: #f1f5f9;
          --text-color-light: #94a3b8;
          --text-color-white: #000000;
          --box-shadow: 0 4px 20px -8px rgba(0, 0, 0, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: var(--background-color-card);
            margin: 15% auto;
            padding: 20px;
            border: 1px solid var(--border-color);
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            color: var(--text-color-dark);
        }
    </style>

</head>
<body>
    <main class="page-content">  
        <div class="page-header" style="max-width: 1100px; margin: 2rem auto 1rem auto; padding: 0 1rem;">
            <a href="<?php echo $backLink; ?>" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo $backLinkText; ?></a>        
        </div>
        <div class="booking-container">
            <div class="worker-profile-panel">
                <?php
                    $workerAvatar = $worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png';
                    if ($worker['profile_image'] && strpos($worker['profile_image'], '/') !== 0) {
                        $workerAvatar = '/dailyfix/' . $worker['profile_image'];
                    }
                ?>
                <img src="<?php echo htmlspecialchars($workerAvatar); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="profile-avatar-custom">
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
                <form id="booking-form" method="POST" data-worker-id="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="sub_service_name" value="<?php echo htmlspecialchars($subServiceName); ?>">
                    <input type="hidden" id="booking_date" name="booking_date">
                    <input type="hidden" id="booking_time_combined" name="booking_time">
                    <input type="hidden" id="service_item_name" name="service_item_name">

                    <div class="form-group">
                        <label>Services for "<?php echo htmlspecialchars($subServiceName); ?>"</label>
                        <div id="service-selection-grid" class="services-grid">
                            <?php if (!empty($subServiceItems)): ?>
                                <?php foreach ($subServiceItems as $item): ?>
                                    <div class="service-option" data-item-name="<?php echo htmlspecialchars($item['name']); ?>">
                                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="service-price">₹<?php echo htmlspecialchars($item['price']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>This worker has not listed any items for this service.</p>
                            <?php endif; ?>
                        </div>
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

    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Booking Request Sent!</h2>
            <p>Your request has been sent successfully. Redirecting to your bookings page in <span id="countdown">5</span> seconds...</p>
        </div>
    </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelectionGrid = document.getElementById('service-selection-grid');
            const hiddenServiceItemInput = document.getElementById('service_item_name');
            const bookingForm = document.getElementById('booking-form');
            const submitButton = document.getElementById('submit-booking-btn');
            const successModal = document.getElementById('successModal');
            const countdownSpan = document.getElementById('countdown');
            
            if (serviceSelectionGrid) {
                serviceSelectionGrid.addEventListener('click', function(e) {
                    const clickedService = e.target.closest('.service-option');
                    if (!clickedService) return;

                    document.querySelectorAll('.service-option').forEach(option => {
                        option.classList.remove('selected');
                    });

                    clickedService.classList.add('selected');
                    hiddenServiceItemInput.value = clickedService.dataset.itemName;
                });
            }

            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!hiddenServiceItemInput.value) {
                    alert('Please select a service item before sending the booking request.');
                    return;
                }

                const formData = new FormData(bookingForm);

                fetch('/dailyfix/api/create_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        successModal.style.display = 'block';
                        let countdown = 5;
                        countdownSpan.textContent = countdown;
                        const interval = setInterval(() => {
                            countdown--;
                            countdownSpan.textContent = countdown;
                            if (countdown <= 0) {
                                clearInterval(interval);
                                window.location.href = '/dailyfix/customer/bookings.php';
                            }
                        }, 1000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    </script>
</body>
</html>