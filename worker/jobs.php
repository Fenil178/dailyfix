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
function format_time_ago($timestamp_str) {
    $time = strtotime($timestamp_str);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 86400) . ' days ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

// Fetch pending and upcoming jobs for the worker
$pendingJobs = [];
$upcomingJobs = [];
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

    // Fetch new job requests (status = 'pending')
    $sql_pending = "
        SELECT b.id, b.service_details, b.booking_time, b.created_at, u.full_name as customer_name, u.profile_image as customer_avatar, u.latitude as customer_lat, u.longitude as customer_lon, u.address_line1, u.address_line2, u.city, u.state, u.pincode
        ";
    if ($worker_lat && $worker_lon) {
        $sql_pending .= ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance";
    }
    $sql_pending .= "
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'pending'
        ORDER BY b.created_at DESC
    ";
    
    $params_pending = [$userId];
    if ($worker_lat && $worker_lon) {
        array_unshift($params_pending, $worker_lat, $worker_lon, $worker_lat);
    }
    
    $stmt = $conn->prepare($sql_pending);
    $stmt->execute($params_pending);
    $pendingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch upcoming jobs (status = 'confirmed' or 'in_progress')
    $sql_upcoming = "
        SELECT b.*, u.full_name as customer_name, u.profile_image as customer_avatar, u.latitude as customer_lat, u.longitude as customer_lon, u.address_line1, u.address_line2, u.city, u.state, u.pincode
        ";
    if ($worker_lat && $worker_lon) {
        $sql_upcoming .= ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance";
    }
    $sql_upcoming .= "
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status IN ('confirmed', 'in_progress')
        ORDER BY b.booking_time ASC
    ";
    
    $params_upcoming = [$userId];
    if ($worker_lat && $worker_lon) {
        array_unshift($params_upcoming, $worker_lat, $worker_lon, $worker_lat);
    }
    
    $stmt = $conn->prepare($sql_upcoming);
    $stmt->execute($params_upcoming);
    $upcomingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Worker jobs fetch error: " . $e->getMessage());
    $pendingJobs = [];
    $upcomingJobs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Job Requests - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        .job-card-body .map-container {
            height: 200px;
            width: 100%;
            border-radius: 8px;
            margin-top: 1rem;
            z-index: 1; /* Ensure map markers are clickable */
        }
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
            </div>

            <div id="new-requests" class="tab-content active">
                <?php if (count($pendingJobs) > 0): ?>
                    <div class="job-card-grid">
                        <?php foreach ($pendingJobs as $job): ?>
                            <div class="job-card" id="job-card-<?php echo $job['id']; ?>">
                                <div class="job-card-header">
                                    <img src="<?php echo htmlspecialchars($job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Requested: <?php echo format_time_ago($job['created_at']); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Appointment:</strong> <?php 
                                        $bookingTime = new DateTime($job['booking_time']);
                                        $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                        echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A")); 
                                    ?></p>
                                    <p><strong>Details:</strong></p>
                                    <?php 
                                        // FIXED: Extract and display only the service and item details
                                        $details = explode("\n", $job['service_details']);
                                        foreach ($details as $line) {
                                            if (strpos($line, 'Address:') === false) {
                                                echo '<p>' . htmlspecialchars($line) . '</p>';
                                            }
                                        }
                                    ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['address_line1'] . ', ' . $job['address_line2'] . ', ' . $job['city'] . ', ' . $job['state'] . ' - ' . $job['pincode']); ?></p>
                                    <?php if (isset($job['distance'])): ?>
                                        <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['customer_lat'] && $job['customer_lon'] && $worker_lat && $worker_lon): ?>
                                    <div id="map-<?php echo $job['id']; ?>" class="map-container"></div>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var map = L.map('map-<?php echo $job['id']; ?>').setView([<?php echo $job['customer_lat']; ?>, <?php echo $job['customer_lon']; ?>], 13);
                                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
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
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No New Job Requests</h3>
                        <p>You don't have any pending job requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="upcoming-jobs" class="tab-content">
                 <?php if (count($upcomingJobs) > 0): ?>
                    <div class="job-card-grid">
                        <?php foreach ($upcomingJobs as $job): ?>
                             <div class="job-card">
                                <div class="job-card-header">
                                    <img src="<?php echo htmlspecialchars($job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Scheduled for: <?php 
                                            $bookingTime = new DateTime($job['booking_time']);
                                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                            echo htmlspecialchars($bookingTime->format("D, M j, Y, g:i A")); 
                                        ?></p>
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
                                     <?php if (isset($job['distance'])): ?>
                                        <p><strong>Distance:</strong> <?php echo round($job['distance'], 2); ?> km away</p>
                                    <?php endif; ?>
                                    <p><strong>Status:</strong> <span class="item-status <?php echo htmlspecialchars($job['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($job['status'])); ?></span></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Upcoming Jobs</h3>
                        <p>You have no confirmed jobs in your schedule.</p>
                    </div>
                <?php endif; ?>
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
                document.getElementById(tabId).classList.add('active');
            });
        });

        // --- NEW: Job Action Handler ---
        function handleJobAction(bookingId, status, bookingTime, buttonElement) {
            // Disable both buttons to prevent double-clicking
            buttonElement.parentElement.querySelectorAll('.btn').forEach(btn => {
                btn.disabled = true;
                btn.textContent = '...';
            });
            
            // Call the API using Fetch
            let url = `/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`;
            if (bookingTime) {
                url += `&booking_time=${encodeURIComponent(bookingTime)}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server responded with an error.');
                    }
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            throw new Error('Did not receive a JSON response. Received: ' + text);
                        });
                    }
                })
                .then(data => {
                    if (data.status === 'success') {
                        // On success, fade out the card and then reload the page
                        const card = document.getElementById(`job-card-${bookingId}`);
                        if(card) {
                            card.style.transition = 'opacity 0.5s ease';
                            card.style.opacity = '0';
                        }
                        setTimeout(() => {
                            window.location.reload(); // Reload to update both tabs
                        }, 500);
                    } else {
                        // On a valid JSON response with an error status
                        alert(`Error: ${data.message}`);
                        buttonElement.parentElement.querySelector('.btn.accept').disabled = false;
                        buttonElement.parentElement.querySelector('.btn.accept').textContent = 'Accept';
                        buttonElement.parentElement.querySelector('.btn.decline').disabled = false;
                        buttonElement.parentElement.querySelector('.btn.decline').textContent = 'Decline';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('A network error occurred. Please try again.');
                    // Re-enable buttons
                     buttonElement.parentElement.querySelector('.btn.accept').disabled = false;
                     buttonElement.parentElement.querySelector('.btn.accept').textContent = 'Accept';
                     buttonElement.parentElement.querySelector('.btn.decline').disabled = false;
                     buttonElement.parentElement.querySelector('.btn.decline').textContent = 'Decline';
                });
        }
    </script>
    
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>