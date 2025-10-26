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
$subServiceId = $_GET['sub_service_id']; // This remains to fetch the correct items

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

// Fetch Worker Details
try {
    $sql = "
        SELECT u.id, u.full_name, u.profile_image, u.latitude, u.longitude,
               u.address_line1, u.address_line2, u.city, u.state, u.pincode,
               wp.bio, wp.experience_years, wp.hourly_rate"; // Keep hourly_rate if needed elsewhere

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

// Fetch Service Name and Items
$subServiceItems = [];
$subServiceName = '';
try {
    $stmt = $conn->prepare("SELECT name FROM public.sub_services WHERE id = ?");
    $stmt->execute([$subServiceId]);
    $subServiceName = $stmt->fetchColumn();

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
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
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
          --success-color: #10b981; /* Added */
          --danger-color: #ef4444; /* Added */
          --info-color: #3b82f6; /* Added */
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
          --success-color: #34d399; /* Added */
          --danger-color: #f87171; /* Added */
          --info-color: #60a5fa; /* Added */
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
        }
        .modal-content {
            background-color: var(--background-color-card);
            padding: 20px;
            border: 1px solid var(--border-color);
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            color: var(--text-color-dark);
            box-shadow: var(--box-shadow);
        }

        /* Add or adjust styles for coupon section */
        .coupon-section {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
        }
        .coupon-section label { font-weight: 500; display: block; margin-bottom: 0.5rem; color: var(--text-color-dark); }
        .coupon-input-group { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
        .coupon-input-group input[type="text"] { flex-grow: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--hover-color); color: var(--text-color-dark); text-transform: uppercase; }
        .coupon-input-group button { padding: 10px 15px; flex-shrink: 0; background-color: var(--primary-color); color: var(--text-color-white); border:none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s, opacity 0.2s; }
        .coupon-input-group button:disabled { opacity: 0.6; cursor: not-allowed; }
        .coupon-message { font-size: 0.85rem; margin-top: 0.5rem; min-height: 1.2em; font-weight: 500; }
        .remove-coupon-btn { font-size: 0.85rem; color: var(--danger-color); margin-top: 0.5rem; text-decoration: none; cursor: pointer; display: none; } /* Initially hidden */
        .price-summary { margin-top: 1rem; font-size: 0.9rem; color: var(--text-color-light); border-top: 1px dashed var(--border-color); padding-top: 1rem; display: none; /* Initially hidden */ }
        .price-summary p { margin: 0.3rem 0; }
        .price-summary hr { border: none; border-top: 1px solid var(--border-color); margin: 0.5rem 0; }
        .price-summary .discount-applied { color: var(--success-color); font-weight: 500; }
        .price-summary .final-price-display { font-weight: bold; font-size: 1.1em; color: var(--text-color-dark); }

         /* Styles for Available Offer Buttons */
         .available-offer-btn {
                background-color: var(--hover-color); border: 1px dashed var(--primary-color); color: var(--primary-color);
                padding: 5px 10px; border-radius: 6px; font-size: 0.8em; cursor: pointer; transition: background-color 0.2s, color 0.2s;
                margin-bottom: 0.5rem; /* Add some spacing */
            }
         .available-offer-btn code { background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 3px; font-weight: bold;}
         .available-offer-btn:hover { background-color: var(--primary-color); color: white; }
         body.dark-mode .available-offer-btn { background-color: rgba(251, 191, 36, 0.1); border-color: var(--primary-color); color: var(--primary-color); }
         body.dark-mode .available-offer-btn:hover { background-color: var(--primary-color); color: #111; }
         body.dark-mode .available-offer-btn code { background: rgba(255,255,255,0.1); }
         body.dark-mode .coupon-input-group input[type="text"] { background-color: #333; border-color: #555; }
         body.dark-mode .coupon-input-group button { color: #111; } /* If primary color is light */
        /* Add style for disabled input */
         #coupon-section-wrapper input[type="text"]:disabled { /* Target specific wrapper */
             background-color: var(--border-color);
             cursor: not-allowed;
             opacity: 0.7;
         }
         body.dark-mode #coupon-section-wrapper input[type="text"]:disabled {
              background-color: #444;
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
                    <span><i class="fas fa-star"></i> 4.8 Stars</span> <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
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
                    <input type="hidden" id="sub_service_item_id_hidden" name="sub_service_item_id" value=""> <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $userId; ?>">
                    <input type="hidden" name="sub_service_name" value="<?php echo htmlspecialchars($subServiceName); ?>"> <input type="hidden" id="booking_date" name="booking_date">
                    <input type="hidden" id="booking_time_combined" name="booking_time">
                    <input type="hidden" id="service_item_name" name="service_item_name"> <input type="hidden" id="price" name="price"> <input type="hidden" id="applied_offer_id" name="applied_offer_id" value="">
                    <input type="hidden" id="discount_amount_val" name="discount_amount" value="0">

                    <div class="form-group">
                        <label>Services for "<?php echo htmlspecialchars($subServiceName); ?>"</label>
                        <div id="service-selection-grid" class="services-grid">
                            <?php if (!empty($subServiceItems)): ?>
                                <?php foreach ($subServiceItems as $item): ?>
                                    <div class="service-option"
                                         data-item-id="<?php echo htmlspecialchars($item['id']); ?>" data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                         data-price="<?php echo htmlspecialchars($item['price']); ?>">
                                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="service-price">₹<?php echo htmlspecialchars(number_format((float)$item['price'], 2)); ?></span>
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

                    <div class="coupon-section" id="coupon-section-wrapper" style="display: none;">
                         <label for="coupon-code">Apply Coupon</label>
                        <div class="coupon-input-group">
                            <input type="text" id="coupon-code" placeholder="Enter coupon code" style="text-transform: uppercase;">
                            <button type="button" id="apply-coupon-btn">Apply</button>
                        </div>
                        <div id="coupon-message" class="coupon-message"></div>
                        <a href="#" id="remove-coupon-btn" class="remove-coupon-btn" style="display: none;">Remove Coupon</a>
                        <div id="available-offers-container" style="margin-top: 1rem;"></div>
                     </div>

                    <div class="price-summary" id="price-summary-display" style="display: none;">
                        <p>Original Price: <span id="original-price-display">₹0.00</span></p>
                        <p>Discount: <span id="discount-applied-display" class="discount-applied">-₹0.00</span></p>
                        <hr>
                        <p>Final Price: <span id="final-price-display" class="final-price-display">₹0.00</span></p>
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
            L.marker([<?php echo $customer_lat; ?>, <?php echo $customer_lon; ?>]).addTo(map).bindPopup('Your Location');
            L.marker([<?php echo $worker['latitude']; ?>, <?php echo $worker['longitude']; ?>]).addTo(map).bindPopup('<?php echo htmlspecialchars(addslashes($worker['full_name'])); ?>\'s Location');
        });
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelectionGrid = document.getElementById('service-selection-grid');
            const hiddenServiceItemInput = document.getElementById('service_item_name');
            const hiddenSubServiceItemInput = document.getElementById('sub_service_item_id_hidden'); // Get new hidden input
            const hiddenPriceInput = document.getElementById('price');
            const bookingForm = document.getElementById('booking-form');
            const submitButton = document.getElementById('submit-booking-btn');
            const successModal = document.getElementById('successModal');
            const countdownSpan = document.getElementById('countdown');

            // Coupon related elements
            const couponSectionWrapper = document.getElementById('coupon-section-wrapper');
            const couponCodeInput = document.getElementById('coupon-code');
            const applyCouponBtn = document.getElementById('apply-coupon-btn');
            const couponMessageDiv = document.getElementById('coupon-message');
            const removeCouponBtn = document.getElementById('remove-coupon-btn');
            const availableOffersContainer = document.getElementById('available-offers-container');
            const hiddenOfferIdInput = document.getElementById('applied_offer_id');
            const hiddenDiscountInput = document.getElementById('discount_amount_val');

            // Price summary elements
            const priceSummaryDisplay = document.getElementById('price-summary-display');
            const originalPriceDisplay = document.getElementById('original-price-display');
            const discountAppliedDisplay = document.getElementById('discount-applied-display');
            const finalPriceDisplay = document.getElementById('final-price-display');

            let selectedItemPrice = 0;
            const workerId = bookingForm.dataset.workerId;

            // --- Fetch and Display Available Offers ---
            function fetchAndDisplayOffers() {
                 if (!workerId || !selectedItemPrice || selectedItemPrice <= 0) {
                     availableOffersContainer.innerHTML = '';
                     return;
                 }
                 fetch(`/dailyfix/api/get_worker_offers.php?worker_id=${workerId}`)
                     .then(res => res.json())
                     .then(result => {
                         if (result.status === 'success' && result.data && result.data.length > 0) {
                             displayAvailableOffers(result.data); // Call function to display buttons
                         } else {
                             // Display "No coupons" message if API succeeds but data is empty, or if API fails
                             availableOffersContainer.innerHTML = '<p style="font-size: 0.9em; color: var(--text-color-light);">No coupons currently available.</p>';
                         }
                         // --- END MODIFIED SECTION ---
                     })
                     .catch(err => {
                        console.error("Error fetching available offers:", err);
                        availableOffersContainer.innerHTML = '<p style="font-size: 0.8em; color: var(--danger-color);">Could not load offers.</p>';
                    });
            }

            // --- Fetch and Display Available Offers ---
            function fetchAndDisplayOffers() {
                if (!workerId || !selectedItemPrice || selectedItemPrice <= 0) {
                     availableOffersContainer.innerHTML = ''; // Clear if no item selected or no worker
                     return;
                }
                 fetch(`/dailyfix/api/get_worker_offers.php?worker_id=${workerId}`)
                     .then(res => res.json())
                     .then(result => {
                         // --- Check API result and decide what to display ---
                         if (result.status === 'success' && result.data && result.data.length > 0) {
                             displayAvailableOffers(result.data); // Call function to display buttons if offers exist
                         } else {
                             availableOffersContainer.innerHTML = '<p style="font-size: 0.9em; color: var(--text-color-light);">No coupons currently available.</p>';
                         }
                     })
                     .catch(err => {
                        console.error("Error fetching available offers:", err);
                        availableOffersContainer.innerHTML = '<p style="font-size: 0.9em; color: var(--danger-color);">Could not load offers.</p>';
                    });
            }

            function displayAvailableOffers(offers) {
                // Check if the container exists (safety check)
                 if (!availableOffersContainer) {
                     console.error("Available offers container not found in displayAvailableOffers!");
                     return;
                 }

                // Build the HTML for the offer buttons
                let offersHtml = '<p style="font-size: 0.9em; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light);">Available Offers:</p>';
                offersHtml += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';
                offers.forEach(offer => {
                    let offerText = '';
                    if (offer.discount_type === 'percentage') offerText = `${parseFloat(offer.discount_value)}% off`;
                    else offerText = `₹${parseFloat(offer.discount_value).toFixed(2)} off`;
                    if (parseFloat(offer.min_booking_amount) > 0) offerText += ` (min ₹${parseFloat(offer.min_booking_amount).toFixed(2)})`;
                    let canApply = selectedItemPrice >= parseFloat(offer.min_booking_amount);
                    let titleText = canApply ? `Click to apply ${offer.coupon_code}` : `Requires min ₹${parseFloat(offer.min_booking_amount).toFixed(2)} service value`;
                    offersHtml += `<button type="button" class="available-offer-btn" data-code="${offer.coupon_code}" title="${titleText}" ${!canApply ? 'disabled style="opacity:0.5; cursor: not-allowed; border-style: dotted;"' : ''}><code>${offer.coupon_code}</code>: ${offerText}</button>`;
                });
                offersHtml += '</div>';
                availableOffersContainer.innerHTML = offersHtml;
                availableOffersContainer.querySelectorAll('.available-offer-btn:not([disabled])').forEach(btn => {
                    btn.addEventListener('click', function() {
                        couponCodeInput.value = this.dataset.code;
                        applyCouponBtn.click();
                    });
                });
            }

            // --- Service Selection Logic (UPDATED) ---
            if (serviceSelectionGrid) {
                serviceSelectionGrid.addEventListener('click', function(e) {
                    const clickedService = e.target.closest('.service-option');
                    if (!clickedService) return;

                    resetCouponState();

                    document.querySelectorAll('.service-option').forEach(option => {
                        option.classList.remove('selected');
                    });

                    clickedService.classList.add('selected');
                    hiddenServiceItemInput.value = clickedService.dataset.itemName;
                    hiddenSubServiceItemInput.value = clickedService.dataset.itemId; // <<< SET ITEM ID
                    hiddenPriceInput.value = clickedService.dataset.price;
                    selectedItemPrice = parseFloat(clickedService.dataset.price);

                    if (selectedItemPrice > 0) {
                        couponSectionWrapper.style.display = 'block';
                        fetchAndDisplayOffers();
                    } else {
                        couponSectionWrapper.style.display = 'none';
                        availableOffersContainer.innerHTML = '';
                    }
                });
            }

            // --- Reset Coupon State ---
            function resetCouponState() {
                couponCodeInput.value = '';
                couponCodeInput.disabled = false;
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerHTML = 'Apply';
                couponMessageDiv.textContent = '';
                couponMessageDiv.style.color = '';
                removeCouponBtn.style.display = 'none';
                priceSummaryDisplay.style.display = 'none';
                hiddenOfferIdInput.value = '';
                hiddenDiscountInput.value = '0';
                couponSectionWrapper.style.display = 'none';
                availableOffersContainer.innerHTML = '';
            }

            // --- Apply Coupon Logic (UPDATED to send item_id) ---
            applyCouponBtn.addEventListener('click', function() {
                const code = couponCodeInput.value.trim().toUpperCase();
                const subServiceItemId = hiddenSubServiceItemInput.value; // <<< GET ITEM ID

                if (!code) { couponMessageDiv.textContent = 'Please enter a coupon code.'; couponMessageDiv.style.color = 'var(--danger-color)'; return; }
                if (selectedItemPrice <= 0) { couponMessageDiv.textContent = 'Please select a service first.'; couponMessageDiv.style.color = 'var(--danger-color)'; return; }
                if (!subServiceItemId) { // <<< Added check
                    couponMessageDiv.textContent = 'Please select a specific service item first.';
                    couponMessageDiv.style.color = 'var(--danger-color)';
                    return;
                }

                applyCouponBtn.disabled = true;
                applyCouponBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                couponMessageDiv.textContent = '';

                const formData = new FormData();
                formData.append('worker_id', workerId);
                formData.append('coupon_code', code);
                formData.append('item_price', selectedItemPrice);
                formData.append('sub_service_item_id', subServiceItemId); // <<< PASS ITEM ID

                fetch('/dailyfix/api/validate_offer_pre_booking.php', { method: 'POST', body: formData })
                    .then(res => res.json().then(body => ({ ok: res.ok, body, status: res.status }))) // Get status code
                    .then(({ ok, body, status }) => {
                        applyCouponBtn.disabled = false; // Re-enable regardless of outcome

                        if (ok && body.status === 'success') {
                            couponMessageDiv.textContent = body.message + ` Discount: ₹${body.discount_amount}`;
                            couponMessageDiv.style.color = 'var(--success-color)';
                            applyCouponBtn.innerHTML = 'Applied';
                            applyCouponBtn.disabled = true; // Disable after successful apply
                            couponCodeInput.disabled = true; // Disable input too
                            removeCouponBtn.style.display = 'inline'; // Show remove button

                            // Update hidden fields
                            hiddenOfferIdInput.value = body.offer_id;
                            hiddenDiscountInput.value = parseFloat(body.discount_amount.replace(/,/g, ''));

                            // Update price summary display
                            originalPriceDisplay.textContent = `₹${body.original_price}`;
                            discountAppliedDisplay.textContent = `-₹${body.discount_amount}`;
                            finalPriceDisplay.textContent = `₹${body.final_price}`;
                            priceSummaryDisplay.style.display = 'block';

                        } else {
                             // Updated Error Handling for 409 Conflict
                             if (status === 409) { // HTTP 409 Conflict indicates already used
                                 couponMessageDiv.textContent = body.message || 'You have already used this coupon for this item.';
                             } else {
                                 couponMessageDiv.textContent = body.message || 'Error validating coupon.';
                             }
                             couponMessageDiv.style.color = 'var(--danger-color)';
                             applyCouponBtn.innerHTML = 'Apply';
                             priceSummaryDisplay.style.display = 'none';
                             hiddenOfferIdInput.value = '';
                             hiddenDiscountInput.value = '0';
                        }
                    })
                    .catch(error => {
                        console.error("Coupon Validation Error:", error);
                        couponMessageDiv.textContent = 'A network error occurred during validation.';
                        couponMessageDiv.style.color = 'var(--danger-color)';
                        applyCouponBtn.disabled = false;
                        applyCouponBtn.innerHTML = 'Apply';
                        priceSummaryDisplay.style.display = 'none';
                        hiddenOfferIdInput.value = '';
                        hiddenDiscountInput.value = '0';
                    });
            });

            // --- Remove Coupon Logic ---
            removeCouponBtn.addEventListener('click', function(e) {
                e.preventDefault();
                couponCodeInput.value = '';
                couponCodeInput.disabled = false;
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerHTML = 'Apply';
                couponMessageDiv.textContent = 'Coupon removed.';
                couponMessageDiv.style.color = 'var(--info-color)'; // Or neutral/light grey
                removeCouponBtn.style.display = 'none';
                priceSummaryDisplay.style.display = 'none';
                hiddenOfferIdInput.value = '';
                hiddenDiscountInput.value = '0';

                // Re-enable coupon section and fetch offers if a service is still selected
                if (selectedItemPrice > 0) {
                    couponSectionWrapper.style.display = 'block';
                    fetchAndDisplayOffers();
                } else {
                     couponSectionWrapper.style.display = 'none';
                }
            });


            // --- Form Submission Logic (UPDATED to check item_id) ---
            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!hiddenSubServiceItemInput.value) { // <<< CHECK ITEM ID
                    alert('Please select a specific service item.');
                    return;
                }
                if (!document.getElementById('booking_date').value || !document.getElementById('booking_time_combined').value) {
                    alert('Please select a date and time slot.');
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Request...';

                const formData = new FormData(bookingForm);

                fetch('/dailyfix/api/create_booking.php', { method: 'POST', body: formData })
                    .then(response => response.json().then(body => ({ ok: response.ok, body, status: response.status }))) // Get status code
                    .then(({ok, body, status}) => {
                        if (ok && body.status === 'success') {
                            successModal.style.display = 'flex'; // Use flex for centering
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
                             // Updated Error Handling for 409 Conflict
                             if (status === 409) { // HTTP 409 Conflict indicates already used
                                 alert('Error: ' + (body.message || 'You have already used the applied coupon for this item.'));
                             } else {
                                 alert('Error: ' + (body.message || 'Could not create booking.'));
                             }
                             submitButton.disabled = false;
                             submitButton.innerHTML = 'Send Booking Request';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Send Booking Request';
                    });
            });

            // Make modal clickable outside to close
            if (successModal) {
                successModal.addEventListener('click', function(event) {
                    if (event.target === successModal) {
                        // Redirect immediately or just hide
                        window.location.href = '/dailyfix/customer/bookings.php';
                    }
                });
            }
        });
    </script>
</body>
</html>