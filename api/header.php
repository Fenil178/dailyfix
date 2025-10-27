<?php
// This block is now in header.php and handles all session-related logic.

// THIS IS THE PERMANENT FIX:
// This stable path works correctly from any directory.
include_once __DIR__ . "/encryption.php";

$role = null;
$userId = null;
$userName = 'Guest';
$profile_imagePath = null;

// Decrypt user data from cookies to establish the session.
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $userName = decrypt_id($_COOKIE['encrypted_user_name']);
}
if (isset($_COOKIE['encrypted_profile_image'])) {
    $profile_imagePath = decrypt_id($_COOKIE['encrypted_profile_image']);
}

// Get the current page's filename for the active state logic
$currentPage = basename($_SERVER['PHP_SELF']);

// If the role or user ID can't be verified, redirect to the login page.
if ((!$role || !$userId) && $currentPage !== 'login.php' && $currentPage !== 'signup.php') {
    header("Location: /dailyfix/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - DailyFix</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="/dailyfix/assets/css/header.css" />
  <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<body>
<header>
<div class="navbar-wrapper">
<nav class="navbar">
    <div class="logo">
        <a href="/dailyfix/index.php">
            <img src="/dailyfix/assets/images/logo.png" style="width: 50px" alt="DailyFix Logo" />
        </a>
    </div>

    <ul class="nav-links" id="navLinks">
        <?php
            $links = [];
            if ($role === 'customer') {
                $links = [ 'dashboard.php' => 'Dashboard', 'services.php' => 'Browse Services', 'bookings.php' => 'My Bookings', 'reports.php' => 'My Reports', 'contact.php' => 'Help' ];
                $basePath = '/dailyfix/customer/';
            } elseif ($role === 'worker') {
                $links = [ 'dashboard.php' => 'Dashboard', 'jobs.php' => 'Job Requests', 'earnings.php' => 'My Earnings', 'reports.php' => 'My Reports', 'contact.php' => 'Help' ];
                $basePath = '/dailyfix/worker/';
            }

            foreach ($links as $file => $text) {
                // *** MODIFICATION: Skip Reports and Help from main nav ***
                if ($text === 'My Reports' || $text === 'Help') {
                    continue;
                }
                
                $url = "/dailyfix/dashboard.php";
                if ($file !== 'dashboard.php' && $file !== 'contact.php') {
                    $url = $basePath . $file;
                } elseif ($file === 'contact.php') {
                    $url = '/dailyfix/contact.php';
                }
                
                $activeClass = ($currentPage === $file) ? 'active' : '';
                echo "<li><a href='{$url}' class='{$activeClass}'>{$text}</a></li>";
            }
        ?>
    </ul>

    <div class="user-menu">
        <button class="profile-btn" id="profileBtn" title="User Menu">
            <?php if (!empty($profile_imagePath)): ?>
                <?php 
                    // This logic ensures the path is always correct
                    $avatarUrl = $profile_imagePath ?: '/dailyfix/assets/images/default-avatar.png';
                    if ($profile_imagePath && strpos($profile_imagePath, '/') !== 0) {
                        $avatarUrl = '/dailyfix/' . $profile_imagePath;
                    }
                ?>
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="My Profile" class="profile-avatar">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </button>
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="/dailyfix/profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
            
            <?php
                $reportsUrl = ($role === 'worker') ? '/dailyfix/worker/reports.php' : '/dailyfix/customer/reports.php';
                $helpUrl = '/dailyfix/contact.php';
                
                echo "<a href='{$reportsUrl}'><i class='fas fa-chart-bar'></i> My Reports</a>";
                echo "<a href='{$helpUrl}'><i class='fas fa-question-circle'></i> Help</a>";
            ?>
            <button id="theme-toggle-btn"><i class="fas fa-moon"></i> Theme</button>
            <a href="#" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div id="custom-logout-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Are you sure you want to log out?</h2>
            <p>You will be redirected to the login page.</p>
            <div class="modal-buttons">
                <button id="confirm-logout-btn">Yes, Log Out</button>
                <button id="cancel-logout-btn">Cancel</button>
            </div>
        </div>
    </div>
</nav>
</div>
</header>

<nav class="mobile-bottom-nav">
    <?php 
        // Define active states for cleaner HTML
        $isDashboard = ($currentPage === 'dashboard.php') ? 'active' : '';
        $isProfile = ($currentPage === 'profile.php') ? 'active' : '';
    ?>

    <?php if ($role === 'customer'): ?>
        <?php
            $isServices = ($currentPage === 'services.php') ? 'active' : '';
            $isBookings = ($currentPage === 'bookings.php') ? 'active' : '';
        ?>
        <a href="/dailyfix/dashboard.php" class="<?php echo $isDashboard; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo $basePath; ?>services.php" class="<?php echo $isServices; ?>">
            <i class="fas fa-search"></i>
            <span>Services</span>
        </a>
        <a href="<?php echo $basePath; ?>bookings.php" class="<?php echo $isBookings; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Bookings</span>
        </a>

    <?php elseif ($role === 'worker'): ?>
            <?php
            $isJobs = ($currentPage === 'jobs.php') ? 'active' : '';
            $isEarnings = ($currentPage === 'earnings.php') ? 'active' : '';
        ?>
        <a href="/dailyfix/dashboard.php" class="<?php echo $isDashboard; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo $basePath; ?>jobs.php" class="<?php echo $isJobs; ?>">
            <i class="fas fa-briefcase"></i>
            <span>Jobs</span>
        </a>
        <a href="<?php echo $basePath; ?>earnings.php" class="<?php echo $isEarnings; ?>">
            <i class="fas fa-dollar-sign"></i>
            <span>Earnings</span>
        </a>
    <?php endif; ?>
    
    <a href="/dailyfix/profile.php" class="<?php echo $isProfile; ?>">
        <i class="fas fa-user"></i>
        <span>Profile</span>
    </a>
</nav>
</body>
</html>