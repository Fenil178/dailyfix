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
    $time = strtotime($timestamp_str);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

// Initialize arrays for jobs
$pendingJobs = [];
$upcomingJobs = [];
$inProgressJobs = [];
$completedJobs = [];
$cancelledJobs = []; // <-- NEW: Added array for cancelled jobs
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

    // --- NEW: Fetch Cancelled Jobs ---
    $sql_cancelled = "
        SELECT 
            b.*, 
            u.full_name as customer_name, 
            u.profile_image as customer_avatar
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
            z-index: 1;
            /* Ensure map markers are clickable */
        }

        .review-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        /* --- New Search Bar UI --- */
        .search-bar-container {
            margin-bottom: 2rem;
            position: relative;
        }

        #job-search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            /* Make room for icon */
            border-radius: 8px;
            border: 1px solid var(--border-color, #ddd);
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        #job-search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .search-bar-container .fa-search {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            /* A softer gray */
        }

        /* --- Responsive Grid --- */
        .job-card-grid {
            display: grid;
            /* This makes the grid responsive */
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        /* --- Card UI Polish --- */
        .job-card {
            transition: box-shadow 0.3s ease, transform 0.3s ease;
            border: 1px solid var(--border-color, #eee);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* --- Responsive Typography & Layout --- */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.75rem;
                /* Smaller title on mobile */
            }

            .management-container {
                padding: 1rem;
            }

            .tab-nav {
                /* Allows tabs to scroll horizontally on mobile */
                display: flex;
                overflow-x: auto;
                white-space: nowrap;
                -ms-overflow-style: none;
                /* IE and Edge */
                scrollbar-width: none;
                /* Firefox */
            }

            .tab-nav::-webkit-scrollbar {
                display: none;
                /* Chrome, Safari */
            }

            .tab-nav .tab-link {
                flex: 0 0 auto;
                /* Prevents tabs from shrinking */
            }
        }
    </style>
    <style>
        /* Common skeleton styles (loader, shimmer, dark-mode) */
        .skeleton-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: var(--background-color-body, #f9f9f9);
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .skeleton-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .skeleton-container {
            max-width: 1100px;
            width: 100%;
            padding: 0 1rem;
            margin: 1rem auto;
            margin-top: 80px;
            /* Adjust to match your header's height */
        }

        @keyframes shimmer {
            0% {
                background-position: -400px 0;
            }

            100% {
                background-position: 400px 0;
            }
        }

        .skeleton {
            animation: shimmer 1.5s infinite linear;
            background: linear-gradient(to right,
                    var(--hover-color, #f0f0f0) 8%,
                    var(--border-color, #e2e8f0) 18%,
                    var(--hover-color, #f0f0f0) 33%);
            background-size: 800px 104px;
            border-radius: 6px;
        }

        /* Page-specific skeleton layout for worker/jobs.php */
        .skeleton-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
        }

        .skeleton-title {
            height: 38px;
            width: 300px;
        }

        .skeleton-header-btn {
            height: 42px;
            width: 180px;
        }

        /* --- New Skeleton Style for Search --- */
        .skeleton-search-bar {
            height: 45px;
            /* Match the real search bar height */
            width: 100%;
            margin-bottom: 2rem;
            /* Match the real search bar margin */
        }

        .skeleton-tabs {
            display: flex;
            gap: 1rem;
            height: 36px;
            margin-bottom: 2rem;
        }

        .skeleton-tab-item {
            width: 140px;
            height: 100%;
        }

        .skeleton-job-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .skeleton-job-card {
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }

        .skeleton-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .skeleton-avatar {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .skeleton-info {
            flex-grow: 1;
        }

        .skeleton-line {
            height: 16px;
            border-radius: 4px;
        }

        .skeleton-line.name {
            height: 20px;
            width: 60%;
            margin-bottom: 0.5rem;
        }

        .skeleton-line.date {
            height: 14px;
            width: 40%;
        }

        .skeleton-card-body {
            padding-top: 1rem;
            border-top: 1px solid var(--border-color, #e2e8f0);
        }

        .skeleton-line.detail {
            width: 90%;
            margin-bottom: 0.75rem;
        }

        .skeleton-line.detail-short {
            width: 70%;
            margin-bottom: 1.5rem;
        }

        .skeleton-map {
            height: 150px;
            width: 100%;
        }

        .skeleton-card-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .skeleton-action-btn {
            height: 40px;
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .skeleton-job-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>

<body>
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton-header-bar">
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-header-btn"></div>
            </div>

            <div class="skeleton skeleton-search-bar"></div>

            <div class="skeleton-tabs">
                <div class="skeleton skeleton-tab-item"></div>
                <div class="skeleton skeleton-tab-item"></div>
                <div class="skeleton skeleton-tab-item"></div>
                <div class="skeleton skeleton-tab-item"></div>
                <div class="skeleton skeleton-tab-item"></div> </div>

            <div class="skeleton-job-grid">
                <div class="skeleton-job-card">
                    <div class="skeleton-card-header">
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="skeleton-info">
                            <div class="skeleton skeleton-line name"></div>
                            <div class="skeleton skeleton-line date"></div>
                        </div>
                    </div>
                    <div class="skeleton-card-body">
                        <div class="skeleton skeleton-line detail"></div>
                        <div class="skeleton skeleton-line detail-short"></div>
                        <div class="skeleton skeleton-map"></div>
                    </div>
                    <div class="skeleton-card-actions">
                        <div class="skeleton skeleton-action-btn"></div>
                        <div class="skeleton skeleton-action-btn"></div>
                    </div>
                </div>

                <div class="skeleton-job-card">
                    <div class="skeleton-card-header">
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="skeleton-info">
                            <div class="skeleton skeleton-line name"></div>
                            <div class="skeleton skeleton-line date"></div>
                        </div>
                    </div>
                    <div class="skeleton-card-body">
                        <div class="skeleton skeleton-line detail"></div>
                        <div class="skeleton skeleton-line detail-short"></div>
                        <div class="skeleton skeleton-map"></div>
                    </div>
                    <div class="skeleton-card-actions">
                        <div class="skeleton skeleton-action-btn"></div>
                        <div class="skeleton skeleton-action-btn"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="management-container">
            <div class="management-header">
                <h1 class="page-title">Job Management</h1>
                <a href="/dailyfix/profile.php#availability" class="btn btn-main" style="text-decoration:none;">Manage Availability</a>
            </div>

            <div class="search-bar-container">
                <i class="fas fa-search"></i>
                <input type="text" id="job-search-input" class="form-control" placeholder="Search by customer, service, date, status...">
            </div>

            <div class="tab-nav">
                <button class="tab-link active" data-tab="new-requests">New Requests (<?php echo count($pendingJobs); ?>)</button>
                <button class="tab-link" data-tab="upcoming-jobs">Upcoming Jobs (<?php echo count($upcomingJobs); ?>)</button>
                <button class="tab-link" data-tab="current-jobs">Current Jobs (<?php echo count($inProgressJobs); ?>)</button>
                <button class="tab-link" data-tab="completed-jobs">Completed Jobs (<?php echo count($completedJobs); ?>)</button>
                <button class="tab-link" data-tab="cancelled-jobs">Cancelled (<?php echo count($cancelledJobs); ?>)</button> </div>

            <div id="new-requests" class="tab-content active">
                <div class="job-card-grid" id="new-requests-grid">
                    <?php if (count($pendingJobs) > 0) : ?>
                        <?php foreach ($pendingJobs as $job) : ?>
                            <?php
                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $searchMonth = $bookingTime->format("F"); // "F" = full month name
                            $displayDate = $bookingTime->format("D, M j, Y, g:i A");
                            ?>
                            <div class="job-card" id="job-card-<?php echo $job['id']; ?>" data-search-terms="<?php echo strtolower(htmlspecialchars($searchMonth)); ?>">
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
                                    <p><strong>Appointment:</strong> <?php echo htmlspecialchars($displayDate); ?></p>
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) {
                                        if (strpos($line, 'Address:') === false) {
                                            echo '<p>' . htmlspecialchars($line) . '</p>';
                                        }
                                    }
                                    ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['address_line1'] . ', ' . $job['address_line2'] . ', ' . $job['city'] . ', ' . $job['state'] . ' - ' . $job['pincode']); ?></p>
                                    <?php if (isset($job['distance'])) : ?>
                                        <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p>
                                    <?php endif; ?>

                                    <?php if ($job['customer_lat'] && $job['customer_lon'] && $worker_lat && $worker_lon) : ?>
                                        <div id="map-<?php echo $job['id']; ?>" class="map-container"></div>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                var map = L.map('map-<?php echo $job['id']; ?>').setView([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>], 13);
                                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                    attribution: '&copy; OpenStreetMap contributors'
                                                }).addTo(map);
                                                L.marker([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>]).addTo(map).bindPopup("Customer's Location").openPopup();
                                                L.marker([<?php echo $worker_lat; ?>, <?php echo $worker_lon; ?>]).addTo(map).bindPopup("Your Location");
                                            });
                                        </script>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-actions">
                                    <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'confirmed', '<?php echo $job['booking_time']; ?>', this)" class="btn accept">Accept</button>
                                    <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'cancelled', null, this)" class="btn decline">Decline</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="new-requests-empty" <?php if (count($pendingJobs) > 0) echo 'style="display: none;"'; ?>>
                    <i class="fas fa-inbox"></i>
                    <h3 id="new-requests-empty-title">No New Job Requests</h3>
                    <p id="new-requests-empty-text">You don't have any pending job requests at the moment.</p>
                </div>
            </div>

            <div id="upcoming-jobs" class="tab-content">
                <div class="job-card-grid" id="upcoming-jobs-grid">
                    <?php if (count($upcomingJobs) > 0) : ?>
                        <?php foreach ($upcomingJobs as $job) : ?>
                            <?php
                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $searchMonth = $bookingTime->format("F");
                            $displayDate = $bookingTime->format("D, M j, Y, g:i A");
                            ?>
                            <div class="job-card" data-search-terms="<?php echo strtolower(htmlspecialchars($searchMonth)); ?>">
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
                                        <p>Scheduled for: <?php echo htmlspecialchars($displayDate); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) {
                                        if (strpos($line, 'Address:') === false) {
                                            echo '<p>' . htmlspecialchars($line) . '</p>';
                                        }
                                    }
                                    ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['address_line1'] . ', ' . $job['address_line2'] . ', ' . $job['city'] . ', ' . $job['state'] . ' - ' . $job['pincode']); ?></p>
                                    <?php if (isset($job['distance'])) : ?>
                                        <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> <span class="item-status <?php echo htmlspecialchars($job['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($job['status'])); ?></span></p>
                                </div>
                                <div class="job-card-actions">
                                    <?php
                                    $bookingTimestamp = strtotime($job['booking_time']);
                                    $currentTimestamp = time();
                                    // MODIFIED: Allow starting 15 minutes (900 seconds) early
                                    if ($job['status'] === 'confirmed' && $currentTimestamp >= ($bookingTimestamp - 900)) :
                                    ?>
                                        <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'in_progress', null, this)" class="btn btn-main" style="background-color: #f59e0b; color: #fff;">Start Job</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="upcoming-jobs-empty" <?php if (count($upcomingJobs) > 0) echo 'style="display: none;"'; ?>>
                    <i class="fas fa-calendar-alt"></i>
                    <h3 id="upcoming-jobs-empty-title">No Upcoming Jobs</h3>
                    <p id="upcoming-jobs-empty-text">You have no confirmed jobs in your schedule.</p>
                </div>
            </div>

            <div id="current-jobs" class="tab-content">
                <div class="job-card-grid" id="current-jobs-grid">
                    <?php if (count($inProgressJobs) > 0) : ?>
                        <?php foreach ($inProgressJobs as $job) : ?>
                            <?php
                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $searchMonth = $bookingTime->format("F");
                            $displayDate = $bookingTime->format("D, M j, Y, g:i A");
                            ?>
                            <div class="job-card" data-search-terms="<?php echo strtolower(htmlspecialchars($searchMonth)); ?>">
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
                                        <p>Status: <span class="item-status in_progress">In Progress</span></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Appointment:</strong> <?php echo htmlspecialchars($displayDate); ?></p>
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) {
                                        if (strpos($line, 'Address:') === false) {
                                            echo '<p>' . htmlspecialchars($line) . '</p>';
                                        }
                                    }
                                    ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['address_line1'] . ', ' . $job['address_line2'] . ', ' . $job['city'] . ', ' . $job['state'] . ' - ' . $job['pincode']); ?></p>

                                </div>
                                <div class="job-card-actions">
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn accept">View Details & Complete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="current-jobs-empty" <?php if (count($inProgressJobs) > 0) echo 'style="display: none;"'; ?>>
                    <i class="fas fa-clock"></i>
                    <h3 id="current-jobs-empty-title">No Jobs In Progress</h3>
                    <p id="current-jobs-empty-text">You are not currently working on any job.</p>
                </div>
            </div>

            <div id="completed-jobs" class="tab-content">
                <div class="job-card-grid" id="completed-jobs-grid">
                    <?php if (count($completedJobs) > 0) : ?>
                        <?php foreach ($completedJobs as $job) : ?>
                            <?php
                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $searchMonth = $bookingTime->format("F");
                            $displayDate = $bookingTime->format("D, M j, Y");
                            ?>
                            <div class="job-card" data-search-terms="<?php echo strtolower(htmlspecialchars($searchMonth)); ?>">
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
                                        <p>Completed on: <?php echo htmlspecialchars($displayDate); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) {
                                        echo '<p>' . htmlspecialchars($line) . '</p>';
                                    }
                                    ?>
                                    <?php if (!empty($job['final_cost'])) : ?>
                                        <p><strong>Final Cost:</strong> ₹<?php echo htmlspecialchars(number_format($job['final_cost'], 2)); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> <span class="item-status completed">Completed</span></p>

                                    <?php if ($job['rating']) : ?>
                                        <div class="review-section">
                                            <strong>Customer Review:</strong>
                                            <div class="rating">
                                                <?php for ($i = 0; $i < 5; $i++) : ?>
                                                    <i class="fas fa-star" style="color: <?php echo $i < $job['rating'] ? '#ffc107' : '#e0e0e0'; ?>;"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if ($job['comment']) : ?>
                                                <p><em>"<?php echo htmlspecialchars($job['comment']); ?>"</em></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-actions">
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn-secondary">
                                        <i class="fas fa-info-circle"></i> See Details
                                    </a>
                                    <a href="/dailyfix/generate_invoice.php?id=<?php echo $job['id']; ?>" class="btn-main-custom" target="_blank">
                                        <i class="fas fa-download"></i> Download Invoice
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="completed-jobs-empty" <?php if (count($completedJobs) > 0) echo 'style="display: none;"'; ?>>
                    <i class="fas fa-check-circle"></i>
                    <h3 id="completed-jobs-empty-title">No Completed Jobs</h3>
                    <p id="completed-jobs-empty-text">You haven't completed any jobs yet.</p>
                </div>
            </div>

            <div id="cancelled-jobs" class="tab-content">
                <div class="job-card-grid" id="cancelled-jobs-grid">
                    <?php if (count($cancelledJobs) > 0) : ?>
                        <?php foreach ($cancelledJobs as $job) : ?>
                            <?php
                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                            $searchMonth = $bookingTime->format("F");
                            $displayDate = $bookingTime->format("D, M j, Y");
                            ?>
                            <div class="job-card" data-search-terms="<?php echo strtolower(htmlspecialchars($searchMonth)); ?>">
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
                                        <p>Booked for: <?php echo htmlspecialchars($displayDate); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong></p>
                                    <?php
                                    $details = explode("\n", $job['service_details']);
                                    foreach ($details as $line) {
                                        echo '<p>' . htmlspecialchars($line) . '</p>';
                                    }
                                    ?>
                                    <?php if (!empty($job['cancellation_reason'])) : ?>
                                        <p style="margin-top: 10px;"><strong>Reason for Cancellation:</strong> <br><span style="color: var(--danger-color); font-style: italic;"><?php echo htmlspecialchars($job['cancellation_reason']); ?></span></p>
                                    <?php endif; ?>
                                    <p style="margin-top: 10px;"><strong>Status:</strong> <span class="item-status cancelled">Cancelled</span></p>
                                </div>
                                <div class="job-card-actions">
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn-secondary">
                                        <i class="fas fa-info-circle"></i> See Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="empty-state" id="cancelled-jobs-empty" <?php if (count($cancelledJobs) > 0) echo 'style="display: none;"'; ?>>
                    <i class="fas fa-times-circle"></i>
                    <h3 id="cancelled-jobs-empty-title">No Cancelled Jobs</h3>
                    <p id="cancelled-jobs-empty-text">You do not have any cancelled or rejected jobs.</p>
                </div>
            </div>
            </div>
    </main>

    <script>
        // --- Tab Functionality ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                link.classList.add('active');
                const contentEl = document.getElementById(tabId);
                if (contentEl) contentEl.classList.add('active');
            });
        });

        // --- Job Action Handler ---
        function handleJobAction(bookingId, status, bookingTime, buttonElement) {
            // Find the closest parent container holding the buttons
            const actionContainer = buttonElement.closest('.job-card-actions');
            const buttonsInContainer = actionContainer ? actionContainer.querySelectorAll('.btn, button') : [buttonElement];
            // Use a Map to store original HTML keyed by the button element
            const originalTexts = new Map();

            // *** MODIFIED LOOP START ***
            buttonsInContainer.forEach(btn => {
                originalTexts.set(btn, btn.innerHTML); // Store original HTML
                btn.disabled = true; // Disable ALL buttons in the container

                // ONLY change the innerHTML of the button that was clicked
                if (btn === buttonElement) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                }
            });
            // *** MODIFIED LOOP END ***

            let url = `/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`;
            if (status === 'confirmed' && bookingTime) {
                url += `&booking_time=${encodeURIComponent(bookingTime)}`;
            }

            // Cancellation Reason Prompt for Decline
            let cancellationReason = null;
            if (status === 'cancelled') {
                cancellationReason = prompt("Please provide a mandatory reason for declining this job:");
                if (cancellationReason === null || cancellationReason.trim() === "") {
                    alert("Declination cancelled. A reason is required.");
                    // Restore buttons immediately using the Map
                    buttonsInContainer.forEach(btn => {
                        btn.disabled = false;
                        if (originalTexts.has(btn)) {
                            btn.innerHTML = originalTexts.get(btn); // Restore specific original HTML
                        }
                    });
                    return; // Stop the process
                }
                url += `&cancellation_reason=${encodeURIComponent(cancellationReason.trim())}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        // Attempt to parse JSON error first
                        return response.json().then(errData => {
                            throw new Error(errData.message || `HTTP error ${response.status}`);
                        }).catch(() => {
                            // Fallback if response wasn't JSON
                            throw new Error(`HTTP error ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Success: Remove the card visually and reload smoothly
                        const card = document.getElementById(`job-card-${bookingId}`);
                        if (card) {
                            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.95)';
                            setTimeout(() => {
                                window.location.reload(); // Reload after animation
                            }, 500); // Match animation duration
                        } else {
                            window.location.reload(); // Reload immediately if card not found
                        }
                    } else if (data.status === 'conflict') {
                        // Specific handling for conflict
                        alert(`Error: ${data.message || 'Could not accept job due to a time conflict.'}`);
                        // Restore buttons on conflict using the Map
                        buttonsInContainer.forEach(btn => {
                            btn.disabled = false;
                            if (originalTexts.has(btn)) {
                                btn.innerHTML = originalTexts.get(btn);
                            }
                        });
                    } else {
                        // General failure
                        throw new Error(data.message || 'Could not update status.');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert(`Error: ${error.message || 'A network error occurred. Please try again.'}`);
                    // Restore buttons on any failure using the Map
                    buttonsInContainer.forEach(btn => {
                        btn.disabled = false;
                        if (originalTexts.has(btn)) {
                            btn.innerHTML = originalTexts.get(btn); // Restore specific original HTML
                        }
                    });
                    // Optional: Reload even on error if state might be inconsistent
                    // window.location.reload();
                });
        }

        // --- Initialize Leaflet Maps for Pending Jobs ---
        // (Ensure this runs *after* the DOM is ready if not already deferred)
        <?php foreach ($pendingJobs as $job) : ?>
            <?php if ($job['customer_lat'] && $job['customer_lon'] && $worker_lat && $worker_lon) : ?>
                try {
                    var map_<?php echo $job['id']; ?> = L.map('map-<?php echo $job['id']; ?>').setView([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map_<?php echo $job['id']; ?>);
                    L.marker([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>]).addTo(map_<?php echo $job['id']; ?>).bindPopup("Customer's Location");
                    L.marker([<?php echo $worker_lat; ?>, <?php echo $worker_lon; ?>]).addTo(map_<?php echo $job['id']; ?>).bindPopup("Your Location");
                } catch (e) {
                    console.error("Error initializing map for job <?php echo $job['id']; ?>:", e);
                    // Optionally display a message in the map container
                    const mapDiv = document.getElementById('map-<?php echo $job['id']; ?>');
                    if (mapDiv) mapDiv.innerHTML = '<p style=\"padding:10px; text-align:center; color: red;\">Map could not be loaded.</p>';
                }
            <?php endif; ?>
        <?php endforeach; ?>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('job-search-input');

            // Check if the search input exists on the page
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();

                    // Find the currently active tab
                    const activeTab = document.querySelector('.tab-content.active');
                    if (!activeTab) return; // Exit if no active tab found

                    // Find the grid, cards, and empty message *within* that active tab
                    const gridInActiveTab = activeTab.querySelector('.job-card-grid');
                    const cardsInActiveTab = gridInActiveTab ? gridInActiveTab.querySelectorAll('.job-card') : [];
                    const emptyStateInActiveTab = activeTab.querySelector('.empty-state');

                    if (!emptyStateInActiveTab) return; // Exit if no empty state found

                    let visibleCards = 0;

                    cardsInActiveTab.forEach(card => {
                        // Get the visible text
                        const cardText = card.innerText.toLowerCase();
                        // Get the hidden search data (for full month names, etc.)
                        const searchData = (card.dataset.searchTerms || "").toLowerCase();

                        // Combine all searchable text
                        const fullSearchableText = cardText + ' ' + searchData;

                        if (fullSearchableText.includes(searchTerm)) {
                            card.style.display = 'block';
                            visibleCards++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Now, manage the empty state message for the active tab
                    const emptyTitle = emptyStateInActiveTab.querySelector('h3');
                    const emptyText = emptyStateInActiveTab.querySelector('p');
                    const hasOriginalJobs = cardsInActiveTab.length > 0;

                    if (visibleCards === 0) {
                        if (searchTerm === '' && !hasOriginalJobs) {
                            // Search is empty, and there were no jobs in this tab to begin with
                            // Reset to original message (IDs are just for targeting)
                            const originalTitle = document.getElementById(activeTab.id + '-empty-title').textContent;
                            const originalText = document.getElementById(activeTab.id + '-empty-text').innerHTML;
                            if (emptyTitle) emptyTitle.textContent = originalTitle;
                            if (emptyText) emptyText.innerHTML = originalText;

                            emptyStateInActiveTab.style.display = 'block';
                            if (gridInActiveTab) gridInActiveTab.style.display = 'grid';
                        } else {
                            // Search has text, but no results found
                            if (emptyTitle) emptyTitle.textContent = 'No Jobs Found';
                            if (emptyText) emptyText.innerHTML = 'Your search for "' + this.value + '" did not match any jobs in this tab.';

                            emptyStateInActiveTab.style.display = 'block';
                            if (gridInActiveTab) gridInActiveTab.style.display = 'none'; // Hide grid container
                        }
                    } else {
                        // We have visible cards
                        emptyStateInActiveTab.style.display = 'none';
                        if (gridInActiveTab) gridInActiveTab.style.display = 'grid'; // Ensure grid is visible
                    }
                });
            }
        });
    </script>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>

</html>