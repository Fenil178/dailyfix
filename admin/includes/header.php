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
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">

    <style>body { opacity: 0; transition: opacity 0.3s ease; }</style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="logo">
                <a href="index.php"><img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo" /></a>
            </div>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="manage_users.php" class="<?php echo ($currentPage == 'manage_users.php') ? 'active' : ''; ?>">Users</a></li>
                <li><a href="view_bookings.php" class="<?php echo ($currentPage == 'view_bookings.php') ? 'active' : ''; ?>">Bookings</a></li>
                <li><a href="reports.php" class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">Reports</a></li>
                <li><a href="platform_earnings.php" class="<?php echo ($currentPage == 'platform_earnings.php') ? 'active' : ''; ?>">Earnings</a></li>
                <li><a href="manage_reviews.php" class="<?php echo ($currentPage == 'manage_reviews.php') ? 'active' : ''; ?>">Reviews</a></li>
                <li class="nav-item-dropdown">
                    <a href="#" class="nav-link-dropdown-toggle <?php echo ($currentPage == 'manage_services.php' || $currentPage == 'manage_sub_services.php' || $currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">
                        Services <i class="fas fa-caret-down dropdown-arrow"></i>
                    </a>
                    <ul class="dropdown-nav-menu">
                        <li><a href="manage_services.php" class="<?php echo ($currentPage == 'manage_services.php') ? 'active' : ''; ?>">Manage Categories</a></li>
                        <li><a href="manage_sub_services.php" class="<?php echo ($currentPage == 'manage_sub_services.php') ? 'active' : ''; ?>">Manage Sub-Services</a></li>
                        <li><a href="manage_sub_service_items.php" class="<?php echo ($currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">Manage Service Items</a></li>
                    </ul>
                </li>
                <li><a href="manage_worker_keys.php" class="<?php echo ($currentPage == 'manage_worker_keys.php') ? 'active' : ''; ?>">Worker Keys</a></li>
                <li><a href="manage_admins.php" class="<?php echo ($currentPage == 'manage_admins.php') ? 'active' : ''; ?>">Admins</a></li>
            </ul>
            
            <div class="user-menu">
                <button class="profile-btn" id="profileBtn" aria-label="Open user menu"><i class="fas fa-user-shield"></i></button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-user-info"><?php echo htmlspecialchars($adminName ?? 'Admin'); ?></div>
                    <button id="theme-toggle-btn"><i class="fas fa-moon"></i> Toggle Theme</button>
                    <a href="logout.php" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <button class="mobile-menu-toggle-btn" id="mobile-menu-toggle" aria-label="Toggle Navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="mobile-nav-slider" id="mobileNavSlider">
        <button class="mobile-menu-close-btn" id="mobile-menu-close" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>

        <ul class="mobile-nav-links">
            <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="manage_users.php" class="<?php echo ($currentPage == 'manage_users.php') ? 'active' : ''; ?>">Users</a></li>
            <li><a href="view_bookings.php" class="<?php echo ($currentPage == 'view_bookings.php') ? 'active' : ''; ?>">Bookings</a></li>
            <li><a href="reports.php" class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">Reports</a></li>
            <li><a href="platform_earnings.php" class="<?php echo ($currentPage == 'platform_earnings.php') ? 'active' : ''; ?>">Earnings</a></li>
            <li><a href="manage_reviews.php" class="<?php echo ($currentPage == 'manage_reviews.php') ? 'active' : ''; ?>">Reviews</a></li>
            <li class="nav-item-dropdown-mobile">
                <a href="#" class="nav-link-dropdown-toggle-mobile <?php echo ($currentPage == 'manage_services.php' || $currentPage == 'manage_sub_services.php' || $currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">
                    Services <i class="fas fa-caret-down dropdown-arrow"></i>
                </a>
                <ul class="dropdown-nav-menu-mobile">
                    <li><a href="manage_services.php" class="<?php echo ($currentPage == 'manage_services.php') ? 'active' : ''; ?>">Manage Categories</a></li>
                    <li><a href="manage_sub_services.php" class="<?php echo ($currentPage == 'manage_sub_services.php') ? 'active' : ''; ?>">Manage Sub-Services</a></li>
                    <li><a href="manage_sub_service_items.php" class="<?php echo ($currentPage == 'manage_sub_service_items.php') ? 'active' : ''; ?>">Manage Service Items</a></li>
                </ul>
            </li>
            <li><a href="manage_worker_keys.php" class="<?php echo ($currentPage == 'manage_worker_keys.php') ? 'active' : ''; ?>">Worker Keys</a></li>
            <li><a href="manage_admins.php" class="<?php echo ($currentPage == 'manage_admins.php') ? 'active' : ''; ?>">Admins</a></li>
        </ul>

        <div class="mobile-nav-controls">
            <button id="theme-toggle-btn-mobile"><i class="fas fa-moon"></i> Toggle Theme</button>
            <a href="#" id="logout-link-mobile"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>


    <div id="custom-logout-modal" class="modal confirmation-modal modal-danger" role="dialog" aria-hidden="true">
        <div class="modal-content">
            <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
            <div class="modal-icon"><i class="fas fa-sign-out-alt"></i></div>
            <h2 id="modal-title">Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            <div class="modal-buttons">
                <button id="confirm-logout-btn" class="btn btn-confirm">Yes, Log Out</button>
                <button id="cancel-logout-btn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- ORIGINAL DESKTOP DROPDOWN SCRIPT ---
        const servicesDropdown = document.querySelector('.nav-item-dropdown');
        if (servicesDropdown) {
            const dropdownToggle = servicesDropdown.querySelector('.nav-link-dropdown-toggle');
            
            dropdownToggle.addEventListener('click', function(event) {
                event.preventDefault();
                servicesDropdown.classList.toggle('open');
            });
        }
        document.addEventListener('click', function(event) {
            if (servicesDropdown && !servicesDropdown.contains(event.target)) {
                servicesDropdown.classList.remove('open');
            }
        });

        // --- NEW MOBILE MENU SCRIPT ---
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileNavSlider = document.getElementById('mobileNavSlider');
        const mobileNavOverlay = document.getElementById('mobileNavOverlay');

        const openMenu = () => {
            mobileNavSlider.classList.add('open');
            mobileNavOverlay.classList.add('open');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        };

        const closeMenu = () => {
            mobileNavSlider.classList.remove('open');
            mobileNavOverlay.classList.remove('open');
            document.body.style.overflow = '';
        };

        mobileMenuToggle.addEventListener('click', openMenu);
        mobileMenuClose.addEventListener('click', closeMenu);
        mobileNavOverlay.addEventListener('click', closeMenu);

        // --- NEW MOBILE *SERVICES* DROPDOWN SCRIPT ---
        const servicesDropdownMobile = document.querySelector('.nav-item-dropdown-mobile');
        if (servicesDropdownMobile) {
            const dropdownToggleMobile = servicesDropdownMobile.querySelector('.nav-link-dropdown-toggle-mobile');
            
            dropdownToggleMobile.addEventListener('click', function(event) {
                event.preventDefault();
                servicesDropdownMobile.classList.toggle('open');
            });
        }

        // --- SCRIPT TO CONNECT MOBILE BUTTONS TO ORIGINAL BUTTONS ---
        
        // Connect mobile theme toggle to original theme toggle
        const themeToggleBtnMobile = document.getElementById('theme-toggle-btn-mobile');
        const originalThemeToggleBtn = document.getElementById('theme-toggle-btn');
        if (themeToggleBtnMobile && originalThemeToggleBtn) {
            themeToggleBtnMobile.addEventListener('click', function() {
                originalThemeToggleBtn.click(); // Trigger a click on the original button
                
                // Update mobile icon if original has one (assuming it swaps)
                const originalIcon = originalThemeToggleBtn.querySelector('i');
                const mobileIcon = themeToggleBtnMobile.querySelector('i');
                if(originalIcon && mobileIcon) {
                    // This assumes your original script swaps the icon class
                    setTimeout(() => { 
                         mobileIcon.className = originalIcon.className;
                    }, 100);
                }
            });
        }

        // Connect mobile logout link to original logout link
        const logoutLinkMobile = document.getElementById('logout-link-mobile');
        const originalLogoutLink = document.getElementById('logout-link');
        if (logoutLinkMobile && originalLogoutLink) {
            logoutLinkMobile.addEventListener('click', function(event) {
                event.preventDefault(); // Stop <a> from navigating
                originalLogoutLink.click(); // Trigger a click on the original link
                closeMenu(); // Close the menu
            });
        }
    });
    </script>

    <main class="page-content" id="main-content">