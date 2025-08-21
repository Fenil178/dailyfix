<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

if (!isset($_GET['service'])) {
    header("Location: /dailyfix/customer/services.php");
    exit;
}

$serviceSlug = $_GET['service'];
$workers = [];
$serviceName = 'Service';

try {
    // 1. Find the sub-service ID from the slug
    $stmt = $conn->prepare("SELECT id, name FROM public.sub_services WHERE slug = ?");
    $stmt->execute([$serviceSlug]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($service) {
        $serviceId = $service['id'];
        $serviceName = $service['name'];

        // 2. Find all workers linked to this sub-service ID
        $stmt = $conn->prepare("
            SELECT u.id, u.full_name, u.profile_image, wp.bio, wp.hourly_rate, wp.experience_years
            FROM public.users u
            JOIN public.worker_profiles wp ON u.id = wp.user_id
            JOIN public.worker_services ws ON u.id = ws.user_id
            WHERE ws.sub_service_id = ? AND u.account_status = 'active' AND u.role = 'worker'
        ");
        $stmt->execute([$serviceId]);
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
    <link rel="stylesheet" href="/dailyfix/assets/css/worker_list.css" />
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    </head>
<body>
    <main class="page-content">
    <section class="services-hero">
        <h1>Available Workers for <?php echo htmlspecialchars($serviceName); ?></h1>
    </section>
    <section class="page-header">
        <a href="/dailyfix/customer/services.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Services</a>
    </section>

        <section class="worker-list-container">
            <?php if (count($workers) > 0): ?>
                <div class="worker-grid">
                <?php
                foreach ($workers as $worker): ?>
                    <div class="worker-card">
                        <img src="<?php echo htmlspecialchars($worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="worker-avatar">
                        <h3 class="worker-name"><?php echo htmlspecialchars($worker['full_name']); ?></h3>
                        <p class="worker-bio"><?php echo htmlspecialchars(substr($worker['bio'], 0, 100)) . '...'; ?></p>
                        <div class="worker-meta">
                            <span><i class="fas fa-star"></i> 4.8 (120 reviews)</span>
                            <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                        </div>
                        
                        <a href="/dailyfix/customer/book_worker.php?id=<?php echo $worker['id']; ?>&service=<?php echo urlencode($serviceSlug); ?>" class="view-profile-btn">
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