<?php
include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$bookingId = $_GET['id'];
$booking = null;

try {
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            c.full_name AS customer_name,
            c.profile_image AS customer_avatar,
            w.full_name AS worker_name,
            w.profile_image AS worker_avatar
        FROM 
            public.bookings b
        JOIN 
            public.users c ON b.customer_id = c.id
        JOIN 
            public.users w ON b.worker_id = w.id
        WHERE 
            b.id = ? AND (b.customer_id = ? OR b.worker_id = ?)
    ");
    $stmt->execute([$bookingId, $userId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking Details Error: " . $e->getMessage());
}

if (!$booking) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking #<?php echo htmlspecialchars($booking['id']); ?> - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/booking-details.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <main class="page-content">
        <div class="management-container">
            <a href="/dailyfix/api/all_activity.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to All Activity</a>
            
            <div class="details-hero">
                <div class="details-hero-header">
                    <h1>Booking #<?php echo htmlspecialchars($booking['id']); ?></h1>
                    <span class="item-status <?php echo htmlspecialchars($booking['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($booking['status'])); ?></span>
                </div>
            </div>

            <div class="details-content-grid">
                <div class="timeline-column">
                    <ul class="booking-timeline">
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="timeline-content">
                                <div class="label">Scheduled Time</div>
                                <div class="value"><?php echo date("D, M d, Y - g:i A", strtotime($booking['booking_time'])); ?></div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-tools"></i></div>
                            <div class="timeline-content">
                                <div class="label">Service Details</div>
                                <div class="value service-details-box"><?php echo nl2br(htmlspecialchars($booking['service_details'])); ?></div>
                            </div>
                        </li>
                        <?php if ($booking['final_cost']): ?>
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-rupee-sign"></i></div>
                            <div class="timeline-content">
                                <div class="label">Final Cost</div>
                                <div class="value">₹<?php echo number_format($booking['final_cost'], 2); ?></div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="sidebar-column">
                    <div class="participant-card">
                        <h3>Participants</h3>
                        <div class="participant-profile">
                            <img src="<?php echo htmlspecialchars($booking['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Customer">
                            <div>
                                <div class="role">Customer</div>
                                <div class="name"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                            </div>
                        </div>
                        <div class="participant-profile">
                            <img src="<?php echo htmlspecialchars($booking['worker_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Worker">
                            <div>
                                <div class="role">Worker</div>
                                <div class="name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>