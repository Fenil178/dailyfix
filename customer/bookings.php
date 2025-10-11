<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security: Ensure the user is a customer
if ($role !== 'customer') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

// Fetch all bookings for the customer
$bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name as worker_name
        FROM public.bookings b
        JOIN public.users u ON b.worker_id = u.id
        WHERE b.customer_id = ?
        ORDER BY b.booking_time DESC
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Customer bookings fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Bookings - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">My Bookings</h1>
            <div class="item-list">
                 <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <?php
                                $serviceName = 'Service'; // Default value
                                $itemName = ''; // Default value
                                if (!empty($booking['service_details'])) {
                                    if (preg_match('/Service: (.*)/', $booking['service_details'], $service_matches)) {
                                        $serviceName = trim($service_matches[1]);
                                    }
                                    if (preg_match('/Item: (.*)/', $booking['service_details'], $item_matches)) {
                                        $itemName = trim($item_matches[1]);
                                    }
                                }
                                ?>
                                <p><strong>Booking for <?php echo htmlspecialchars($serviceName); ?><?php if ($itemName) echo " - " . htmlspecialchars($itemName); ?></strong></p>
                                <small>Scheduled for <?php 
                                    $bookingTime = new DateTime($booking['booking_time']);
                                    $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                    echo htmlspecialchars($bookingTime->format("D, M d, Y g:i A")); 
                                ?></small>
                            </div>
                            <div class="item-status <?php echo htmlspecialchars($booking['status']); ?>">
                                <?php echo str_replace('_', ' ', htmlspecialchars($booking['status'])); ?>
                            </div>
                            <div class="item-value">
                                <i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($booking['final_cost'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                     <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <h3>You Haven't Booked Any Services</h3>
                        <p>Your past and upcoming bookings will appear here.</p>
                        <a href="/dailyfix/customer/services.php" style="margin-top: 1rem; display:inline-block;" class="btn-main">Browse Services</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>