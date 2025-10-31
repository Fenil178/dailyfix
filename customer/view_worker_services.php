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
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    
    <style>
        /* Page Header */
        .page-header {
            max-width: 1100px;
            margin: 2rem auto 1rem auto;
            padding: 0 1rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color); /* FIXED */
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        .back-link i {
            font-size: 0.9rem;
        }

        /* Main Container */
        .booking-container {
            display: flex;
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 1rem 2rem 1rem;
            align-items: flex-start;
        }

        /* Worker Profile Panel */
        .worker-profile-panel {
            background: var(--background-color); /* FIXED */
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* FIXED */
            min-width: 320px;
            max-width: 350px;
            position: sticky;
            top: 100px;
        }

        .profile-avatar-custom {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem auto;
            display: block;
            border: 4px solid var(--primary-color);
        }

        .worker-profile-panel h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color); /* FIXED */
            text-align: center;
            margin-bottom: 1rem;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-color); /* FIXED */
            opacity: 0.8; /* FIXED */
        }

        .profile-meta span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .profile-meta i {
            color: var(--primary-color);
        }

        .profile-bio {
            font-size: 0.95rem;
            color: var(--text-color); /* FIXED */
            opacity: 0.8; /* FIXED */
            line-height: 1.6;
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Services List Panel */
        .services-list-panel {
            flex-grow: 1;
            min-width: 0;
        }

        .services-list-panel h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color); /* FIXED */
            margin-bottom: 1.5rem;
        }

        /* Service Category Group */
        .service-category-group {
            background: var(--background-color); /* FIXED */
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* FIXED */
            overflow: hidden;
        }

        .service-category-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color); /* FIXED */
            background: var(--hover-color);
        }

        .service-category-header i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        /* Service Item Row */
        .service-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
        }

        .service-item-row:last-child {
            border-bottom: none;
        }

        .service-item-row:hover {
            background: var(--hover-color);
        }

        .service-item-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .service-item-details i {
            font-size: 1.2rem;
            color: var(--text-color); /* FIXED */
            opacity: 0.7; /* FIXED */
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .service-item-details strong {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-color); /* FIXED */
        }

        /* Book Button */
        .service-item-book {
            flex-shrink: 0;
        }

        .book-now-btn {
            background-color: var(--primary-color);
            color: var(--text-color-white); /* FIXED */
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: opacity 0.2s ease, transform 0.2s ease;
            display: inline-block;
            white-space: nowrap;
        }

        .book-now-btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }

        /* Dark mode button text is handled by the --text-color-white var */

        /* Empty State */
        .empty-state {
            background: var(--background-color); /* FIXED */
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* FIXED */
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color); /* FIXED */
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            font-size: 1rem;
            color: var(--text-color); /* FIXED */
            opacity: 0.8; /* FIXED */
        }

        .btn-main {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--text-color-white); /* FIXED */
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s ease;
        }

        .btn-main:hover {
            opacity: 0.85;
        }

        /* Dark mode button text is handled by the --text-color-white var */

        /* Tablet Breakpoint */
        @media (max-width: 1024px) {
            .booking-container {
                gap: 1.5rem;
                padding: 0 1rem 2rem 1rem;
            }

            .worker-profile-panel {
                min-width: 280px;
                max-width: 300px;
                padding: 1.5rem;
            }

            .profile-avatar-custom {
                width: 100px;
                height: 100px;
            }

            .worker-profile-panel h1 {
                font-size: 1.3rem;
            }

            .services-list-panel h2 {
                font-size: 1.5rem;
            }
        }

        /* Mobile Breakpoint */
        @media (max-width: 900px) {
            .booking-container {
                flex-direction: column;
                padding-bottom: 5rem;
            }

            .worker-profile-panel {
                position: static;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                top: 0;
            }

            .services-list-panel {
                width: 100%;
            }
        }

        /* Small Mobile */
        @media (max-width: 600px) {
            .page-header {
                margin: 1rem auto 0.5rem auto;
            }

            .booking-container {
                padding: 0 0.5rem 5rem 0.5rem;
                gap: 1rem;
            }

            .worker-profile-panel {
                padding: 1.25rem;
            }

            .profile-avatar-custom {
                width: 90px;
                height: 90px;
            }

            .worker-profile-panel h1 {
                font-size: 1.2rem;
            }

            .profile-meta {
                font-size: 0.85rem;
                gap: 0.5rem;
            }

            .profile-bio {
                font-size: 0.9rem;
            }

            .services-list-panel h2 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }

            .service-category-header {
                padding: 0.875rem 1rem;
                font-size: 1.1rem;
            }

            .service-item-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem;
            }

            .service-item-details {
                width: 100%;
            }

            .service-item-details strong {
                font-size: 1rem;
            }

            .service-item-book {
                width: 100%;
            }

            .book-now-btn {
                width: 100%;
                text-align: center;
                padding: 0.75rem 1rem;
            }

            .empty-state {
                padding: 2rem 1rem;
            }

            .empty-state i {
                font-size: 2.5rem;
            }

            .empty-state h3 {
                font-size: 1.25rem;
            }

            .empty-state p {
                font-size: 0.9rem;
            }
        }

        /* Very Small Mobile */
        @media (max-width: 400px) {
            .service-category-header {
                font-size: 1rem;
            }

            .service-item-details i {
                font-size: 1rem;
                width: 20px;
            }

            .service-item-details strong {
                font-size: 0.95rem;
            }

            .book-now-btn {
                font-size: 0.85rem;
            }
        }
    </style>
    </head>
<body>
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