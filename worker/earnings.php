<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security: Ensure the user is a worker
if ($role !== 'worker') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

// Fetch earnings data
$totalEarnings = 0;
$monthEarnings = 0;
$completedJobs = [];
try {
    // Total earnings
    $stmt = $conn->prepare("SELECT SUM(final_cost) FROM public.bookings WHERE worker_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalEarnings = $stmt->fetchColumn() ?: 0;

    // This month's earnings
    $stmt = $conn->prepare("SELECT SUM(final_cost) FROM public.bookings WHERE worker_id = ? AND status = 'completed' AND booking_time >= date_trunc('month', current_date)");
    $stmt->execute([$userId]);
    $monthEarnings = $stmt->fetchColumn() ?: 0;
    
    // List of completed jobs
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name as customer_name
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'completed'
        ORDER BY b.booking_time DESC
    ");
    $stmt->execute([$userId]);
    $completedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Worker earnings fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Earnings - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">My Earnings</h1>
            <div class="summary-grid">
                <div class="summary-card">
                    <h4>Total Earnings (All Time)</h4>
                    <p>₹<?php echo number_format($totalEarnings, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h4>This Month's Earnings</h4>
                    <p>₹<?php echo number_format($monthEarnings, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h4>Completed Jobs</h4>
                    <p><?php echo count($completedJobs); ?></p>
                </div>
            </div>
            
            <h2 class="item-list-header">Payout History</h2>
            <div class="item-list">
                 <?php if (count($completedJobs) > 0): ?>
                    <?php foreach ($completedJobs as $job): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <p><strong>Job with <?php echo htmlspecialchars($job['customer_name']); ?></strong></p>
                                <small>Completed on <?php echo date("M d, Y", strtotime($job['booking_time'])); ?></small>
                            </div>
                            <div class="item-value">
                                +$<?php echo number_format($job['final_cost'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                     <div class="empty-state">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>No Completed Jobs Yet</h3>
                        <p>Your earnings from completed jobs will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>