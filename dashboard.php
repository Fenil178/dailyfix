<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

// All session-related variables ($role, $userId, $userName) are now available from header.php

// Initialize variables for dashboard data
$totalBookings = 0;
$pendingJobs = 0;
$completedJobs = 0;
$recentActivities = [];
$userAddress1 = null;
$userAddress2 = null;

try {
    // Fetch user's location for dashboard display
    $locStmt = $conn->prepare("SELECT address_line1, address_line2 FROM public.users WHERE id = ?");
    $locStmt->execute([$userId]);
    $location = $locStmt->fetch(PDO::FETCH_ASSOC);
    if ($location) {
        $userAddress1 = $location['address_line1'];
        $userAddress2 = $location['address_line2'];
    }

    // Fetch stat card data based on the user's role
    if ($role === 'customer') {
        // Corrected schema from 'dailyfix.bookings' to 'public.bookings'
        $stmt = $conn->prepare('SELECT COUNT(*) FROM public.bookings WHERE customer_id = ?');
        $stmt->execute([$userId]);
        $totalBookings = $stmt->fetchColumn();

        // Corrected schema from 'dailyfix.bookings' to 'public.bookings'
        $stmt = $conn->prepare('SELECT COUNT(*) FROM public.bookings WHERE customer_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();

    } elseif ($role === 'worker') {
        // Corrected schema from 'dailyfix.bookings' to 'public.bookings'
        $stmt = $conn->prepare('SELECT COUNT(*) FROM public.bookings WHERE worker_id = ? AND status = \'pending\'');
        $stmt->execute([$userId]);
        $pendingJobs = $stmt->fetchColumn();
        
        // Corrected schema from 'dailyfix.bookings' to 'public.bookings'
        $stmt = $conn->prepare('SELECT COUNT(*) FROM public.bookings WHERE worker_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();
    }

    // --- Fetch dynamic data for the "Recent Activity" section ---
    if ($role === 'customer') {
        $stmt = $conn->prepare("
            SELECT b.id, b.service_details, b.status, b.booking_time, u.full_name as worker_name
            FROM public.bookings b
            JOIN public.users u ON b.worker_id = u.id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($role === 'worker') {
        $stmt = $conn->prepare("
            SELECT b.id, b.service_details, b.status, b.booking_time, u.full_name as customer_name
            FROM public.bookings b
            JOIN public.users u ON b.customer_id = u.id
            WHERE b.worker_id = ?
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/dashboard_location.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <?php include_once __DIR__ . "/api/header.php"; ?>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <?php if (isset($_GET['action']) && $_GET['action'] === 'new_user') : ?>
                <h1>Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
            <?php else : ?>
                <h1>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
            <?php endif; ?>

            <div class="dashboard-location-wrapper">
                <div class="dashboard-location" id="dashboard-location-toggle">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <?php if ($userAddress1): ?>
                            <span class="address-line-1"><?php echo htmlspecialchars($userAddress1); ?></span>
                            <span class="address-line-2"><?php echo htmlspecialchars($userAddress2); ?></span>
                        <?php else: ?>
                            <a href="/dailyfix/profile.php#location" style="text-decoration:none; color:inherit;">
                                <span class="address-line-1">Set Your Location</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>

                <div class="location-dropdown" id="location-dropdown">
                    <a href="/dailyfix/profile.php#location">
                        <i class="fas fa-edit"></i>
                        <span>Update Location</span>
                    </a>
                </div>
            </div>

            <p>Here is your <?php echo htmlspecialchars(ucfirst($role)); ?> dashboard overview.</p>
        </div>

        <section class="dashboard-grid">
            <?php if ($role === 'customer') : ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <i class="fas fa-file-invoice stat-card-icon"></i>
                        <h3 class="stat-card-title">Total Bookings</h3>
                    </div>
                    <p class="stat-card-value"><?php echo $totalBookings; ?></p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <i class="fas fa-check-circle stat-card-icon success"></i>
                        <h3 class="stat-card-title">Completed Jobs</h3>
                    </div>
                    <p class="stat-card-value success"><?php echo $completedJobs; ?></p>
                </div>
                <div class="stat-card action-card">
                    <i class="fas fa-search stat-card-icon"></i>
                    <h3 class="stat-card-title">Find a Worker</h3>
                    <a href="/dailyfix/customer/services.php" class="stat-card-cta">Browse Services</a>
                </div>

            <?php elseif ($role === 'worker') : ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <i class="fas fa-hourglass-start stat-card-icon warning"></i>
                        <h3 class="stat-card-title">Pending Requests</h3>
                    </div>
                    <p class="stat-card-value warning"><?php echo $pendingJobs; ?></p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <i class="fas fa-check-double stat-card-icon success"></i>
                        <h3 class="stat-card-title">Completed Jobs</h3>
                    </div>
                    <p class="stat-card-value success"><?php echo $completedJobs; ?></p>
                </div>
                <div class="stat-card action-card">
                    <i class="fas fa-user-cog stat-card-icon"></i>
                    <h3 class="stat-card-title">Manage Profile</h3>
                    <a href="/dailyfix/profile.php" class="stat-card-cta">Update Profile</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section">
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Recent Activity</h2>
                <a href="/dailyfix/api/all_activity.php" class="view-all-link">View All</a>
            </div>
            <div class="activity-list">
                <?php if (count($recentActivities) > 0): ?>
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><i class="fas fa-history"></i></div>
                            <div class="activity-details">
                                <p>
                                    <strong>Booking Request</strong> 
                                    <?php if ($role === 'customer'): ?>
                                        with <?php echo htmlspecialchars($activity['worker_name']); ?>
                                    <?php else: ?>
                                        from <?php echo htmlspecialchars($activity['customer_name']); ?>
                                    <?php endif; ?>
                                </p>
                                <small>
                                    Status: <span class="status-<?php echo htmlspecialchars(strtolower($activity['status'])); ?>"><?php echo ucfirst(htmlspecialchars($activity['status'])); ?></span>
                                    - <?php echo date("M d, Y", strtotime($activity['booking_time'])); ?>
                                </small>
                            </div>
                            <a href="/dailyfix/booking-details.php?id=<?php echo $activity['id']; ?>" class="btn-view">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item-empty" style="padding: 1.5rem; text-align: center; color: var(--text-color-light);">
                        <p>No recent activity to display.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const locationToggle = document.getElementById('dashboard-location-toggle');
        const locationDropdown = document.getElementById('location-dropdown');

        if (locationToggle && locationDropdown) {
            // Event listener to toggle the dropdown when the location is clicked
            locationToggle.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevents the document click from firing immediately
                locationDropdown.classList.toggle('active');
                locationToggle.querySelector('.dropdown-arrow').style.transform = 
                    locationDropdown.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
            });

            // Event listener to close the dropdown if clicking anywhere else on the page
            document.addEventListener('click', (event) => {
                if (!locationToggle.contains(event.target) && !locationDropdown.contains(event.target)) {
                    locationDropdown.classList.remove('active');
                    locationToggle.querySelector('.dropdown-arrow').style.transform = 'rotate(0deg)';
                }
            });
        }
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.profile-tabs .tab-link');
        const tabPanes = document.querySelectorAll('.tab-pane');

        // Function to activate a specific tab
        function activateTab(tabId) {
            // Deactivate all tabs and panes first
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            tabPanes.forEach(pane => {
                pane.classList.remove('active');
            });

            // Activate the correct tab and pane
            const tabToActivate = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
            const paneToActivate = document.getElementById(tabId);

            if (tabToActivate && paneToActivate) {
                tabToActivate.classList.add('active');
                paneToActivate.classList.add('active');
            }
        }

        // 1. Handle clicking on tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                event.preventDefault();
                const tabId = this.getAttribute('data-tab');
                activateTab(tabId);
                // Update URL hash without reloading the page
                window.history.pushState(null, null, `#${tabId}`);
            });
        });

        // 2. Check URL hash on page load and activate the correct tab
        const currentHash = window.location.hash.substring(1); // Get hash without the '#'
        if (currentHash) {
            activateTab(currentHash);
        } else {
            // Activate the default tab if no hash is present
            activateTab('my-details');
        }
    });
    </script>

    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>