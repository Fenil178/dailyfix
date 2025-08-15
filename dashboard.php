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

try {
    // Fetch stat card data based on the user's role
    if ($role === 'customer') {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE customer_id = ?');
        $stmt->execute([$userId]);
        $totalBookings = $stmt->fetchColumn();

        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE customer_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();

    } elseif ($role === 'worker') {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE worker_id = ? AND status = \'pending\'');
        $stmt->execute([$userId]);
        $pendingJobs = $stmt->fetchColumn();
        
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE worker_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();
    }

    // --- MODIFIED: Fetch dynamic data for the "Recent Activity" section ---
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
            <div class="dashboard-header">
                <h2>Recent Activity</h2>
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

    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>