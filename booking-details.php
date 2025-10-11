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
    <title>Booking #<?php echo htmlspecialchars($booking['id']); ?> - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/booking-details.css" />
</head>
<body>
    <?php include_once __DIR__ . "/api/header.php"; ?>

    <main class="page-content">
        <div class="management-container">
            <a href="/dailyfix/customer/bookings.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
            
            <div class="details-hero">
                <div class="details-hero-header">
                    <h1>Booking #<?php echo htmlspecialchars($booking['id']); ?></h1>
                    <div class="item-status <?php echo htmlspecialchars($booking['status']); ?>">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $booking['status'])); ?>
                    </div>
                </div>
            </div>

            <div class="details-content-grid">
                <div class="timeline-column">
                    <ul class="booking-timeline">
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="timeline-content">
                                <div class="label">Scheduled For</div>
                                <div class="value"><?php
                                    $bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
                                    $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                    echo $bookingTime->format('D, M j, Y, g:i A');
                                ?></div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-tools"></i></div>
                            <div class="timeline-content">
                                <div class="label">Service Details</div>
                                <div class="service-details-box"><?php echo htmlspecialchars($booking['service_details']); ?></div>
                            </div>
                        </li>
                        <li class="timeline-item">
                            <div class="timeline-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="timeline-content">
                                <div class="label">Payment Status</div>
                                <div class="value"><?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?></div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-column">
                    <div class="participant-card">
                        <h3>Participants</h3>
                        <div class="participant-profile">
                            <img src="<?php echo htmlspecialchars(strpos($booking['customer_avatar'], '/') === 0 ? $booking['customer_avatar'] : '/dailyfix/' . $booking['customer_avatar']); ?>" alt="Customer">
                            <div>
                                <div class="role">Customer</div>
                                <div class="name"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                            </div>
                        </div>
                        <div class="participant-profile">
                            <img src="<?php echo htmlspecialchars(strpos($booking['worker_avatar'], '/') === 0 ? $booking['worker_avatar'] : '/dailyfix/' . $booking['worker_avatar']); ?>" alt="Worker">
                            <div>
                                <div class="role">Worker</div>
                                <div class="name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($role === 'worker' && $booking['status'] === 'in_progress' && !$booking['work_completed_by_worker']): ?>
                <div class="action-panel" style="text-align: center; margin-top: 2rem;">
                    <button id="mark-complete-btn" class="btn-main" data-booking-id="<?php echo $booking['id']; ?>">Mark Job as Complete</button>
                </div>
            <?php endif; ?>

            <?php if ($role === 'customer' && $booking['work_completed_by_worker'] && $booking['payment_status'] === 'unpaid'): ?>
                <div class="action-panel" style="text-align: center; margin-top: 2rem;">
                    <h2>Payment Required</h2>
                    <p>The worker has marked this job as complete. Please proceed with the payment of <strong>$<?php echo number_format($booking['final_cost'], 2); ?></strong>.</p>
                    <button id="pay-now-btn" class="btn-main" data-booking-id="<?php echo $booking['id']; ?>">Pay Now</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // For Worker: Mark job as complete
        const markCompleteBtn = document.getElementById('mark-complete-btn');
        if (markCompleteBtn) {
            markCompleteBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to mark this job as complete? The customer will be prompted for payment.')) return;

                const bookingId = this.dataset.bookingId;
                const formData = new FormData();
                formData.append('booking_id', bookingId);

                fetch('/dailyfix/api/mark-work-done.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Job marked as complete!');
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            });
        }

        // For Customer: Process static payment
        const payNowBtn = document.getElementById('pay-now-btn');
        if (payNowBtn) {
            payNowBtn.addEventListener('click', function() {
                this.disabled = true;
                this.textContent = 'Processing...';

                const bookingId = this.dataset.bookingId;
                const formData = new FormData();
                formData.append('booking_id', bookingId);

                fetch('/dailyfix/api/process-static-payment.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Payment successful!');
                            window.location.reload();
                        } else {
                            alert('Payment failed: ' + data.message);
                            this.disabled = false;
                            this.textContent = 'Pay Now';
                        }
                    });
            });
        }
    });
    </script>

    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>