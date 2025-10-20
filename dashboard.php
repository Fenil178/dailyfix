<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

// Initialize variables
$stats = [
    'primary' => 0, 'secondary' => 0, 'tertiary' => 0, 'quaternary' => 0,
];
$recentActivities = [];
$todaysAgenda = [];

try {
    if ($role === 'customer') {
        // Card 1: Upcoming Bookings
        $stmt = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE customer_id = ? AND status IN ('pending', 'confirmed', 'in_progress')");
        $stmt->execute([$userId]);
        $stats['primary'] = $stmt->fetchColumn();

        // Card 2: Completed Services
        $stmt = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE customer_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $stats['secondary'] = $stmt->fetchColumn();
        
        // Card 3: Pending Reviews
        $stmt = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE customer_id = ? AND status = 'completed' AND id NOT IN (SELECT booking_id FROM public.reviews WHERE reviewer_id = ?)");
        $stmt->execute([$userId, $userId]);
        $stats['tertiary'] = $stmt->fetchColumn();

        // Card 4: Unique Workers Hired
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT worker_id) FROM public.bookings WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $stats['quaternary'] = $stmt->fetchColumn();

        // Fetch Recent Activities
        $stmt_activity = $conn->prepare("
            SELECT b.id, b.service_details, b.status, b.booking_time, u.full_name as worker_name
            FROM public.bookings b JOIN public.users u ON b.worker_id = u.id
            WHERE b.customer_id = ? ORDER BY b.created_at DESC LIMIT 5
        ");
        $stmt_activity->execute([$userId]);
        $recentActivities = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($role === 'worker') {
        // Card 1: New Job Requests
        $stmt = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE worker_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        $stats['primary'] = $stmt->fetchColumn();

        // Card 2: Earnings This Month
        $stmt = $conn->prepare("SELECT COALESCE(SUM(final_cost), 0) FROM public.bookings WHERE worker_id = ? AND status = 'completed' AND booking_time >= date_trunc('month', current_date)");
        $stmt->execute([$userId]);
        $stats['secondary'] = $stmt->fetchColumn();
        
        // Card 3: Average Rating
        $stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) FROM public.reviews WHERE worker_id = ?");
        $stmt->execute([$userId]);
        $stats['tertiary'] = round($stmt->fetchColumn(), 1);

        // Card 4: Total Completed Jobs
        $stmt = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE worker_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        $stats['quaternary'] = $stmt->fetchColumn();

        // Fetch Today's Agenda
        $stmt_agenda = $conn->prepare("
            SELECT b.id, b.booking_time, u.full_name as customer_name FROM public.bookings b
            JOIN public.users u ON b.customer_id = u.id
            WHERE b.worker_id = ? AND b.status = 'confirmed' AND b.booking_time::date = current_date
            ORDER BY b.booking_time ASC
        ");
        $stmt_agenda->execute([$userId]);
        $todaysAgenda = $stmt_agenda->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Recent Activities
        $stmt_activity = $conn->prepare("
            SELECT b.id, b.service_details, b.status, b.booking_time, u.full_name as customer_name
            FROM public.bookings b JOIN public.users u ON b.customer_id = u.id
            WHERE b.worker_id = ? ORDER BY b.created_at DESC LIMIT 3
        ");
        $stmt_activity->execute([$userId]);
        $recentActivities = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<body>
    <main class="dashboard-container-v4">
        <div class="welcome-banner">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>!</h1>
                    <p>Here's what's happening with your account today.</p>
                </div>
                <?php if ($role === 'customer') : ?>
                    <a href="/dailyfix/customer/services.php" class="btn-primary-v4">
                        <i class="fas fa-plus-circle"></i>
                        <span>Book New Service</span>
                    </a>
                <?php elseif ($role === 'worker') : ?>
                    <a href="/dailyfix/worker/jobs.php" class="btn-primary-v4">
                        <i class="fas fa-briefcase"></i>
                        <span>View All Jobs</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats-grid-v4">
            <?php if ($role === 'customer'): ?>
                <div class="stat-card-v4 stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Upcoming Bookings</span>
                        <span class="stat-value"><?php echo $stats['primary']; ?></span>
                        <span class="stat-desc">Services scheduled</span>
                    </div>
                </div>
                <div class="stat-card-v4 stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Completed Services</span>
                        <span class="stat-value"><?php echo $stats['secondary']; ?></span>
                        <span class="stat-desc">Jobs finished successfully</span>
                    </div>
                </div>
                <div class="stat-card-v4 stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Workers Hired</span>
                        <span class="stat-value"><?php echo $stats['quaternary']; ?></span>
                        <span class="stat-desc">Professionals booked</span>
                    </div>
                </div>
            <?php elseif ($role === 'worker'): ?>
                <div class="stat-card-v4 stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">New Job Requests</span>
                        <span class="stat-value"><?php echo $stats['primary']; ?></span>
                        <span class="stat-desc">Awaiting your response</span>
                    </div>
                </div>
                <div class="stat-card-v4 stat-success">
                    <div class="stat-icon">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Monthly Earnings</span>
                        <span class="stat-value">₹<?php echo number_format($stats['secondary'], 0); ?></span>
                        <span class="stat-desc">This month's revenue</span>
                    </div>
                </div>
                <div class="stat-card-v4 stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Average Rating</span>
                        <span class="stat-value"><?php echo $stats['tertiary']; ?> <small style="font-size: 0.5em;">/ 5</small></span>
                        <span class="stat-desc">Customer satisfaction</span>
                    </div>
                </div>
                <div class="stat-card-v4 stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Jobs Done</span>
                        <span class="stat-value"><?php echo $stats['quaternary']; ?></span>
                        <span class="stat-desc">All-time completions</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="<?php echo ($role === 'worker') ? 'dashboard-columns-v4' : ''; ?>">
            <div class="main-column-v4">
                <div class="section-card-v4">
                    <div class="section-header-v4">
                        <h2><i class="fas fa-history"></i> Recent Activity</h2>
                        <a href="/dailyfix/api/all_activity.php" class="view-all-link-v4">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="activity-list-v4">
                        <?php if (empty($recentActivities)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent activity to display</p>
                                <small>Your activities will appear here</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item-v4">
                                    <div class="activity-icon-v4">
                                        <i class="fas fa-bolt"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-title">
                                            <?php
                                            if ($role === 'customer') {
                                                $serviceName = 'Service'; // Default value
                                                $itemName = ''; // Default value
                                                if (!empty($activity['service_details'])) {
                                                    if (preg_match('/Service: (.*)/', $activity['service_details'], $service_matches)) {
                                                        $serviceName = trim($service_matches[1]);
                                                    }
                                                    if (preg_match('/Item: (.*)/', $activity['service_details'], $item_matches)) {
                                                        $itemName = trim($item_matches[1]);
                                                    }
                                                }
                                                echo "<strong>Booking Request</strong> for <strong>" . htmlspecialchars($serviceName) . ($itemName ? ' - ' . htmlspecialchars($itemName) : '') . "</strong>";
                                            } else {
                                                echo "<strong>Booking Request</strong> from " . htmlspecialchars($activity['customer_name']);
                                            }
                                            ?>
                                        </p>
                                        <span class="activity-status status-<?php echo strtolower($activity['status']); ?>">
                                            <i class="fas fa-circle"></i> <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </div>
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $activity['id']; ?>" class="btn-view-v4">
                                        <span>View</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($role === 'worker'): ?>
            <div class="sidebar-column-v4">
                <div class="section-card-v4">
                    <div class="section-header-v4">
                        <h2><i class="fas fa-calendar-day"></i> Today's Agenda</h2>
                    </div>
                    <div class="agenda-list-v4">
                        <?php if (empty($todaysAgenda)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No jobs scheduled today</p>
                                <small>Enjoy your free time!</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($todaysAgenda as $job): ?>
                                <div class="agenda-item-v4">
                                    <div class="agenda-time">
                                        <i class="fas fa-clock"></i>
                                        <span><?php 
                                            $bookingTime = new DateTime($job['booking_time'], new DateTimeZone('UTC'));
                                            $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                            echo $bookingTime->format("g:i A"); 
                                        ?></span>
                                    </div>
                                    <div class="agenda-content">
                                        <p>with <strong><?php echo htmlspecialchars($job['customer_name']); ?></strong></p>
                                    </div>
                                    <a href="/dailyfix/booking-details.php?id=<?php echo $job['id']; ?>" class="btn-view-v4">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>