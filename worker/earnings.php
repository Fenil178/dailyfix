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
    // Calculate sum of (worker_earning - discount_amount)
    $stmt = $conn->prepare("
        SELECT SUM(COALESCE(worker_earning, 0) - COALESCE(discount_amount, 0))
        FROM public.bookings
        WHERE worker_id = ? AND status = 'completed' AND payment_status = 'paid'
    ");
    $stmt->execute([$userId]);
    $totalEarnings = $stmt->fetchColumn() ?: 0;

    // Calculate sum of (worker_earning - discount_amount) for the current month
    $stmt = $conn->prepare("
        SELECT SUM(COALESCE(worker_earning, 0) - COALESCE(discount_amount, 0))
        FROM public.bookings
        WHERE worker_id = ?
          AND status = 'completed'
          AND payment_status = 'paid'
          AND created_at >= date_trunc('month', current_date) -- Use created_at or booking_time based on preference
    ");
    $stmt->execute([$userId]);
    $monthEarnings = $stmt->fetchColumn() ?: 0;

    // Fetch individual job earnings after discount
    $stmt = $conn->prepare("
        SELECT
            b.id, b.booking_time, b.service_details,
            u.full_name as customer_name,
            (COALESCE(b.worker_earning, 0) - COALESCE(b.discount_amount, 0)) AS amount_earned
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'completed' AND b.payment_status = 'paid'
        ORDER BY b.booking_time DESC
    ");
    $stmt->execute([$userId]);
    $completedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Worker earnings fetch error: " . $e->getMessage());
    // Optionally set an error message to display
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
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
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

        /* Page-specific skeleton layout for earnings.php */
        .skeleton-title { height: 38px; width: 300px; margin: 2rem 0; }
        .skeleton-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .skeleton-stat-card {
            height: 100px;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }
        .skeleton-list-card {
            height: 300px;
            width: 100%;
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }
        .skeleton-list-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
        .skeleton-list-item { height: 40px; width: 100%; margin-bottom: 1rem; }
        
        @media (max-width: 768px) {
            .skeleton-stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>  
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-title"></div>
            
            <div class="skeleton-stats-grid">
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            </div>
            
            <div class="skeleton-list-card">
            <div class="skeleton skeleton-list-title"></div>
            <div class="skeleton skeleton-list-item"></div>
            <div class="skeleton skeleton-list-item"></div>
            <div class="skeleton skeleton-list-item"></div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="management-container">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
                <h1 class="page-title" style="margin-bottom: 0;">My Earnings</h1>
                <a href="/dailyfix/worker/wallet.php" class="btn btn-main" style="text-decoration:none;">Go to My Wallet</a>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <h4>Total Earnings (All Time)</h4>
                    <p><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($totalEarnings, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h4>This Month's Earnings</h4>
                    <p><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($monthEarnings, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h4>Completed Jobs (Paid)</h4>
                    <p><?php echo count($completedJobs); ?></p>
                </div>
            </div>

            <h2 class="item-list-header">Payout History</h2>
            <div class="item-list">
                 <?php if (count($completedJobs) > 0): ?>
                    <?php foreach ($completedJobs as $job): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <p><strong>Job #<?php echo htmlspecialchars($job['id']); ?> with <?php echo htmlspecialchars($job['customer_name']); ?></strong></p>
                                <small>Completed on <?php echo date("M d, Y", strtotime($job['booking_time'])); ?></small>
                            </div>
                            <div class="item-value">
                                +<i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($job['amount_earned'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
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