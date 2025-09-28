<?php
include_once __DIR__ . '/auth_check.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - DailyFix</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/admin_style.css">
    
    <style>body { opacity: 0; transition: opacity 0.3s ease; }</style>
</head>
<body> 
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <a href="index.php"><img src="../assets/images/logo.png" alt="DailyFix Logo" /></a>
            </div>
            
            <button class="mobile-menu-btn" id="mobile-menu" aria-label="Toggle Navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>"><i class="fas fa-chart-dashboard"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="<?php echo ($currentPage == 'manage_users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="view_bookings.php" class="<?php echo ($currentPage == 'view_bookings.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li class="nav-item-dropdown">
    <a href="#" class="nav-link-dropdown-toggle <?php echo ($currentPage == 'manage_services.php' || $currentPage == 'manage_sub_services.php' || $currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">
        <i class="fas fa-layer-group"></i> Services <i class="fas fa-caret-down dropdown-arrow"></i>
    </a>
    <ul class="dropdown-nav-menu">
        <li><a href="manage_services.php" class="<?php echo ($currentPage == 'manage_services.php') ? 'active' : ''; ?>">Manage Categories</a></li>
        <li><a href="manage_sub_services.php" class="<?php echo ($currentPage == 'manage_sub_services.php') ? 'active' : ''; ?>">Manage Sub-Services</a></li>
        <li><a href="manage_sub_service_items.php" class="<?php echo ($currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">Manage Service Items</a></li>
    </ul>
</li>
                <li><a href="manage_admins.php" class="<?php echo ($currentPage == 'manage_admins.php') ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Admins</a></li>
            </ul>
            
            <div class="user-menu">
                <button class="profile-btn" id="profileBtn" aria-label="Open user menu"><i class="fas fa-user-shield"></i></button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-user-info"><?php echo htmlspecialchars($adminName ?? 'Admin'); ?></div>
                    <button id="theme-toggle-btn"><i class="fas fa-moon"></i> Toggle Theme</button>
                    <a href="logout.php" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="custom-logout-modal" class="modal" role="dialog" aria-hidden="true">
        <div class="modal-content">
            <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
            <h2 id="modal-title">Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            <div class="modal-buttons">
                <button id="confirm-logout-btn" class="btn btn-danger">Yes, Log Out</button>
                <button id="cancel-logout-btn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logic for the new Services dropdown
        const servicesDropdown = document.querySelector('.nav-item-dropdown');
        if (servicesDropdown) {
            const dropdownToggle = servicesDropdown.querySelector('.nav-link-dropdown-toggle');
            
            dropdownToggle.addEventListener('click', function(event) {
                // Prevent the link from navigating, as it's just a toggle
                event.preventDefault();
                servicesDropdown.classList.toggle('open');
            });
        }

        // Close the dropdown if the user clicks outside of it
        document.addEventListener('click', function(event) {
            if (servicesDropdown && !servicesDropdown.contains(event.target)) {
                servicesDropdown.classList.remove('open');
            }
        });
    });
    </script>

    <main class="page-content" id="main-content">
