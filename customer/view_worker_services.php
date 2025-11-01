<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php"; // Provides $userId, $role

// Only customers can view this booking page
if ($role !== 'customer') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

if (!isset($_GET['worker_id']) || !is_numeric($_GET['worker_id'])) {
    header("Location: /dailyfix/customer/services.php");
    exit;
}

$workerId = (int)$_GET['worker_id'];

$worker = null;
$customer_lat = null;
$customer_lon = null;
$groupedServices = [];
$error = '';

try {
    // 1. Get customer's location (for distance)
    $stmt_cust = $conn->prepare("SELECT latitude, longitude FROM public.users WHERE id = ?");
    $stmt_cust->execute([$userId]);
    $customer_location = $stmt_cust->fetch(PDO::FETCH_ASSOC);
    if ($customer_location) {
        $customer_lat = $customer_location['latitude'];
        $customer_lon = $customer_location['longitude'];
    }

    // 2. Fetch Worker's main details
    $sql_worker = "
        SELECT u.id, u.full_name, u.profile_image, u.latitude, u.longitude,
               u.address_line1, u.address_line2, u.city, u.state, u.pincode,
               wp.bio, wp.experience_years,
               (SELECT COALESCE(AVG(rating), 0) FROM public.reviews WHERE worker_id = u.id) as avg_rating,
               (SELECT COUNT(id) FROM public.reviews WHERE worker_id = u.id) as review_count";
    
    if ($customer_lat && $customer_lon) {
        $sql_worker .= ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance";
    }
    
    $sql_worker .= "
        FROM public.users u
        JOIN public.worker_profiles wp ON u.id = wp.user_id
        WHERE u.id = ? AND u.role = 'worker'
    ";

    $stmt_worker = $conn->prepare($sql_worker);
    if ($customer_lat && $customer_lon) {
        $stmt_worker->execute([$customer_lat, $customer_lon, $customer_lat, $workerId]);
    } else {
        $stmt_worker->execute([$workerId]);
    }
    $worker = $stmt_worker->fetch(PDO::FETCH_ASSOC);

    if (!$worker) {
        throw new Exception("Worker not found.");
    }

    // 3. Fetch all services this worker offers, grouped by main category
    $stmt_services = $conn->prepare("
        SELECT 
            s.name as main_service_name, s.icon as main_service_icon,
            ss.id as sub_service_id, ss.name as sub_service_name, ss.icon as sub_service_icon, ss.slug as sub_service_slug
        FROM public.worker_services ws
        JOIN public.sub_services ss ON ws.sub_service_id = ss.id
        JOIN public.services s ON ss.service_id = s.id
        WHERE ws.user_id = ?
        ORDER BY s.name, ss.name
    ");
    $stmt_services->execute([$workerId]);
    $all_services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

    // Group them in PHP
    foreach ($all_services as $service) {
        $groupedServices[$service['main_service_name']]['icon'] = $service['main_service_icon'];
        $groupedServices[$service['main_service_name']]['items'][] = $service;
    }

} catch (Exception $e) {
    error_log("View Worker Services Error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Services by <?php echo htmlspecialchars($worker['full_name'] ?? 'Worker'); ?></title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/book_worker.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/view_worker_services.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
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

        /* Page-specific skeleton layout for view_worker_services.php */
        .skeleton-back-link { height: 20px; width: 150px; margin: 2rem 0 1rem 0; }
        .skeleton-profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        .skeleton-panel {
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
            height: fit-content;
        }
        .skeleton-avatar { width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 1rem auto; }
        .skeleton-line { height: 16px; margin-bottom: 1rem; border-radius: 4px; }
        .skeleton-line.title { height: 28px; width: 60%; margin: 0 auto 1rem auto; }
        .skeleton-line.meta { height: 14px; width: 80%; margin: 0 auto 1.5rem auto; }
        .skeleton-map { height: 200px; width: 100%; margin-top: 1.5rem; }
        
        .skeleton-list-title { height: 24px; width: 50%; margin-bottom: 1.5rem; }
        .skeleton-list-item { display: flex; justify-content: space-between; align-items: center; height: 40px; margin-bottom: 1rem; }
        .skeleton-item-name { height: 20px; width: 200px; }
        .skeleton-item-button { height: 40px; width: 100px; }

        @media (max-width: 900px) {
            .skeleton-profile-container { grid-template-columns: 1fr; }
        }
    </style>
    </head>
<body>    
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-back-link"></div>
            <div class="skeleton-profile-container">
            <div class="skeleton-panel">
                <div class="skeleton skeleton-avatar"></div>
                <div class="skeleton skeleton-line title"></div>
                <div class="skeleton skeleton-line meta"></div>
                <div class="skeleton skeleton-line" style="width: 100%;"></div>
                <div class="skeleton skeleton-line" style="width: 40%;"></div>
                <div class="skeleton skeleton-map"></div>
            </div>
            <div class="skeleton-panel">
                <div class="skeleton skeleton-line skeleton-list-title"></div>
                <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-name"></div>
                <div class="skeleton skeleton-item-button"></div>
                </div>
                <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-name"></div>
                <div class="skeleton skeleton-item-button"></div>
                </div>
                <div class="skeleton-list-item">
                <div class="skeleton skeleton-item-name"></div>
                <div class="skeleton skeleton-item-button"></div>
                </div>
                <div class.skeleton-list-item">
                <div class="skeleton skeleton-item-name"></div>
                <div class="skeleton skeleton-item-button"></div>
                </div>
            </div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="page-header">
            <a href="javascript:history.back()" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Search Results
            </a>
        </div>

        <?php if ($error): ?>
            <div class="management-container">
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Worker Not Found</h3>
                    <p><?php echo htmlspecialchars($error); ?></p>
                    <a href="/dailyfix/customer/services.php" class="btn-main" style="margin-top: 1rem;">Browse Services</a>
                </div>
            </div>
        <?php elseif ($worker): ?>
            <div class="booking-container">
                <div class="worker-profile-panel">
                    <?php
                        $workerAvatar = $worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png';
                        if ($worker['profile_image'] && strpos($worker['profile_image'], '/') !== 0) {
                            $workerAvatar = '/dailyfix/' . $worker['profile_image'];
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($workerAvatar); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="profile-avatar-custom">
                    <h1><?php echo htmlspecialchars($worker['full_name']); ?></h1>
                    <div class="profile-meta">
                        <span><i class="fas fa-star"></i> <?php echo number_format($worker['avg_rating'], 1); ?> (<?php echo $worker['review_count']; ?> reviews)</span>
                        <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                        <?php if (isset($worker['distance'])): ?>
                            <span><i class="fas fa-map-pin"></i> <?php echo round($worker['distance'], 2); ?> km away</span>
                        <?php endif; ?>
                    </div>
                    <p class="profile-bio"><?php echo nl2br(htmlspecialchars($worker['bio'])); ?></p>
                    <div class="profile-meta" style="margin-top: 1.5rem; justify-content: flex-start; text-align: left;">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>
                            <?php echo htmlspecialchars(implode(', ', array_filter([
                                $worker['address_line1'],
                                $worker['address_line2'],
                                $worker['city'],
                                $worker['state']
                            ]))); ?>
                        </span>
                    </div>
                </div>

                <div class="services-list-panel">
                    <h2>Services Offered by <?php echo htmlspecialchars(explode(' ', $worker['full_name'])[0]); ?></h2>
                    
                    <?php if (empty($groupedServices)): ?>
                        <div class="empty-state">
                            <i class="fas fa-toolbox"></i>
                            <h3>No Services Listed</h3>
                            <p>This worker has not listed any specific services yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groupedServices as $mainName => $data): ?>
                            <div class="service-category-group">
                                <div class="service-category-header">
                                    <i class="<?php echo htmlspecialchars($data['icon']); ?>"></i>
                                    <?php echo htmlspecialchars($mainName); ?>
                                </div>
                                <div class="service-items-list">
                                    <?php foreach ($data['items'] as $item): ?>
                                        <div class="service-item-row">
                                            <div class="service-item-details">
                                                <i class="<?php echo htmlspecialchars($item['sub_service_icon']); ?>"></i>
                                                <strong><?php echo htmlspecialchars($item['sub_service_name']); ?></strong>
                                            </div>
                                            <div class="service-item-book">
                                                <a href="/dailyfix/customer/book_worker.php?id=<?php echo $workerId; ?>&sub_service_id=<?php echo $item['sub_service_id']; ?>" class="book-now-btn">
                                                    Book Now
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
    <script defer src="/dailyfix/assets/js/app.js"></script>
</body>
</html>