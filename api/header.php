<?php
// This block is now in header.php and handles all session-related logic.
// All files that include this header will automatically have this logic executed.

// Include the encryption file. The path is relative to the core directory.
include_once __DIR__ . "/../api/encryption.php";

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

// If the role or user ID can't be verified, redirect to the login page.
if (!$role || !$userId) {
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
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <a href="/dailyfix/index.php">
            <img src="/dailyfix/assets/images/logo.png" style="width: 50px" alt="DailyFix Logo" />
        </a>
    </div>

    <ul class="nav-links" id="navLinks">
        <li><a href="/dailyfix/dashboard.php">Dashboard</a></li>
        <li><a href="/dailyfix/customer/services.php">Services</a></li>
        <li><a href="/dailyfix/customer/about.php">About</a></li>
        <li><a href="/dailyfix/customer/contact.php">Help</a></li>
    </ul>

    <div class="user-menu">
        <button class="profile-btn" id="profileBtn" title="User Menu">
            <?php if (!empty($profile_imagePath)): ?>
                <img src="<?php echo htmlspecialchars($profile_imagePath); ?>" alt="My Profile" class="profile-avatar">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </button>
        <div class="dropdown-menu" id="dropdownMenu">
            <a href="/dailyfix/profile.php">
                <i class="fas fa-user-circle"></i>
                My Profile
            </a>
            <button id="theme-toggle-btn">
                <i class="fas fa-moon"></i>
                Theme
            </button>
            <a href="#" id="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
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
</body>
</html>