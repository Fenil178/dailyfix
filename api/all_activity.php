<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php";

$allActivities = [];

try {
    if ($role === 'customer') {
        $stmt = $conn->prepare("
            SELECT b.id, b.status, b.booking_time, u.full_name as worker_name, b.service_details
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
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style>
        /* Common skeleton styles (loader, shimmer, dark-mode) */
        .skeleton-loader {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: var(--background-color-body, #f9f9f9);
            z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
        }
        .skeleton-loader.hidden { opacity: 0; pointer-events: none; }
        .skeleton-container {
            max-width: 1100px; width: 100%;
            padding: 0 1rem;
            margin: 1rem auto;
            margin-top: 80px; /* Adjust to match your header's height */
        }
        @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        .skeleton {
            animation: shimmer 1.5s infinite linear;
            background: linear-gradient(to right, 
            var(--hover-color, #f0f0f0) 8%, 
            var(--border-color, #e2e8f0) 18%, 
            var(--hover-color, #f0f0f0) 33%);
            background-size: 800px 104px; border-radius: 6px;
        }
        body.dark-mode .skeleton-loader { background-color: var(--background-color-body, #121212); }
        body.dark-mode .skeleton {
            background: linear-gradient(to right, 
            var(--hover-color, #2c2c2c) 8%, 
            var(--border-color, #334155) 18%, 
            var(--hover-color, #2c2c2c) 33%);
            background-size: 800px 104px;
        }

        /* Page-specific skeleton layout for all_activity.php */
        .skeleton-title { height: 38px; width: 300px; margin: 2rem 0; }
        .skeleton-list-card {
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }
        body.dark-mode .skeleton-list-card {
            background-color: var(--background-color-card, #1f1f1f);
            border: 1px solid var(--border-color, #334155);
        }
        .skeleton-list-item { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px; 
            width: 100%; 
            margin-bottom: 1rem; 
        }
        .skeleton-item-content { height: 36px; width: 60%; }
        .skeleton-item-status { height: 28px; width: 100px; }
        .skeleton-item-button { height: 40px; width: 120px; }
    </style>
</head>
<body>
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-title"></div>
            
            <div class="skeleton-list-card">
            <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-content"></div>
                <div class="skeleton skeleton-item-status"></div>
                <div class="skeleton skeleton-item-button"></div>
            </div>
            <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-content"></div>
                <div class="skeleton skeleton-item-status"></div>
                <div class="skeleton skeleton-item-button"></div>
            </div>
            <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-content"></div>
                <div class="skeleton skeleton-item-status"></div>
                <div class="skeleton skeleton-item-button"></div>
            </div>
            <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-content"></div>
                <div class="skeleton skeleton-item-status"></div>
                <div class="skeleton skeleton-item-button"></div>
            </div>
            </div>
        </div>
    </div>
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
                                        echo "Booking for <strong>" . htmlspecialchars($serviceName) . ($itemName ? " - " . htmlspecialchars($itemName) : "") . "</strong>";
                                    } else {
                                        echo "Booking Request from <strong>" . htmlspecialchars($activity['customer_name']) . "</strong>";
                                    }
                                    ?>
                                </p>
                                <small>
                                    Scheduled for <?php echo date("M d, Y", strtotime($activity['booking_time'])); ?>
                                </small>
                            </div>
                            <div class="item-status <?php echo htmlspecialchars($activity['status']); ?>">
                                <?php echo str_replace('_', ' ', htmlspecialchars($activity['status'])); ?>
                            </div>
                            <a href="/dailyfix/booking-details.php?id=<?php echo $activity['id']; ?>" class="btn-main">View Details</a>
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