<?php
include_once __DIR__ . "/api/encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);
    if ($decrypted_role && in_array($decrypted_role, ['customer', 'worker', 'admin'])) {
        $role = $decrypted_role;
    }
}
if ($role) {
    $redirect_path = ($role === 'admin') ? '/dailyfix/admin/index.php' : '/dailyfix/dashboard.php';
    header("Location: " . $redirect_path);
    exit();
}

include_once __DIR__ . "/api/connect.php";

$stats = [
    'workers' => 0,
    'customers' => 0,
    'services' => 0,
    'average_review' => '0.0'
];
$services = [];
$reviews = [];

try {
    $stats['workers'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'worker' AND account_status = 'active'")->fetchColumn();
    $stats['customers'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'customer' AND account_status = 'active'")->fetchColumn();
    $stats['services'] = $conn->query("SELECT COUNT(*) FROM public.services")->fetchColumn();
    
    // MODIFIED: Calculate average review rating instead of completed bookings
    $avg_rating_raw = $conn->query("SELECT AVG(rating) FROM public.reviews")->fetchColumn();
    if ($avg_rating_raw) {
        $stats['average_review'] = number_format((float)$avg_rating_raw, 1);
    }

    // Fetch only 4 services as requested
    $services = $conn->query("SELECT name, icon, slug FROM public.services ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
    $reviews = $conn->query("
        SELECT r.rating, r.comment, u.full_name 
        FROM public.reviews r
        JOIN public.users u ON r.reviewer_id = u.id
        WHERE r.rating = 5 AND LENGTH(r.comment) > 20
        ORDER BY r.created_at DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Home page database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DailyFix - Your Trusted Home Service Partner</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }

        /* Enhanced Navbar with glassmorphism */
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }

        /* Added styles for guest action buttons in the new header */
        .navbar .guest-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar .guest-actions .btn-login,
        .navbar .guest-actions .btn-signup {
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid #ddd;
        }

        .navbar .guest-actions .btn-login {
            background-color: transparent;
            color: #333;
        }
        
        .navbar .guest-actions .btn-login:hover {
            background-color: #2563eb;
            color: white;
        }

        .navbar .guest-actions .btn-signup {
            color: #fff;
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .navbar .guest-actions .btn-signup:hover {
            opacity: 0.9;
        }
        
        /* Enhanced Welcome Banner with animated gradient */
        .welcome-banner {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
            position: relative;
            overflow: hidden;
            margin-top: 2rem;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, -30px) rotate(180deg); }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-text h1 {
            animation: fadeInUp 0.8s ease;
            text-shadow: 2px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .welcome-text p {
            animation: fadeInUp 1s ease;
            text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.15);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Stat Cards with hover effects */
        .stat-card-v4 {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .stat-card-v4::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .stat-card-v4:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card-v4:hover::before {
            left: 100%;
        }

        .stat-icon {
            transition: all 0.3s ease;
        }

        .stat-card-v4:hover .stat-icon {
            transform: scale(1.15) rotate(5deg);
        }

        /* Animated stat values */
        .stat-value {
            font-weight: 800;
            background: linear-gradient(135deg, currentColor, currentColor);
            -webkit-background-clip: text;
            background-clip: text;
        }

        /* Section Cards with depth */
        .section-card-v4 {
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .section-card-v4:hover {
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        /* Enhanced Service Cards */
        .service-card {
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
        }

        .service-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .service-card:hover::after {
            opacity: 1;
        }

        .service-card:hover {
            transform: translateY(-6px) scale(1.03);
        }

        /* /// START: HOW IT WORKS ENHANCEMENT /// */
        .how-it-works-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 2.5rem 1.5rem;
            gap: 2rem;
            position: relative;
        }

        .how-it-works-step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 0 1rem;
            z-index: 1;
        }
        
        /* Connecting lines */
        .how-it-works-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 40px; /* Align with middle of the icon */
            left: 50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #dbeafe, #60a5fa, #dbeafe);
            z-index: -1;
        }

        .step-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }

        .how-it-works-step:hover .step-icon-wrapper {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
        }

        .step-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: var(--text-color-light);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .how-it-works-container {
                flex-direction: column;
                align-items: center;
            }
            .how-it-works-step:not(:last-child)::after {
                width: 2px;
                height: 100%;
                left: calc(50% - 1px);
                top: 50%;
                background: linear-gradient(to bottom, #dbeafe, #60a5fa, #dbeafe);
            }
        }
        /* /// END: HOW IT WORKS ENHANCEMENT /// */

        /* Enhanced Testimonials */
        .testimonial-card {
            transition: all 0.3s ease;
            position: relative;
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: -10px;
            left: 10px;
            font-size: 4rem;
            color: rgba(102, 126, 234, 0.1);
            font-family: Georgia, serif;
            line-height: 1;
        }

        .testimonial-stars {
            animation: sparkle 2s ease-in-out infinite;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Enhanced Buttons */
        .btn-primary-v4 {
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-v4::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-primary-v4:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary-v4:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .btn-primary-v4 span {
            position: relative;
            z-index: 1;
        }

        /* Scroll reveal animation */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Enhanced CTA Banner */
        .cta-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            background-size: 200% 200%;
            animation: gradientShift 6s ease infinite;
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .custom {
            margin-bottom: 1.5rem;
        }

        /* Loading Animation for Stats */
        .stat-value.loading {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Smooth transitions for nav links */
        .nav-links a {
            transition: all 0.3s ease;
            position: relative;
            color: #333; /* Default link color */
            text-decoration: none;
        }

        .nav-links a:hover {
            color: #2563eb; /* Link color on hover */
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: currentColor;
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        /* Footer enhancement - Now handled by footer.css */
        
        /* Stagger animation for stat cards */
        .stat-card-v4:nth-child(1) { animation-delay: 0.1s; }
        .stat-card-v4:nth-child(2) { animation-delay: 0.2s; }
        .stat-card-v4:nth-child(3) { animation-delay: 0.3s; }
        .stat-card-v4:nth-child(4) { animation-delay: 0.4s; }

        /* Empty state enhancement */
        .empty-state {
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        footer {
        background-color: #ffffff; /* Light mode background color */
        color: #333; /* Light mode text color */
        padding: 0.5rem 0;
        text-align: center;
        border-top: 1px solid #e0e0e0;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .social-icons {
        margin-top: 1rem 0;
    }

    .social-icons a {
        color: #333; /* Light mode icon color */
        margin: 0 0.8rem;
        font-size: 1.5rem;
        transition: color 0.3s ease;
    }

    .social-icons a:hover {
        color: var(--primary-color); /* Uses primary color on hover */
    }   
    </style>
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="logo">
            <a href="/dailyfix/home.php">
                <img src="/dailyfix/assets/images/logo.png" style="width: 50px" alt="DailyFix Logo" />
            </a>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="#services">Services</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
        </ul>
        <div class="guest-actions">
            <a href="/dailyfix/login.php" class="btn-login">Login</a>
            <a href="/dailyfix/signup.php" class="btn-signup">Sign Up</a>
        </div>
    </nav>

    <main class="dashboard-container-v4">
        <div class="welcome-banner">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1>Your <span style="color: #fbbf24;">Trusted Partner</span> for Home Services</h1>
                    <p>Connect with verified local professionals in minutes. From plumbing to cleaning, we've got you covered.</p>
                </div>
                <a href="/dailyfix/signup.php" class="btn-primary-v4">
                    <i class="fas fa-user-plus"></i>
                    <span>Get Started Now</span>
                </a>
            </div>
        </div>

        <div class="stats-grid-v4 reveal">
            <div class="stat-card-v4 stat-success">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Happy Customers</span>
                    <span class="stat-value loading" data-target="<?php echo $stats['customers']; ?>">0</span>
                    <span class="stat-desc">Satisfaction guaranteed</span>
                </div>
            </div>
            <div class="stat-card-v4 stat-primary">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Verified Workers</span>
                    <span class="stat-value loading" data-target="<?php echo $stats['workers']; ?>">0</span>
                    <span class="stat-desc">Skilled & trusted professionals</span>
                </div>
            </div>
            <div class="stat-card-v4 stat-danger">
                <div class="stat-icon"><i class="fas fa-concierge-bell"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Service Categories</span>
                    <span class="stat-value loading" data-target="<?php echo $stats['services']; ?>">0</span>
                    <span class="stat-desc">Covering all your needs</span>
                </div>
            </div>
            <div class="stat-card-v4 stat-warning">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Average Review</span>
                    <span class="stat-value loading" data-target="<?php echo $stats['average_review']; ?>">0.0</span>
                    <span class="stat-desc">From customer ratings</span>
                </div>
            </div>
        </div>
        
        <div class="dashboard-columns-v4" style="grid-template-columns: 1fr; gap: 2rem;">
            <div class="section-card-v4 reveal" id="services">
                <div class="section-header-v4">
                    <h2><i class="fas fa-star"></i> Our Most Popular Services</h2>
                </div>
                <div style="padding: 1.5rem;">
                    <?php if (empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-wrench"></i>
                            <p>Services are being updated</p>
                            <small>Please check back soon!</small>
                        </div>
                    <?php else: ?>
                        <div class="stats-grid-v4" style="gap: 1rem;">
                            <?php foreach ($services as $service): ?>
                                <a href="/dailyfix/login.php" class="stat-card-v4 service-card" style="text-decoration: none; color: inherit;">
                                    <div class="stat-icon"><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i></div>
                                    <div class="stat-info">
                                        <span class="stat-value" style="font-size: 1.5rem;"><?php echo htmlspecialchars($service['name']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card-v4 reveal" id="how-it-works">
                <div class="section-header-v4">
                    <h2><i class="fas fa-rocket"></i> Get Help in 3 Simple Steps</h2>
                </div>
                <div class="how-it-works-container">
                    <div class="how-it-works-step">
                        <div class="step-icon-wrapper"><i class="fas fa-mouse-pointer"></i></div>
                        <h3 class="step-title">1. Choose a Service</h3>
                        <p class="step-description">Browse our wide range of categories and select the specific task you need done.</p>
                    </div>
                    <div class="how-it-works-step">
                        <div class="step-icon-wrapper"><i class="fas fa-calendar-check"></i></div>
                        <h3 class="step-title">2. Book a Professional</h3>
                        <p class="step-description">Pick a time that works for you and we'll match you with a top-rated, verified professional.</p>
                    </div>
                    <div class="how-it-works-step">
                        <div class="step-icon-wrapper"><i class="fas fa-check-circle"></i></div>
                        <h3 class="step-title">3. Relax & Rate</h3>
                        <p class="step-description">Your pro arrives and completes the job. Once done, you can rate your experience.</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($reviews)): ?>
            <div class="section-card-v4 reveal" id="testimonials">
                <div class="section-header-v4">
                    <h2><i class="fas fa-comment-dots"></i> What Our Customers Say</h2>
                </div>
                <div class="stats-grid-v4" style="padding: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                    <?php foreach($reviews as $review): ?>
                    <div class="stat-card-v4 testimonial-card" style="flex-direction: column; align-items: flex-start; gap: 1rem;">
                        <div class="testimonial-stars" style="color: var(--warning-color);">
                            <?php for($i = 0; $i < $review['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                        </div>
                        <p style="font-style: italic; color: var(--text-color-light);">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        <strong style="color: var(--text-color);">— <?php echo htmlspecialchars($review['full_name']); ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="cta-banner reveal">
                <div class="welcome-content" style="text-align: center; flex-direction: column;">
                    <div class="welcome-text">
                        <h2>Ready to Simplify Your Life?</h2>
                        <p>Join thousands of satisfied customers and professional workers on DailyFix today.</p>
                    </div>
                    <a href="/dailyfix/signup.php" class="btn-primary-v4 custom">
                        <i class="fas fa-user-plus"></i>
                        <span>Create Your Free Account</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> DailyFix. All Rights Reserved.</p>
        <div class="social-icons">
            <a href="mailto:jayrajparmar1509@gmail.com" title="Email"><i class="fas fa-envelope"></i></a>
            <a href="https://www.linkedin.com/in/jay-parmar-106195295/" target="_blank" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
            <a href="https://x.com/jayraj1509" target="_blank" title="X (Twitter)"><i class="fab fa-x-twitter"></i></a>
            <a href="https://github.com/Jayraj1509" target="_blank" title="GitHub"><i class="fab fa-github-alt"></i></a>
            <a href="https://www.instagram.com/_jayrajsinh_parmar_/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // MODIFIED: Animated stats counter to handle decimals
            const statNumbers = document.querySelectorAll('.stat-value');
            let animated = false;

            function animateStats() {
                if (animated) return;
                
                statNumbers.forEach(statNumber => {
                    statNumber.classList.remove('loading');
                    const target = parseFloat(statNumber.dataset.target);
                    if (isNaN(target)) return;
                    
                    const isFloat = target % 1 !== 0;
                    let current = 0;
                    const duration = 2000;
                    
                    const updateCount = () => {
                        const step = target / (duration / 16); // ~60fps
                        if (current < target) {
                            current += step;
                            if (current > target) current = target;
                            
                            if (isFloat) {
                                statNumber.innerText = current.toFixed(1);
                            } else {
                                statNumber.innerText = Math.floor(current).toLocaleString();
                            }
                            requestAnimationFrame(updateCount);
                        } else {
                            if (isFloat) {
                                statNumber.innerText = target.toFixed(1);
                            } else {
                                statNumber.innerText = target.toLocaleString();
                            }
                        }
                    };
                    requestAnimationFrame(updateCount);
                });
                
                animated = true;
            }

            // Intersection Observer for stats animation
            const statsSection = document.querySelector('.stats-grid-v4');
            if (statsSection) {
                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animateStats();
                            statsObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                
                statsObserver.observe(statsSection);
            }

            // Scroll reveal animation
            const reveals = document.querySelectorAll('.reveal');
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            reveals.forEach(reveal => {
                revealObserver.observe(reveal);
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const navbarHeight = navbar.offsetHeight;
                        const targetPosition = target.offsetTop - navbarHeight - 20;
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>