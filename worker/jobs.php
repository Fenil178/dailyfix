<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security: Ensure the user is a worker
if ($role !== 'worker') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

/**
 * Function to format a timestamp into a relative time string.
 */
function format_time_ago($timestamp_str)
{
    // Ensure the input is not null or empty before proceeding
    if (empty($timestamp_str)) {
        return 'unknown time';
    }
    try {
        // Attempt to create a DateTime object, handling potential errors
        $date = new DateTime($timestamp_str);
        $time = $date->getTimestamp();
    } catch (Exception $e) {
        // Log the error or handle it as needed
        error_log("Error parsing timestamp: " . $timestamp_str . " - " . $e->getMessage());
        return 'invalid date'; // Return a default value or indication of error
    }

    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    // Use the DateTime object for formatting
    return $date->format('M j, Y');
}


// Initialize arrays for jobs
$pendingJobs = [];
$upcomingJobs = [];
$inProgressJobs = [];
$completedJobs = [];
$cancelledJobs = []; // <-- New array for cancelled jobs
$worker_lat = null;
$worker_lon = null;

try {
    // Fetch worker's own location for distance calculation & map
    $stmt = $conn->prepare("SELECT latitude, longitude FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $worker_location = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($worker_location) {
        $worker_lat = $worker_location['latitude'];
        $worker_lon = $worker_location['longitude'];
    }

    // --- Fetch Pending Jobs ---
    $sql_pending = "
        SELECT b.id, b.service_details, b.booking_time, b.created_at, u.full_name as customer_name, u.profile_image as customer_avatar, u.latitude as customer_lat, u.longitude as customer_lon, u.address_line1, u.address_line2, u.city, u.state, u.pincode
        " . ($worker_lat && $worker_lon ? ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance" : "") . "
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'pending'
        ORDER BY b.booking_time DESC
    ";
    $params_pending = $worker_lat && $worker_lon ? [$worker_lat, $worker_lon, $worker_lat, $userId] : [$userId];
    $stmt = $conn->prepare($sql_pending);
    $stmt->execute($params_pending);
    $pendingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch Upcoming Jobs (Confirmed for today or in the future) ---
    $sql_upcoming = "
        SELECT b.*, u.full_name as customer_name, u.profile_image as customer_avatar, u.latitude as customer_lat, u.longitude as customer_lon, u.address_line1, u.address_line2, u.city, u.state, u.pincode
        " . ($worker_lat && $worker_lon ? ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance" : "") . "
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'confirmed' AND b.booking_time >= CURRENT_DATE
        ORDER BY b.booking_time ASC
    ";
    $params_upcoming = $worker_lat && $worker_lon ? [$worker_lat, $worker_lon, $worker_lat, $userId] : [$userId];
    $stmt = $conn->prepare($sql_upcoming);
    $stmt->execute($params_upcoming);
    $upcomingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch In-Progress Jobs ---
    $sql_in_progress = "
        SELECT b.*, u.full_name as customer_name, u.profile_image as customer_avatar, u.latitude as customer_lat, u.longitude as customer_lon, u.address_line1, u.address_line2, u.city, u.state, u.pincode
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'in_progress'
        ORDER BY b.booking_time DESC
    ";
    $stmt = $conn->prepare($sql_in_progress);
    $stmt->execute([$userId]);
    $inProgressJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --- Fetch Completed Jobs ---
    $sql_completed = "
        SELECT
            b.*,
            u.full_name as customer_name,
            u.profile_image as customer_avatar,
            r.rating,
            r.comment
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        LEFT JOIN public.reviews r ON b.id = r.booking_id
        WHERE b.worker_id = ? AND b.status = 'completed'
        ORDER BY b.booking_time DESC
    ";
    $stmt = $conn->prepare($sql_completed);
    $stmt->execute([$userId]);
    $completedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch Cancelled Jobs ---
    $sql_cancelled = "
        SELECT
            b.*,
            u.full_name as customer_name,
            u.profile_image as customer_avatar,
            COALESCE(b.rejection_reason, b.cancellation_reason) as reason_cancelled
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'cancelled'
        ORDER BY b.booking_time DESC
    ";
    $stmt = $conn->prepare($sql_cancelled);
    $stmt->execute([$userId]);
    $cancelledJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    error_log("Worker jobs fetch error: " . $e->getMessage());
    // $errorMessage = "Could not load job data. Please try again later.";
} catch (Exception $e) {
    error_log("General error in jobs.php: " . $e->getMessage());
    // $errorMessage = "An unexpected error occurred.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Job Management - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style>
        .job-card-body .map-container {
            height: 200px;
            width: 100%;
            border-radius: 8px;
            margin-top: 1rem;
            z-index: 1; /* Ensure map markers are clickable */
        }
        .review-section, .cancellation-reason-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .cancellation-reason-section strong {
            color: #dc3545;
        }
        .cancellation-reason-section p {
            margin-top: 5px;
            font-style: italic;
            color: #555;
        }

        /* --- Loading Spinner Styles --- */
        .btn .fa-spinner {
            margin-left: 8px; /* Add space between text and spinner */
            animation: fa-spin 1s infinite linear; /* Use Font Awesome's spin animation */
        }
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* --- Modal Styles --- */
        .modal {
          display: none; position: fixed; z-index: 1000;
          left: 0; top: 0; width: 100%; height: 100%;
          overflow: auto; background-color: rgba(0,0,0,0.4);
          animation-name: fadeIn; animation-duration: 0.3s;
        }
        .modal-content {
          background-color: #fefefe; margin: 10% auto; padding: 25px;
          border: 1px solid #ddd; width: 90%; max-width: 500px;
          border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
          position: relative; animation-name: slideIn; animation-duration: 0.4s;
        }
        @keyframes fadeIn { from {opacity: 0} to {opacity: 1} }
        @keyframes slideIn { from {top: -100px; opacity: 0} to {top: 0; opacity: 1} }
        .close-button {
          color: #aaa; position: absolute; top: 10px; right: 15px;
          font-size: 28px; font-weight: bold; line-height: 1; cursor: pointer;
        }
        .close-button:hover, .close-button:focus { color: #333; text-decoration: none; }
        .modal h2 { margin-top: 0; color: #333; font-size: 1.4em; margin-bottom: 10px; }
        .modal p { margin-bottom: 15px; color: #555; }
        .modal textarea {
            width: 100%; margin-top: 10px; margin-bottom: 15px; padding: 10px;
            border: 1px solid #ccc; border-radius: 4px; font-family: inherit;
            min-height: 80px; resize: vertical; box-sizing: border-box;
        }
        .modal-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        /* --- End Modal Styles --- */

    </style>
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>

<body>
    <main class="page-content">
        <div class="management-container">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h1 class="page-title">Job Management</h1>
                <a href="/dailyfix/profile.php#availability" class="btn btn-main" style="text-decoration:none;">Manage Availability</a>
            </div>

            <div class="tab-nav">
                <button class="tab-link active" data-tab="new-requests">New Requests (<?php echo count($pendingJobs); ?>)</button>
                <button class="tab-link" data-tab="upcoming-jobs">Upcoming Jobs (<?php echo count($upcomingJobs); ?>)</button>
                <button class="tab-link" data-tab="current-jobs">Current Jobs (<?php echo count($inProgressJobs); ?>)</button>
                <button class="tab-link" data-tab="completed-jobs">Completed Jobs (<?php echo count($completedJobs); ?>)</button>
                <button class="tab-link" data-tab="cancelled-jobs">Cancelled Jobs (<?php echo count($cancelledJobs); ?>)</button>
            </div>

            <div id="new-requests" class="tab-content active">
                <?php if (count($pendingJobs) > 0) : ?>
                    <div class="job-card-grid">
                        <?php foreach ($pendingJobs as $job) : ?>
                            <div class="job-card" id="job-card-<?php echo $job['id']; ?>">
                                <div class="job-card-header">
                                    <?php
                                    $customerAvatar = $job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png';
                                    if ($job['customer_avatar'] && strpos($job['customer_avatar'], '/') !== 0) {
                                        $customerAvatar = '/dailyfix/' . $job['customer_avatar'];
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Requested: <?php echo format_time_ago($job['created_at']); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Appointment:</strong> <?php
                                                                        try {
                                                                            $bookingTime = new DateTime($job['booking_time']);
                                                                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                                                            echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A"));
                                                                        } catch (Exception $e) { echo 'Invalid Date'; }
                                                                        ?></p>
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) { if (trim($line) !== '' && strpos($line, 'Address:') === false) { echo '<p>' . htmlspecialchars($line) . '</p>'; } }
                                    ?>
                                     <p><strong>Location:</strong> <?php echo htmlspecialchars(implode(', ', array_filter([$job['address_line1'], $job['address_line2'], $job['city'], $job['state']])) . ($job['pincode'] ? ' - ' . $job['pincode'] : '')); ?></p>
                                    <?php if (isset($job['distance'])) : ?>
                                        <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p>
                                    <?php endif; ?>

                                    <?php if ($job['customer_lat'] && $job['customer_lon'] && $worker_lat && $worker_lon) : ?>
                                        <div id="map-<?php echo $job['id']; ?>" class="map-container"></div>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                setTimeout(() => {
                                                    try {
                                                        const mapElement = document.getElementById('map-<?php echo $job['id']; ?>');
                                                        if(mapElement && typeof L !== 'undefined') {
                                                            var map = L.map(mapElement).setView([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>], 13);
                                                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(map);
                                                            L.marker([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>]).addTo(map).bindPopup("Customer");
                                                            L.marker([<?php echo $worker_lat; ?>, <?php echo $worker_lon; ?>]).addTo(map).bindPopup("You");
                                                        }
                                                    } catch (e) { console.error("Map init error map-<?php echo $job['id']; ?>:", e); }
                                                }, 100);
                                            });
                                        </script>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-actions">
                                    <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'confirmed', '<?php echo $job['booking_time']; ?>', this)" class="btn accept">Accept</button>
                                    <button onclick="openDeclineModal(<?php echo $job['id']; ?>)" class="btn decline">Decline</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state"> <i class="fas fa-inbox"></i> <h3>No New Job Requests</h3> <p>You don't have any pending job requests.</p> </div>
                <?php endif; ?>
            </div>

            <div id="upcoming-jobs" class="tab-content">
                 <?php if (count($upcomingJobs) > 0) : ?>
                    <div class="job-card-grid">
                        <?php foreach ($upcomingJobs as $job) : ?>
                             <div class="job-card">
                                 <div class="job-card-header">
                                     <?php /* Avatar logic */ $customerAvatar = $job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'; if ($job['customer_avatar'] && strpos($job['customer_avatar'], '/') !== 0) { $customerAvatar = '/dailyfix/' . $job['customer_avatar']; } ?>
                                     <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer" class="job-card-avatar">
                                     <div class="job-card-customer-info">
                                         <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                         <p>Scheduled for: <?php try { $bookingTime = new DateTime($job['booking_time']); $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A")); } catch (Exception $e) { echo 'Invalid Date'; } ?></p>
                                     </div>
                                 </div>
                                 <div class="job-card-body">
                                     <p><strong>Details:</strong></p>
                                     <?php $details = explode("\n", $job['service_details']); foreach ($details as $line) { if (trim($line) !== '' && strpos($line, 'Address:') === false) { echo '<p>' . htmlspecialchars($line) . '</p>'; } } ?>
                                     <p><strong>Location:</strong> <?php echo htmlspecialchars(implode(', ', array_filter([$job['address_line1'], $job['address_line2'], $job['city'], $job['state']])) . ($job['pincode'] ? ' - ' . $job['pincode'] : '')); ?></p>
                                     <?php if (isset($job['distance'])) : ?> <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p> <?php endif; ?>
                                     <p><strong>Status:</strong> <span class="item-status <?php echo htmlspecialchars($job['status']); ?>"><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($job['status']))); ?></span></p>
                                 </div>
                                 <div class="job-card-actions">
                                     <?php try { $bookingTimestamp = strtotime($job['booking_time']); $currentTimestamp = time(); if ($job['status'] === 'confirmed' && $currentTimestamp >= $bookingTimestamp) : ?>
                                         <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'in_progress', null, this)" class="btn btn-main" style="background-color: #f59e0b; color: #fff;">Start Job</button>
                                     <?php endif; } catch (Exception $e) { error_log("Timestamp error: " . $e->getMessage()); } ?>
                                     <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary">View Details</a>
                                 </div>
                             </div>
                         <?php endforeach; ?>
                     </div>
                 <?php else : ?>
                     <div class="empty-state"> <i class="fas fa-calendar-alt"></i> <h3>No Upcoming Jobs</h3> <p>You have no confirmed jobs scheduled.</p> </div>
                 <?php endif; ?>
             </div>

            <div id="current-jobs" class="tab-content">
                <?php if (count($inProgressJobs) > 0) : ?>
                    <div class="job-card-grid">
                        <?php foreach ($inProgressJobs as $job) : ?>
                             <div class="job-card">
                                 <div class="job-card-header">
                                     <?php /* Avatar logic */ $customerAvatar = $job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'; if ($job['customer_avatar'] && strpos($job['customer_avatar'], '/') !== 0) { $customerAvatar = '/dailyfix/' . $job['customer_avatar']; } ?>
                                     <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer" class="job-card-avatar">
                                     <div class="job-card-customer-info">
                                         <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                         <p>Status: <span class="item-status in_progress">In Progress</span></p>
                                     </div>
                                 </div>
                                 <div class="job-card-body">
                                     <p><strong>Appointment:</strong> <?php try { $bookingTime = new DateTime($job['booking_time']); $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A")); } catch (Exception $e) { echo 'Invalid Date'; } ?></p>
                                     <p><strong>Details:</strong></p>
                                     <?php $details = explode("\n", $job['service_details']); foreach ($details as $line) { if (trim($line) !== '' && strpos($line, 'Address:') === false) { echo '<p>' . htmlspecialchars($line) . '</p>'; } } ?>
                                     <p><strong>Location:</strong> <?php echo htmlspecialchars(implode(', ', array_filter([$job['address_line1'], $job['address_line2'], $job['city'], $job['state']])) . ($job['pincode'] ? ' - ' . $job['pincode'] : '')); ?></p>
                                 </div>
                                 <div class="job-card-actions">
                                     <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn accept">View Details & Complete</a>
                                 </div>
                             </div>
                         <?php endforeach; ?>
                     </div>
                 <?php else : ?>
                     <div class="empty-state"> <i class="fas fa-clock"></i> <h3>No Jobs In Progress</h3> <p>You are not currently working on any job.</p> </div>
                 <?php endif; ?>
             </div>

            <div id="completed-jobs" class="tab-content">
                 <?php if (count($completedJobs) > 0) : ?>
                    <div class="job-card-grid">
                        <?php foreach ($completedJobs as $job) : ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <?php /* Avatar logic */ $customerAvatar = $job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'; if ($job['customer_avatar'] && strpos($job['customer_avatar'], '/') !== 0) { $customerAvatar = '/dailyfix/' . $job['customer_avatar']; } ?>
                                    <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Completed on: <?php try { $bookingTime = new DateTime($job['booking_time']); $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); echo htmlspecialchars($bookingTime->format("D, M j, Y")); } catch (Exception $e) { echo 'Invalid Date'; } ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong></p>
                                    <?php $details = explode("\n", $job['service_details']); foreach ($details as $line) { if (trim($line) !== '') { echo '<p>' . htmlspecialchars($line) . '</p>'; } } ?>
                                    <?php if (!empty($job['final_cost'])) : ?> <p><strong>Final Cost:</strong> ₹<?php echo htmlspecialchars(number_format($job['final_cost'], 2)); ?></p> <?php endif; ?>
                                    <p><strong>Status:</strong> <span class="item-status completed">Completed</span></p>
                                    <?php if ($job['rating']): ?>
                                        <div class="review-section">
                                            <strong>Customer Review:</strong>
                                            <div class="rating" style="margin: 5px 0;">
                                                <?php for ($i = 0; $i < 5; $i++): ?> <i class="fas fa-star" style="color: <?php echo $i < $job['rating'] ? '#ffc107' : '#e0e0e0'; ?>;"></i> <?php endfor; ?>
                                                <span style="margin-left: 5px;">(<?php echo $job['rating']; ?>/5)</span>
                                            </div>
                                            <?php if ($job['comment']): ?> <p style="font-style: italic; color: #555; margin-top: 5px;">"<?php echo nl2br(htmlspecialchars($job['comment'])); ?>"</p> <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-actions">
                                     <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary"> <i class="fas fa-info-circle"></i> See Details </a>
                                     <?php if (!empty($job['final_cost'])): ?> <a href="/dailyfix/generate_invoice.php?id=<?php echo $job['id']; ?>" class="btn btn-main-custom" target="_blank" rel="noopener noreferrer"> <i class="fas fa-download"></i> Download Invoice </a> <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                 <?php else : ?>
                     <div class="empty-state"> <i class="fas fa-check-circle"></i> <h3>No Completed Jobs</h3> <p>You haven't completed any jobs yet.</p> </div>
                 <?php endif; ?>
             </div>

            <div id="cancelled-jobs" class="tab-content">
                <?php if (count($cancelledJobs) > 0) : ?>
                    <div class="job-card-grid">
                        <?php foreach ($cancelledJobs as $job) : ?>
                            <div class="job-card cancelled-job">
                                <div class="job-card-header">
                                    <?php /* Avatar logic */ $customerAvatar = $job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'; if ($job['customer_avatar'] && strpos($job['customer_avatar'], '/') !== 0) { $customerAvatar = '/dailyfix/' . $job['customer_avatar']; } ?>
                                    <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Original Appointment: <?php try { $bookingTime = new DateTime($job['booking_time']); $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A")); } catch (Exception $e) { echo 'Invalid Date'; } ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong></p>
                                    <?php $details = explode("\n", $job['service_details']); foreach ($details as $line) { if (trim($line) !== '') { echo '<p>' . htmlspecialchars($line) . '</p>'; } } ?>
                                    <p><strong>Status:</strong> <span class="item-status cancelled">Cancelled</span></p>
                                     <?php if (!empty($job['reason_cancelled'])): ?>
                                        <div class="cancellation-reason-section"> <strong>Reason for Cancellation:</strong> <p><?php echo nl2br(htmlspecialchars($job['reason_cancelled'])); ?></p> </div>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-actions">
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary"> <i class="fas fa-info-circle"></i> See Details </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state"> <i class="fas fa-ban"></i> <h3>No Cancelled Jobs</h3> <p>No cancelled jobs found.</p> </div>
                <?php endif; ?>
            </div>


        </div>

        <div id="decline-reason-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeDeclineModal()">&times;</span>
                <h2>Reason for Declining Job</h2>
                <p>Please provide a reason for declining this job request.</p>
                <textarea id="decline-reason-text" rows="4" placeholder="Enter reason here..."></textarea>
                <div class="modal-buttons">
                    <button id="confirm-decline-btn" class="btn decline">Confirm Decline</button>
                    <button onclick="closeDeclineModal()" class="btn btn-secondary">Cancel</button>
                </div>
                <input type="hidden" id="decline-booking-id">
            </div>
        </div>
        </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Tab Functionality ---
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            function setActiveTab() {
                const hash = window.location.hash.substring(1);
                let targetTabId = 'new-requests'; // Default
                let validHash = false;
                tabLinks.forEach(link => { if (link.getAttribute('data-tab') === hash) validHash = true; });
                if (hash && validHash) targetTabId = hash;

                tabLinks.forEach(link => { link.classList.toggle('active', link.getAttribute('data-tab') === targetTabId); });
                tabContents.forEach(content => { content.classList.toggle('active', content.id === targetTabId); });
            }

            setActiveTab(); // Initial load

            tabLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const tabId = link.getAttribute('data-tab');
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    link.classList.add('active');
                    const targetContent = document.getElementById(tabId);
                    if (targetContent) targetContent.classList.add('active');
                    history.replaceState(null, null, '#' + tabId); // Use replaceState for cleaner history
                });
            });

            window.addEventListener('hashchange', setActiveTab); // Handle back/forward

            // --- Modal Functions ---
            const declineModal = document.getElementById('decline-reason-modal');
            const declineReasonText = document.getElementById('decline-reason-text');
            const confirmDeclineBtn = document.getElementById('confirm-decline-btn');
            const declineBookingIdInput = document.getElementById('decline-booking-id');

            window.openDeclineModal = function(bookingId) {
                if (!declineModal) return;
                declineBookingIdInput.value = bookingId;
                declineReasonText.value = '';
                declineModal.style.display = 'block';
                declineReasonText.focus();
            }

            window.closeDeclineModal = function() {
                 if (declineModal) declineModal.style.display = 'none';
            }

            if (confirmDeclineBtn) {
                confirmDeclineBtn.addEventListener('click', () => {
                    const bookingId = declineBookingIdInput.value;
                    const reason = declineReasonText.value.trim();
                    if (!reason) { alert('Please provide a reason.'); declineReasonText.focus(); return; }
                    handleJobAction(bookingId, 'cancelled', null, confirmDeclineBtn, reason);
                });
            }

            window.onclick = function(event) { if (event.target == declineModal) closeDeclineModal(); }

             // --- Job Action Handler (UI Improved) ---
             window.handleJobAction = function(bookingId, status, bookingTime, buttonElement, reason = null) {
                // Store original HTML content
                const originalHtml = buttonElement.innerHTML;
                let loadingText = 'Processing...'; // Default loading text

                 // Determine loading text based on action
                 if (status === 'confirmed') loadingText = 'Accepting...';
                 else if (status === 'cancelled') loadingText = 'Declining...';
                 else if (status === 'in_progress') loadingText = 'Starting...';

                 // Add spinner icon to loading text
                 const loadingHtml = `${loadingText} <i class="fas fa-spinner fa-spin"></i>`;


                const actionContainer = buttonElement.closest('.job-card-actions') || buttonElement.closest('.modal-buttons');
                let buttonsInContainer = [];
                if (actionContainer) {
                    buttonsInContainer = actionContainer.querySelectorAll('.btn');
                    buttonsInContainer.forEach(btn => {
                        btn.disabled = true;
                        btn.style.opacity = '0.6';
                        // Set specific loading text for the clicked button
                        if (btn === buttonElement) {
                            btn.innerHTML = loadingHtml;
                        } else {
                            // Optionally dim other buttons without changing text drastically
                            btn.innerHTML = '...'; // Keep it simple for others
                        }
                    });
                } else {
                    buttonElement.disabled = true;
                    buttonElement.style.opacity = '0.6';
                    buttonElement.innerHTML = loadingHtml;
                }


                let url = `/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`;
                if (bookingTime) url += `&booking_time=${encodeURIComponent(bookingTime)}`;
                if (status === 'cancelled' && reason) url += `&reason=${encodeURIComponent(reason)}`;

                fetch(url)
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            return response.text().then(text => { console.error("Non-JSON response:", text); throw new Error("Unexpected server response format."); });
                        }
                    })
                    .then(data => {
                         if (data.status && data.status === 'error') throw new Error(data.message || 'Unknown error.');

                        if (data.status === 'success') {
                            if (buttonElement.id === 'confirm-decline-btn') closeDeclineModal();
                            const card = document.getElementById(`job-card-${bookingId}`);
                            if (card) {
                                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.95)';
                                setTimeout(() => { window.location.reload(); }, 400);
                            } else { window.location.reload(); }
                        } else { throw new Error(data.message || 'Update failed.'); }
                    })
                    .catch(error => {
                        console.error('Action error:', error);
                        alert('Error: ' + error.message + '. Please try again.');
                         // Restore buttons on error
                         if (actionContainer) {
                            buttonsInContainer.forEach(btn => {
                                btn.disabled = false;
                                btn.style.opacity = '1';
                                // Restore original HTML (important!)
                                if (btn === buttonElement) {
                                    btn.innerHTML = originalHtml;
                                } else {
                                     // Restore based on class
                                     if (btn.classList.contains('accept')) btn.innerHTML = 'Accept';
                                     else if (btn.classList.contains('decline')) btn.innerHTML = 'Decline';
                                     else if (btn.classList.contains('btn-main') && btn.textContent.includes('...')) btn.innerHTML = 'Start Job'; // Approximate restore
                                     else if (btn.id === 'confirm-decline-btn') btn.innerHTML = 'Confirm Decline';
                                     else btn.innerHTML = btn.dataset.originalHtml || 'Action'; // Fallback / Consider storing original HTML in data attribute
                                }
                            });
                         } else {
                                buttonElement.disabled = false;
                                buttonElement.style.opacity = '1';
                                buttonElement.innerHTML = originalHtml;
                         }
                    });
            } // End handleJobAction
        }); // End DOMContentLoaded
    </script>


    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>

</html>