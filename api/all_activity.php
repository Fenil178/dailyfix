<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php";

$allActivities = [];

try {
    if ($role === 'customer') {
        $stmt = $conn->prepare("
            SELECT b.id, b.status, b.booking_time, u.full_name as worker_name
            FROM public.bookings b
            JOIN public.users u ON b.worker_id = u.id
            WHERE b.customer_id = ? ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
        $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($role === 'worker') {
        $stmt = $conn->prepare("
            SELECT b.id, b.status, b.booking_time, u.full_name as customer_name
            FROM public.bookings b
            JOIN public.users u ON b.customer_id = u.id
            WHERE b.worker_id = ? ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
        $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("All activity fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>All Activity - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/all_activity.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <main class="page-content">
        <div class="management-container">
            <a href="/dailyfix/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1 class="page-title">All Activity</h1>
            
            <div class="activity-card-list">
                <?php if (count($allActivities) > 0): ?>
                    <?php foreach ($allActivities as $activity): ?>
                        <div class="activity-card">
                            <div class="activity-card-details">
                                <p>
                                    Booking Request 
                                    <?php if ($role === 'customer'): ?>
                                        with <strong><?php echo htmlspecialchars($activity['worker_name']); ?></strong>
                                    <?php else: ?>
                                        from <strong><?php echo htmlspecialchars($activity['customer_name']); ?></strong>
                                    <?php endif; ?>
                                </p>
                                <small>
                                    Scheduled for <?php echo date("M d, Y", strtotime($activity['booking_time'])); ?>
                                </small>
                            </div>
                            <div class="item-status <?php echo htmlspecialchars($activity['status']); ?>" style="margin: 0 1rem;">
                                <?php echo str_replace('_', ' ', htmlspecialchars($activity['status'])); ?>
                            </div>
                            <a href="/dailyfix/booking-details.php?id=<?php echo $activity['id']; ?>" class="btn-main" style="padding: 8px 20px;">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No Activity Found</h3>
                        <p>You do not have any past or upcoming activities.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include_once __DIR__ . "/footer.php"; ?>
    <script defer src="/dailyfix/assets/js/app.js"></script>
</body>
</html>