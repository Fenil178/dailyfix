<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$workerId = $_GET['id'];
$worker = null;

try {
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name, u.profile_image, wp.bio, wp.experience_years, wp.hourly_rate
        FROM public.users u
        JOIN public.worker_profiles wp ON u.id = wp.user_id
        WHERE u.id = ? AND u.role = 'worker'
    ");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Book Worker Page Error: " . $e->getMessage());
}

if (!$worker) {
    echo "Worker not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book <?php echo htmlspecialchars($worker['full_name']); ?></title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/book_worker.css" />

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    </head>
<body>
    <?php include_once __DIR__ . "/../api/header.php"; ?>

    <main class="page-content">
        <div class="booking-container">
            <div class="worker-profile-panel">
                <img src="<?php echo htmlspecialchars($worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="profile-avatar">
                <h1><?php echo htmlspecialchars($worker['full_name']); ?></h1>
                <div class="profile-meta">
                    <span><i class="fas fa-star"></i> 4.8 Stars</span>
                    <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                </div>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($worker['bio'])); ?></p>
            </div>
            <div class="booking-form-panel">
                <h2>Book This Worker</h2>
                <form action="/dailyfix/api/create_booking.php" method="POST">
                    <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $userId; // from header.php ?>">

                    <div class="form-group">
                        <label for="service_details">Describe the work needed</label>
                        <textarea id="service_details" name="service_details" rows="4" required placeholder="e.g., Leaky kitchen sink, need to install a new ceiling fan..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="booking_time">Preferred Date & Time</label>
                        <input type="datetime-local" id="booking_time" name="booking_time" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Your Address</label>
                        <input type="text" id="address" name="address" required placeholder="123 Main St, Anytown">
                    </div>

                    <button type="submit" class="submit-btn">Send Booking Request</button>
                </form>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>