<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security check: Only allow logged-in workers to see this page
if (!isset($role) || $role !== 'worker') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

// Fetch all available sub-services to create the checklist
$allSubServices = [];
try {
    $allSubServices = $conn->query("SELECT id, name FROM public.sub_services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch sub-services for setup page: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Profile - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/worker_profile.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <?php include_once __DIR__ . "/../api/header.php"; ?>

    <main class="page-content">
        <div class="profile-container">
            <h1>Complete Your Worker Profile</h1>
            <p>Welcome, <?php echo htmlspecialchars($userName); ?>! Add your details below to start getting job requests.</p>

            <form action="/dailyfix/api/complete_worker_profile.php" method="POST">
                <div class="form-section">
                    <h3>Your Professional Details</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="experience_years">Years of Experience</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" step="1" placeholder="e.g., 5" required>
                        </div>
                        <div class="form-group">
                            <label for="hourly_rate">Hourly Rate ($)</label>
                            <input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.50" placeholder="e.g., 25.50">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio / Introduction</label>
                        <textarea id="bio" name="bio" rows="5" placeholder="Tell customers about yourself, your skills, and your experience..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Services You Offer</h3>
                    <p>Select all the services you are able to provide.</p>
                    <div class="services-checkbox-grid">
                        <?php foreach ($allSubServices as $sub): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="service-<?php echo $sub['id']; ?>" name="services[]" value="<?php echo $sub['id']; ?>">
                                <label for="service-<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Save Profile & Continue</button>
            </form>
        </div>
    </main>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>