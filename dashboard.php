<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

// --- START: PHP CODE TO FETCH SLIDER DATA ---
$sliderItems = [];
$limit = 3; // Number of items per category in the slider

try {
    // --- Fetch Latest Active Offers ---
    $sqlOffers = "SELECT wo.*, u.full_name as worker_name
                  FROM public.worker_offers wo
                  JOIN public.users u ON wo.worker_id = u.id
                  WHERE wo.is_active = true
                    AND (wo.valid_until IS NULL OR wo.valid_until >= NOW())
                    AND wo.uses_count < COALESCE(wo.max_uses, wo.uses_count + 1)
                  ORDER BY wo.created_at DESC
                  LIMIT :limit";
    $stmtOffers = $conn->prepare($sqlOffers);
    $stmtOffers->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtOffers->execute();
    $offers = $stmtOffers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($offers as $offer) {
        $discountText = ($offer['discount_type'] == 'percentage')
            ? $offer['discount_value'] . '%'
            : '₹' . number_format($offer['discount_value'], 2);
        $minBookingText = ($offer['min_booking_amount'] > 0)
            ? ' on bookings > ₹' . number_format($offer['min_booking_amount'], 2)
            : '';

        $sliderItems[] = [
            'type' => 'offer', // Added type for potential styling/logic
            'icon' => 'fas fa-tags text-warning', // This class is used by CSS now
            'title' => "Offer: {$discountText} OFF!",
            'text' => "Code '{$offer['coupon_code']}' from {$offer['worker_name']}{$minBookingText}.",
        ];
    }

    // --- Fetch Featured Workers (Verified) ---
    $sqlWorkers = "SELECT u.id, u.full_name, wp.bio
                   FROM public.users u
                   JOIN public.worker_profiles wp ON u.id = wp.user_id
                   WHERE u.role = 'worker' AND u.account_status = 'active' AND wp.is_verified = true
                   ORDER BY u.created_at DESC -- Consider a more relevant order?
                   LIMIT :limit";
    $stmtWorkers = $conn->prepare($sqlWorkers);
    $stmtWorkers->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtWorkers->execute();
    $workers = $stmtWorkers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($workers as $worker) {
        $bioSnippet = !empty($worker['bio']) && strlen($worker['bio']) > 80 ? substr($worker['bio'], 0, 77) . '...' : $worker['bio'];
        $sliderItems[] = [
            'type' => 'worker',
            'icon' => 'fas fa-user-check text-primary', // This class is used by CSS now
            'title' => "Featured: {$worker['full_name']}",
            'text' => $bioSnippet ?: 'Verified service provider ready to help.',
        ];
    }

    // --- Fetch New Services (Sub-Services) ---
    $sqlServices = "SELECT ss.name, s.name as parent_service_name
                    FROM public.sub_services ss
                    JOIN public.services s ON ss.service_id = s.id
                    ORDER BY ss.id DESC
                    LIMIT :limit";
    $stmtServices = $conn->prepare($sqlServices);
    $stmtServices->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtServices->execute();
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

    foreach ($services as $service) {
        $sliderItems[] = [
            'type' => 'service',
            'icon' => 'fas fa-tools text-success', // This class is used by CSS now
            'title' => "New Service: {$service['name']}",
            'text' => "Now available under {$service['parent_service_name']}. Book today!",
        ];
    }

    // Shuffle the items for variety if desired
    if (count($sliderItems) > 1) {
        shuffle($sliderItems);
    }

} catch (PDOException $e) {
    // Log error or handle gracefully
    error_log("Error fetching slider data: " . $e->getMessage()); // Log error
    $sliderItems = []; // Ensure slider doesn't break if DB query fails
}

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
    <link rel="stylesheet" href="/dailyfix/assets/css/header.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        body.dark-mode .skeleton-loader { background-color: var(--background-color-body, #121212); }
        body.dark-mode .skeleton {
            background: linear-gradient(to right, 
            var(--hover-color, #2c2c2c) 8%, 
            var(--border-color, #334155) 18%, 
            var(--hover-color, #2c2c2c) 33%);
            background-size: 800px 104px;
        }

        /* Page-specific skeleton layout for dashboard.php */
        .skeleton-welcome-header { display: flex; justify-content: space-between; align-items: center; margin: 2rem 0; }
        .skeleton-title { height: 38px; width: 40%; }
        .skeleton-search { height: 40px; width: 200px; }
        
        .skeleton-slider-card {
            height: 180px;
            width: 100%;
            margin-bottom: 2rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }
        .skeleton-dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        .skeleton-main-card {
            height: 350px;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
            padding: 1.5rem;
        }
        body.dark-mode .skeleton-slider-card,
        body.dark-mode .skeleton-main-card {
            background-color: var(--background-color-card, #1f1f1f);
            border: 1px solid var(--border-color, #334155);
        }
        .skeleton-card-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
        .skeleton-list-item { height: 40px; width: 100%; margin-bottom: 1rem; }

        @media (max-width: 900px) {
            .skeleton-dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .skeleton-welcome-header { flex-direction: column; align-items: flex-start; }
            .skeleton-search { width: 100%; margin-top: 1rem; }
        }
    </style>
</head>
<body>                
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton-welcome-header">
            <div class="skeleton skeleton-title"></div>
            <div class.skeleton skeleton-search"></div>
            </div>
            
            <div class="skeleton-slider-card"></div>
            
            <div class="skeleton-dashboard-grid">
            <div class="skeleton-main-card">
                <div class="skeleton skeleton-card-title"></div>
                <div class.skeleton skeleton-list-item"></div>
                <div class="skeleton skeleton-list-item"></div>
                <div class.skeleton skeleton-list-item"></div>
            </div>
            <div class="skeleton-main-card">
                <div class="skeleton skeleton-card-title"></div>
                <div class.skeleton skeleton-list-item"></div>
                <div class="skeleton skeleton-list-item"></div>
            </div>
            </div>
        </div>
    </div>
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

        <?php 
        if ($role === 'customer' && !empty($sliderItems)): 
        ?>
        <div class="container mt-4 mb-5">
            <h2 class="section-header-v4" style="margin-bottom: 1rem; border-bottom: none; padding: 0;">
                What's New & Offers
            </h2>
            <div class="card-slider-container">

                <button class="slider-arrow prev" aria-label="Previous Slide">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-arrow next" aria-label="Next Slide">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <?php foreach ($sliderItems as $index => $item): ?>
                    <div class="slider-card <?php echo ($index === 0) ? 'active' : ''; ?>" data-type="<?php echo htmlspecialchars($item['type']); ?>">
                        
                        <div class="slider-content-wrapper"> 
                            <?php if (!empty($item['icon'])): ?>
                                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                            <?php endif; ?>
                            
                            <div class="slider-text-content">
                                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                <p><?php echo htmlspecialchars($item['text']); ?></p>
                            </div>

                        </div>

                    </div>
                <?php endforeach; ?>
                
                </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid-v4 stats-grid-<?php echo $role; ?>">
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
                        <span class="stat-label">All Bookings</span>
                        <span class="stat-value"><?php echo $stats['secondary']; ?></span>
                        <span class="stat-desc">Services booked</span>
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