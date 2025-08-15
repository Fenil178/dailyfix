<?php
include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

// Fetch user data based on role
$userData = null;
$workerProfile = null;
$allSubServices = [];
$workerServiceIds = [];

try {
    // Fetch basic user data
    $stmt = $conn->prepare("SELECT full_name, email, phone, profile_image FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user is a worker, fetch their specific profile and services
    if ($role === 'worker') {
        $stmt = $conn->prepare("SELECT bio, experience_years, hourly_rate FROM public.worker_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        $allSubServices = $conn->query("SELECT id, name FROM public.sub_services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT sub_service_id FROM public.worker_services WHERE user_id = ?");
        $stmt->execute([$userId]);
        $workerServiceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    error_log("Profile page error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Profile - DailyFix</title>
    
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/profile.css" /> <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <main class="page-content">
        <div class="profile-page-container">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($userData['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Profile Avatar" class="profile-header-avatar">
                <h1><?php echo htmlspecialchars($userData['full_name']); ?></h1>
                <p><?php echo htmlspecialchars($role); ?></p>
            </div>

            <div class="tab-nav">
                <button class="tab-link active" data-tab="details">My Details</button>
                <?php if ($role === 'worker'): ?>
                    <button class="tab-link" data-tab="professional">Professional Profile</button>
                    <button class="tab-link" data-tab="services">My Services</button>
                <?php else: // Customer ?>
                    <button class="tab-link" data-tab="history">Booking History</button>
                <?php endif; ?>
            </div>

            <div id="details" class="tab-content active">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <form action="/dailyfix/api/update_user_details.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userData['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>">
                        </div>
                        <button type="submit" class="submit-btn">Save Personal Info</button>
                    </form>
                </div>
            </div>

            <?php if ($role === 'worker'): ?>
                <div id="professional" class="tab-content">
                    <div class="form-section">
                        <h3>Professional Profile</h3>
                        <form action="/dailyfix/api/update_worker_profile.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="experience_years">Years of Experience</label>
                                    <input type="number" id="experience_years" name="experience_years" value="<?php echo htmlspecialchars($workerProfile['experience_years']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="hourly_rate">Hourly Rate ($)</label>
                                    <input type="number" id="hourly_rate" name="hourly_rate" step="0.50" value="<?php echo htmlspecialchars($workerProfile['hourly_rate']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="bio">Bio / Introduction</label>
                                <textarea id="bio" name="bio" rows="6"><?php echo htmlspecialchars($workerProfile['bio']); ?></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Save Professional Info</button>
                        </form>
                    </div>
                </div>

                <div id="services" class="tab-content">
                    <div class="form-section">
                        <h3>My Services</h3>
                         <form action="/dailyfix/api/update_worker_services.php" method="POST">
                            <div class="services-checkbox-grid">
                                <?php foreach ($allSubServices as $sub): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox"
                                               id="service-<?php echo $sub['id']; ?>"
                                               name="services[]"
                                               value="<?php echo $sub['id']; ?>"
                                               <?php echo in_array($sub['id'], $workerServiceIds) ? 'checked' : ''; ?>>
                                        <label for="service-<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="submit-btn" style="margin-top: 2rem;">Save Service Selections</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

             <?php if ($role === 'customer'): ?>
                <div id="history" class="tab-content">
                   <div class="form-section">
                       <h3>Booking History</h3>
                       <p>A summary of your recent bookings. For more details, visit the "My Bookings" page.</p>
                       <a href="/dailyfix/customer/bookings.php" class="submit-btn" style="text-align: center; text-decoration: none;">View All My Bookings</a>
                   </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Simple script for tab functionality
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');
                
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                link.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>