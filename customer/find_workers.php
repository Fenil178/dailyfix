<?php
include_once __DIR__ . "/../api/encryption.php"; // Added for security and decryption
include_once __DIR__ . "/../api/connect.php"; // Include your database connection

$isLoggedIn = false;

// Check for user cookies (Copied from services.php for consistency)
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $role = decrypt_id($_COOKIE['encrypted_user_role']);

    if ($userId && $role) {
        $isLoggedIn = true;
    }
}

// Redirect if not logged in (Copied from services.php for consistency)
if (!$isLoggedIn) {
    header("Location: /dailyfix/login.php");
    exit;
}

if (!isset($_GET['service'])) {
    header("Location: /dailyfix/customer/services.php");
    exit;
}

$serviceSlug = $_GET['service'];
$workers = [];
$serviceName = 'Service';
$customer_lat = null;
$customer_lon = null;

// Get customer's location
try {
    // Note: The $userId variable is now guaranteed to be set by the logic above
    $stmt = $conn->prepare("SELECT latitude, longitude FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $customer_location = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer_location) {
        $customer_lat = $customer_location['latitude'];
        $customer_lon = $customer_location['longitude'];
    }
} catch (PDOException $e) {
    error_log("Find Workers Error: " . $e->getMessage());
}

try {
    // 1. Find the sub-service ID from the slug
    $stmt = $conn->prepare("SELECT id, name FROM public.sub_services WHERE slug = ?");
    $stmt->execute([$serviceSlug]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($service) {
        $serviceId = $service['id'];
        $serviceName = $service['name'];

        // 2. Find all workers linked to this sub-service ID and calculate distance and average rating
        $sql = "
            SELECT 
                u.id, 
                u.full_name, 
                u.profile_image, 
                u.latitude, 
                u.longitude, 
                wp.bio, 
                wp.hourly_rate, 
                wp.experience_years,
                (SELECT AVG(rating) FROM public.reviews WHERE worker_id = u.id) as avg_rating,
                (SELECT COUNT(id) FROM public.reviews WHERE worker_id = u.id) as review_count";
        
        if ($customer_lat && $customer_lon) {
            $sql .= ", (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) AS distance";
        }
        
        $sql .= "
            FROM public.users u
            JOIN public.worker_profiles wp ON u.id = wp.user_id
            JOIN public.worker_services ws ON u.id = ws.user_id
            WHERE ws.sub_service_id = ? AND u.account_status = 'active' AND u.role = 'worker'
        ";

        if ($customer_lat && $customer_lon) {
            $sql .= " ORDER BY distance ASC";
        }

        $stmt = $conn->prepare($sql);
        
        if ($customer_lat && $customer_lon) {
            $stmt->execute([$customer_lat, $customer_lon, $customer_lat, $serviceId]);
        } else {
            $stmt->execute([$serviceId]);
        }
        
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Find Workers Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Find Workers for <?php echo htmlspecialchars($serviceName); ?></title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" /> 
    <link rel="stylesheet" href="/dailyfix/assets/css/services.css" /> 
    <link rel="stylesheet" href="/dailyfix/assets/css/worker_list.css" /> 
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
        body.dark-mode .skeleton-loader { background-color: var(--background-color-body, #121212); }
        body.dark-mode .skeleton {
            background: linear-gradient(to right, 
            var(--hover-color, #2c2c2c) 8%, 
            var(--border-color, #334155) 18%, 
            var(--hover-color, #2c2c2c) 33%);
            background-size: 800px 104px;
        }

        /* Page-specific skeleton layout for find_workers.php */
        .skeleton-back-link { height: 20px; width: 150px; margin: 2rem 0 1rem 0; }
        .skeleton-title { height: 38px; width: 40%; margin-bottom: 1.5rem; }
        .skeleton-filter-bar { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .skeleton-filter { height: 40px; width: 120px; }
        .skeleton-worker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .skeleton-worker-card {
            height: 280px;
            border: 1px solid var(--border-color, #e2e8f0);
            background-color: var(--background-color-card, #fff);
            border-radius: 8px;
            padding: 1rem;
        }
        body.dark-mode .skeleton-worker-card {
            background-color: var(--background-color-card, #1f1f1f);
            border: 1px solid var(--border-color, #334155);
        }
        .skeleton-card-avatar { height: 60px; width: 60px; border-radius: 50%; margin-bottom: 1rem; }
        .skeleton-line { height: 16px; margin-bottom: 1rem; border-radius: 4px; }
        .skeleton-line.name { height: 20px; width: 60%; }
        .skeleton-line.meta { height: 14px; width: 80%; }
        .skeleton-card-button { height: 40px; width: 100%; margin-top: 1.5rem; }

        @media (max-width: 900px) { .skeleton-worker-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .skeleton-worker-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="light-mode">
    <?php include_once __DIR__ . "/../api/header.php"; ?>
    
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-back-link"></div>
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton-filter-bar">
            <div class="skeleton skeleton-filter"></div>
            <div class="skeleton skeleton-filter"></div>
            </div>
            
            <div class="skeleton-worker-grid">
            <div class="skeleton-worker-card">
                <div class="skeleton skeleton-card-avatar"></div>
                <div class="skeleton skeleton-line name"></div>
                <div class="skeleton skeleton-line meta"></div>
                <div class="skeleton skeleton-line" style="width: 100%;"></div>
                <div class="skeleton skeleton-line" style="width: 40%;"></div>
                <div class="skeleton skeleton-card-button"></div>
            </div>
            <div class="skeleton-worker-card">
                <div class="skeleton skeleton-card-avatar"></div>
                <div class="skeleton skeleton-line name"></div>
                <div class="skeleton skeleton-line meta"></div>
                <div class="skeleton skeleton-line" style="width: 100%;"></div>
                <div class="skeleton skeleton-line" style="width: 40%;"></div>
                <div class="skeleton skeleton-card-button"></div>
            </div>
            <div class="skeleton-worker-card">
                <div class="skeleton skeleton-card-avatar"></div>
                <div class="skeleton skeleton-line name"></div>
                <div class="skeleton skeleton-line meta"></div>
                <div class="skeleton skeleton-line" style="width: 100%;"></div>
                <div class="skeleton skeleton-line" style="width: 40%;"></div>
                <div class="skeleton skeleton-card-button"></div>
            </div>
            </div>
        </div>
    </div>

    <main class="page-content">
        <section class="worker-list-container section-fly">
            <a href="/dailyfix/customer/services.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Services</a>
            <h1 class="section-title">Available Workers for <?php echo htmlspecialchars($serviceName); ?></h1>
            
            <?php if (count($workers) > 0): ?>
                <div class="worker-grid">
                <?php
                foreach ($workers as $worker): ?>
                    <div class="worker-card">
                        <?php
                            $workerAvatar = $worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png';
                            if ($worker['profile_image'] && strpos($worker['profile_image'], '/') !== 0) {
                                $workerAvatar = '/dailyfix/' . $worker['profile_image'];
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($workerAvatar); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="worker-avatar">
                        <h3 class="worker-name"><?php echo htmlspecialchars($worker['full_name']); ?></h3>
                        <p class="worker-bio"><?php echo htmlspecialchars(substr($worker['bio'], 0, 100)) . '...'; ?></p>
                        <div class="worker-meta">
                            <span><i class="fas fa-star"></i> <?php echo $worker['avg_rating'] ? number_format($worker['avg_rating'], 1) : 'New'; ?> (<?php echo $worker['review_count']; ?> reviews)</span>
                            <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                            <?php if (isset($worker['distance'])): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo round($worker['distance'], 2); ?> km away</span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="/dailyfix/customer/book_worker.php?id=<?php echo $worker['id']; ?>&sub_service_id=<?php echo $service['id']; ?>" class="view-profile-btn">
                            View Profile & Book
                        </a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-workers-found">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>No Workers Found</h2>
                    <p>We're sorry, but no workers are currently available for this service. Please check back later.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>