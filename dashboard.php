<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

// All session-related variables ($role, $userId, $userName, $profile_imagePath) are now available from header.php

// Initialize variables for dashboard data
$totalBookings = 0;
$pendingJobs = 0;
$completedJobs = 0;

try {
    // Fetch data based on the user's role
    if ($role === 'customer') {
        // Customer: Get count of their total bookings
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE customer_id = ?');
        $stmt->execute([$userId]);
        $totalBookings = $stmt->fetchColumn();

        // Customer: Get count of their completed jobs
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE customer_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();

    } elseif ($role === 'worker') {
        // Worker: Get count of their pending job requests
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE worker_id = ? AND status = \'pending\'');
        $stmt->execute([$userId]);
        $pendingJobs = $stmt->fetchColumn();
        
        // Worker: Get count of their completed jobs
        $stmt = $conn->prepare('SELECT COUNT(*) FROM dailyfix.bookings WHERE worker_id = ? AND status = \'completed\'');
        $stmt->execute([$userId]);
        $completedJobs = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // Log any database errors for debugging
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="/dailyfix/assets/css/dashboard.css">

  </head>

<script defer src="/dailyfix/assets/js/app.js"></script>
<body class="light-mode">

<section class="hero">
  <div class="hero-bg-carousel">
    <div class="slide active" style="background-image: url('/dailyfix/assets/images/01.jpg');"></div>
  </div>
  <div class="hero-content">
    <?php if (isset($_GET['action']) && $_GET['action'] === 'new_user'): ?>
        <h1>Welcome to the DailyFix Community, <br><?php echo htmlspecialchars($userName); ?>!</h1>
        <?php else: ?>
            <h1>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
        <?php endif; ?>
        <p>
            <h3>Your <?php echo htmlspecialchars(ucfirst($role)); ?> Dashboard</h3>
            Here's a quick overview of your activity on DailyFix.
        </p>
    </div>
</section>

<section class="services-preview section-fly">
  <h2>My Dashboard</h2>
  <div class="services-grid">
      <?php if ($role === 'customer'): ?>
          <div class="dashboard-card">
              <i class="fas fa-file-invoice"></i>
              <h3>My Total Bookings</h3>
              <p><?php echo $totalBookings; ?></p>
          </div>
          <div class="dashboard-card success-card">
              <i class="fas fa-check-circle"></i>
              <h3>Completed Jobs</h3>
              <p><?php echo $completedJobs; ?></p>
          </div>
          <div class="dashboard-card info-card">
              <i class="fas fa-search"></i>
              <h3>Find a Worker</h3>
              <a href="/dailyfix/customer/services.php" class="btn-card">Browse Services</a>
          </div>

      <?php elseif ($role === 'worker'): ?>
          <div class="dashboard-card worker-card">
              <i class="fas fa-hourglass-start"></i>
              <h3>Pending Job Requests</h3>
              <p><?php echo $pendingJobs; ?></p>
          </div>
          <div class="dashboard-card success-card">
              <i class="fas fa-check-double"></i>
              <h3>Completed Jobs</h3>
              <p><?php echo $completedJobs; ?></p>
          </div>
          <div class="dashboard-card">
              <i class="fas fa-user-cog"></i>
              <h3>My Profile</h3>
              <a href="#" class="btn-card">Update Profile</a>
          </div>
      <?php endif; ?>
  </div>
</section>

<section class="cta section-fly">
    <h2>Ready To Book Your Next Service?</h2>
    <p>Experience the convenience of DailyFix today!</p>
    <a href="/dailyfix/customer/services.php" class="btn-main">Book Now</a>
</section>
  
<?php include_once __DIR__ . "/api/footer.php"; ?>

</body>
</html>