<?php
$serviceSlug = $_GET['service'] ?? ''; // Get the service slug from the URL

// Determine the correct back link URL and text
if (!empty($serviceSlug)) {
    $backLink = "/dailyfix/customer/find_workers.php?service=" . htmlspecialchars($serviceSlug);
    $backLinkText = "Back to Workers";
} else {
    $backLink = "/dailyfix/customer/services.php";
    $backLinkText = "Back to Services";
}

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
    <main class="page-content">  
        <div class="page-header" style="max-width: 1100px; margin: 2rem auto 1rem auto; padding: 0 1rem;">
            <a href="<?php echo $backLink; ?>" class="back-link"><i class="fas fa-arrow-left"></i> <?php echo $backLinkText; ?></a>        
        </div>
        <div class="booking-container">
            <div class="worker-profile-panel">
                <img src="<?php echo htmlspecialchars($worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="<?php echo htmlspecialchars($worker['full_name']); ?>" class="profile-avatar-custom">
                <h1><?php echo htmlspecialchars($worker['full_name']); ?></h1>
                <div class="profile-meta">
                    <span><i class="fas fa-star"></i> 4.8 Stars</span>
                    <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($worker['experience_years']); ?>+ years</span>
                </div>
                
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($worker['bio'])); ?></p>
            </div>

            <div class="booking-form-panel">
                <h2>Book This Worker</h2>
                <form id="booking-form" action="/dailyfix/api/create_booking.php" method="POST">
                    <input type="hidden" name="worker_id" value="<?php echo $worker['id']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $userId; ?>">
                    <input type="hidden" id="booking_time_combined" name="booking_time">

                    <div class="form-group">
                        <label for="service_details">Describe the work needed</label>
                        <textarea id="service_details" name="service_details" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="booking_date">Preferred Date</label>
                        <input type="date" id="booking_date" name="booking_date" required>
                    </div>

                    <div class="form-group">
                        <label>Preferred Time</label>
                        <div class="time-picker-group">
                            <select id="booking_hour" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="time-separator">:</span>
                            <select id="booking_minute" required>
                                <option value="00">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                            <select id="booking_ampm" required>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Your Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    <button type="submit" class="submit-btn">Send Booking Request</button>
                </form>
            </div>

        </div>
    </main>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>

    <script>
        // This script combines the custom time fields before submitting the form
        const bookingForm = document.getElementById('booking-form');
        const hourSelect = document.getElementById('booking_hour');
        const minuteSelect = document.getElementById('booking_minute');
        const ampmSelect = document.getElementById('booking_ampm');
        const hiddenTimeInput = document.getElementById('booking_time_combined');

        function updateHiddenTime() {
            let hour = parseInt(hourSelect.value, 10);
            const minute = minuteSelect.value;
            const ampm = ampmSelect.value;

            if (ampm === 'PM' && hour !== 12) {
                hour += 12;
            } else if (ampm === 'AM' && hour === 12) {
                hour = 0; // Midnight case
            }
            
            // Format to HH:MM for the backend
            const time24hr = `${String(hour).padStart(2, '0')}:${minute}`;
            hiddenTimeInput.value = time24hr;
        }

        // Update the hidden field whenever a time dropdown is changed
        hourSelect.addEventListener('change', updateHiddenTime);
        minuteSelect.addEventListener('change', updateHiddenTime);
        ampmSelect.addEventListener('change', updateHiddenTime);

        // Set the initial value when the page loads
        updateHiddenTime();
    </script>
</body>
</html>