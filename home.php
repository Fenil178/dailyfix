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
/** @var PDO $conn */

// ==================== CACHING SYSTEM ====================
function getCachedData($key, $callback, $ttl = 300) {
    $cache_dir = __DIR__ . "/cache";
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . "/{$key}.json";
    
    // Check if cache file exists and is still valid
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        // Attempt to decode JSON, return null on failure
        $data = json_decode(file_get_contents($cache_file), true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }
    
    // Cache miss or invalid, generate new data
    $data = $callback();
    
    // Only cache if data generation was successful (not null)
    if ($data !== null) {
        file_put_contents($cache_file, json_encode($data));
    }
    return $data;
}


// ==================== INITIALIZE DATA ARRAYS ====================
$stats = [
    'total_customers' => 0, 'new_customers_month' => 0, 'total_workers' => 0,
    'online_workers' => 0, 'total_services' => 0, 'completed_bookings' => 0,
    'average_review' => '0.0', 'total_reviews' => 0, 'success_rate' => '0',
    'bookings_today' => 0
];
$services = []; $reviews = []; $featured_workers = []; $recent_activity = []; $trust_metrics = [];

try {
    // ==================== COMPREHENSIVE STATISTICS ====================
    $stats = getCachedData('homepage_stats', function() use ($conn) {
        try {
            $data = [
                'total_customers' => 0, 'new_customers_month' => 0, 'total_workers' => 0,
                'online_workers' => 0, 'total_services' => 0, 'completed_bookings' => 0,
                'average_review' => '0.0', 'total_reviews' => 0, 'success_rate' => '0',
                'bookings_today' => 0
            ];

            // User stats
            $user_stats = $conn->query("
                SELECT 
                    COUNT(DISTINCT CASE WHEN role = 'customer' AND account_status = 'active' THEN id END) as total_customers,
                    COUNT(DISTINCT CASE WHEN role = 'customer' AND account_status = 'active' 
                        AND created_at >= CURRENT_DATE - INTERVAL '30 days' THEN id END) as new_customers_month,
                    COUNT(DISTINCT CASE WHEN role = 'worker' AND account_status = 'active' THEN id END) as total_workers,
                    0 AS online_workers
                FROM public.users
            ")->fetch(PDO::FETCH_ASSOC);
            if ($user_stats) { $data = array_merge($data, $user_stats); }

            $data['total_services'] = $conn->query("SELECT COUNT(*) FROM public.services")->fetchColumn();
            
            $booking_stats = $conn->query("
                SELECT COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                    COALESCE(ROUND(CAST(COUNT(CASE WHEN status = 'completed' THEN 1 END) AS DECIMAL) / NULLIF(COUNT(*), 0) * 100, 1), 0) as success_rate,
                    COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as bookings_today
                FROM public.bookings
            ")->fetch(PDO::FETCH_ASSOC);
            if ($booking_stats) { $data = array_merge($data, $booking_stats); }

            $review_stats = $conn->query("
                SELECT COALESCE(ROUND(AVG(rating), 1), 0) as average_review, COUNT(*) as total_reviews
                FROM public.reviews
            ")->fetch(PDO::FETCH_ASSOC);
            if ($review_stats) {
                $data['average_review'] = number_format((float)$review_stats['average_review'], 1);
                $data['total_reviews'] = $review_stats['total_reviews'];
            }
            return $data;
        } catch (PDOException $e) {
            error_log("Stats Cache Callback Error: " . $e->getMessage());
            return null; // Return null on error
        }
    }, 300); // Cache for 5 minutes

    // ==================== DYNAMIC SERVICES WITH METRICS ====================
    $services = getCachedData('homepage_services', function() use ($conn) {
        try {
            return $conn->query("
                SELECT 
                    s.id, s.name, s.icon, s.slug,
                    COUNT(DISTINCT ws.user_id) as available_workers,
                    COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating
                FROM public.services s
                LEFT JOIN public.sub_services ss ON s.id = ss.service_id
                LEFT JOIN public.worker_services ws ON ss.id = ws.sub_service_id
                LEFT JOIN public.bookings b ON ws.user_id = b.worker_id AND b.status = 'completed'
                LEFT JOIN public.reviews r ON b.id = r.booking_id
                GROUP BY s.id, s.name, s.icon, s.slug
                ORDER BY COUNT(DISTINCT b.id) DESC, s.name
                LIMIT 8
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Services Cache Callback Error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }, 600); // Cache for 10 minutes

    // ==================== DYNAMIC TESTIMONIALS ====================
    $reviews = getCachedData('homepage_reviews', function() use ($conn) {
        try {
            return $conn->query("
                SELECT DISTINCT ON (r.id) r.id, r.rating, r.comment, r.created_at, u.full_name, s.name as service_name, s.icon as service_icon
                FROM public.reviews r
                JOIN public.users u ON r.reviewer_id = u.id
                JOIN public.bookings b ON r.booking_id = b.id
                JOIN public.worker_services ws ON b.worker_id = ws.user_id
                JOIN public.sub_services ss ON ws.sub_service_id = ss.id
                JOIN public.services s ON ss.service_id = s.id
                WHERE LENGTH(r.comment) > 20 AND r.rating >= 4
                ORDER BY r.id, r.created_at DESC 
                LIMIT 6
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Reviews Cache Callback Error: " . $e->getMessage());
            return [];
        }
    }, 300);

    // ==================== FEATURED WORKERS ====================
    $featured_workers = getCachedData('featured_workers', function() use ($conn) {
        try {
            return $conn->query("
                SELECT u.id, u.full_name, u.profile_image, u.city,
                    COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
                    COUNT(DISTINCT b.id) as completed_jobs,
                    STRING_AGG(DISTINCT s.name, ', ') as specializations
                FROM public.users u
                LEFT JOIN public.bookings b ON u.id = b.worker_id AND b.status = 'completed'
                LEFT JOIN public.reviews r ON b.id = r.booking_id
                LEFT JOIN public.worker_services ws ON u.id = ws.user_id
                LEFT JOIN public.sub_services ss ON ws.sub_service_id = ss.id
                LEFT JOIN public.services s ON ss.service_id = s.id
                WHERE u.role = 'worker' AND u.account_status = 'active'
                GROUP BY u.id, u.full_name, u.profile_image, u.city
                HAVING COUNT(DISTINCT b.id) >= 3
                ORDER BY avg_rating DESC, completed_jobs DESC
                LIMIT 4
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Featured Workers Cache Callback Error: " . $e->getMessage());
            return [];
        }
    }, 600);
    
    // ==================== RECENT ACTIVITY FEED ====================
    $recent_activity = getCachedData('recent_activity', function() use ($conn) {
        try {
            return $conn->query("
                SELECT DISTINCT ON (b.id) b.id, s.name as service_name, s.icon as service_icon, u.city as location, b.created_at
                FROM public.bookings b
                JOIN public.users u ON b.customer_id = u.id
                JOIN public.worker_services ws ON b.worker_id = ws.user_id
                JOIN public.sub_services ss ON ws.sub_service_id = ss.id
                JOIN public.services s ON ss.service_id = s.id
                WHERE b.created_at >= NOW() - INTERVAL '24 hours' AND u.city IS NOT NULL
                ORDER BY b.id, b.created_at DESC
                LIMIT 8
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Recent Activity Cache Callback Error: " . $e->getMessage());
            return [];
        }
    }, 180); // Cache for 3 minutes

    // ==================== TRUST METRICS ====================
    $trust_metrics = getCachedData('trust_metrics', function() use ($conn) {
        try {
            $data = ['bookings_this_month' => 0, 'days_in_operation' => 0, 'customer_satisfaction' => '0'];
            $metrics = $conn->query("
                SELECT COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as bookings_this_month,
                    COUNT(DISTINCT DATE(created_at)) as days_in_operation
                FROM public.bookings
            ")->fetch(PDO::FETCH_ASSOC);
            if ($metrics) { $data = array_merge($data, $metrics); }
            $satisfaction = $conn->query("
                SELECT COALESCE(ROUND(CAST(COUNT(CASE WHEN rating >= 4 THEN 1 END) AS DECIMAL) / NULLIF(COUNT(*), 0) * 100, 0), 0) as customer_satisfaction
                FROM public.reviews
            ")->fetchColumn();
            $data['customer_satisfaction'] = $satisfaction ?: '0';
            return $data;
        } catch (PDOException $e) {
            error_log("Trust Metrics Cache Callback Error: " . $e->getMessage());
            return null;
        }
    }, 300);

    // Ensure stats/metrics have default values if caching failed
    $stats = $stats ?: [
        'total_customers' => 0, 'new_customers_month' => 0, 'total_workers' => 0,
        'online_workers' => 0, 'total_services' => 0, 'completed_bookings' => 0,
        'average_review' => '0.0', 'total_reviews' => 0, 'success_rate' => '0',
        'bookings_today' => 0
    ];
    $trust_metrics = $trust_metrics ?: ['bookings_this_month' => 0, 'days_in_operation' => 0, 'customer_satisfaction' => '0'];


} catch (PDOException $e) {
    // General connection or critical query error
    error_log("Home page database error: " . $e->getMessage());
    // Potentially set an error flag to display a message to the user
    // For now, let the empty arrays handle the display gracefully
}


function timeAgo($datetime) {
    if (!$datetime) return 'sometime ago';
    $timestamp = strtotime($datetime); $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now'; if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DailyFix</title>
    <meta name="description" content="Connect with verified local professionals for all your home service needs. From plumbing to cleaning, <?php echo number_format((int)$stats['total_workers']); ?>+ skilled workers ready to help.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/header.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/home.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="logo"><a href="/dailyfix/home.php"><img src="/dailyfix/assets/images/logo.png" style="width: 50px" alt="DailyFix Logo" /></a></div>
        <ul class="nav-links" id="navLinks">
            <li><a href="#services">Services</a></li><li><a href="#featured-workers">Top Workers</a></li><li><a href="#testimonials">Testimonials</a></li><li><a href="#how-it-works">How It Works</a></li>
        </ul>
        <div class="guest-actions"><a href="/dailyfix/login.php" class="btn-login">Login</a><a href="/dailyfix/signup.php" class="btn-signup">Sign Up</a></div>
    </nav>

    <main class="dashboard-container-v4">
        <div class="welcome-banner"><div class="welcome-content"><div class="welcome-text"><h1>Your <span style="color: #fbbf24;">Trusted Partner</span> for Home Services</h1><p>Connect with <?php echo number_format((int)$stats['total_workers']); ?>+ verified local professionals in minutes. From plumbing to cleaning, we've got you covered.</p><?php if ((int)$trust_metrics['customer_satisfaction'] > 0): ?><div style="margin-top: 1rem;"><span class="trust-badge"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($trust_metrics['customer_satisfaction']); ?>% Customer Satisfaction</span></div><?php endif; ?></div><a href="/dailyfix/signup.php" class="btn-primary-v4"><i class="fas fa-user-plus"></i> <span>Get Started Now</span></a></div></div>
        <div class="stats-grid-v4 reveal"><div class="stat-card-v4 stat-success"><div class="stat-icon"><i class="fas fa-users"></i></div><div class="stat-info"><span class="stat-label">Happy Customers</span><span class="stat-value loading" data-target="<?php echo (int)$stats['total_customers']; ?>">0</span><span class="stat-desc">+<?php echo (int)$stats['new_customers_month']; ?> this month</span></div></div><div class="stat-card-v4 stat-primary"><div class="stat-icon"><i class="fas fa-user-tie"></i></div><div class="stat-info"><span class="stat-label">Verified Workers</span><span class="stat-value loading" data-target="<?php echo (int)$stats['total_workers']; ?>">0</span><span class="stat-desc"><?php echo (int)$stats['online_workers']; ?> online now</span></div></div><div class="stat-card-v4 stat-danger"><div class="stat-icon"><i class="fas fa-concierge-bell"></i></div><div class="stat-info"><span class="stat-label">Total Bookings</span><span class="stat-value loading" data-target="<?php echo (int)$stats['completed_bookings']; ?>">0</span><span class="stat-desc"><?php echo (int)$stats['bookings_today']; ?> booked today</span></div></div><div class="stat-card-v4 stat-warning"><div class="stat-icon"><i class="fas fa-star"></i></div><div class="stat-info"><span class="stat-label">Average Review</span><span class="stat-value loading" data-target="<?php echo htmlspecialchars($stats['average_review']); ?>">0.0</span><span class="stat-desc">From <?php echo number_format((int)$stats['total_reviews']); ?> reviews</span></div></div></div>
        
        <div class="dashboard-columns-v4" style="grid-template-columns: 1fr; gap: 2rem;">
            <div class="section-card-v4 reveal" id="services"><div class="section-header-v4"><h2><i class="fas fa-star"></i> Our Services</h2><span style="color: #666; font-size: 0.9rem;"><?php echo (int)$stats['total_services']; ?> services available</span></div><?php if (empty($services)): ?><div class="empty-state" style="padding: 1.5rem;"><i class="fas fa-wrench"></i><p>Services are being updated</p><small>Please check back soon!</small></div><?php else: ?><div class="service-grid-new"><?php foreach ($services as $service): ?><a href="/dailyfix/login.php" class="service-card-new"><div><div class="service-icon-new"><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i></div><h3 class="service-title-new"><?php echo htmlspecialchars($service['name']); ?></h3><p class="service-description-new">Reliable and professional <?php echo strtolower(htmlspecialchars($service['name'])); ?> at your doorstep.</p></div><div class="service-meta-new"><span><i class="fas fa-users"></i> <?php echo htmlspecialchars($service['available_workers']); ?>+ Pros</span><?php if ($service['avg_rating'] > 0): ?><span><i class="fas fa-star" style="color: #facc15;"></i> <?php echo number_format($service['avg_rating'], 1); ?> Rating</span><?php endif; ?></div></a><?php endforeach; ?></div><?php endif; ?></div>

            <?php if (!empty($featured_workers)): ?>
            <div class="section-card-v4 reveal" id="featured-workers">
                <div class="section-header-v4">
                    <h2><i class="fas fa-award"></i> Top Rated Professionals</h2>
                    <span style="color: #666; font-size: 0.9rem;">Verified & highly experienced</span>
                </div>
                <div class="top-workers-grid">
                    <?php foreach($featured_workers as $worker): ?>
                    <div class="top-worker-card">
                        <img src="<?php echo htmlspecialchars($worker['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" 
                             alt="<?php echo htmlspecialchars($worker['full_name']); ?>" 
                             class="top-worker-avatar" /* Changed class */
                             onerror="this.src='/dailyfix/assets/images/default-avatar.png'">
                        
                        <h3 class="top-worker-name"><?php echo htmlspecialchars($worker['full_name']); ?></h3>
                        <p class="top-worker-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($worker['city'] ?: 'Location N/A'); ?></p>
                        
                        <div class="top-worker-stats"> 
                            <span><i class="fas fa-star"></i> <?php echo number_format($worker['avg_rating'], 1); ?> Rating</span>
                            <span><i class="fas fa-briefcase"></i> <?php echo (int)$worker['completed_jobs']; ?>+ Jobs</span>
                        </div>
                        
                        <?php if (!empty($worker['specializations'])): ?>
                        <div class="top-worker-specializations"> 
                            <h4>Specializes In</h4>
                            <div class="top-worker-pills">
                                <?php 
                                    $specializations = array_slice(explode(',', $worker['specializations']), 0, 3); // Limit to 3 pills
                                    foreach($specializations as $spec):
                                        if(trim($spec)): // Ensure specialization is not empty
                                ?>
                                <span class="top-worker-pill"><?php echo htmlspecialchars(trim($spec)); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                         <div class="top-worker-specializations" style="margin-bottom: 1.5rem;">
                            <h4>Specializes In</h4>
                            <p style="font-size: 0.9rem; color: #9ca3af;">No specializations listed.</p>
                         </div>
                        <?php endif; ?>
                        
                        <a href="/dailyfix/login.php" class="top-worker-profile-link">View Profile</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
             <?php else: ?>
             <div class="section-card-v4 reveal" id="featured-workers">
                 <div class="section-header-v4">
                    <h2><i class="fas fa-award"></i> Top Rated Professionals</h2>
                 </div>
                 <div class="empty-state" style="padding: 2rem;">
                     <i class="fas fa-user-tie"></i>
                     <p>Finding Top Professionals...</p>
                     <small>Check back soon to see our highest-rated workers!</small>
                 </div>
             </div>
            <?php endif; ?>

            <?php if (!empty($recent_activity) && count($recent_activity) >= 3): ?><div class="section-card-v4 reveal"><div class="section-header-v4"><h2><i class="fas fa-bolt"></i> Live Activity</h2><span style="color: #666; font-size: 0.9rem;">Recent bookings on our platform</span></div><div style="padding: 1.5rem;"><?php for($i = 0; $i < min(5, count($recent_activity)); $i++): $activity = $recent_activity[$i]; ?><div class="activity-item"><div class="activity-icon"><i class="<?php echo htmlspecialchars($activity['service_icon']); ?>"></i></div><div class="activity-details"><div class="activity-text"><strong><?php echo htmlspecialchars($activity['service_name']); ?></strong> booked in <strong><?php echo htmlspecialchars($activity['location']); ?></strong></div><div class="activity-time"><?php echo timeAgo($activity['created_at']); ?></div></div></div><?php endfor; ?></div></div><?php endif; ?>
            <?php if (!empty($reviews)): ?><div class="section-card-v4 reveal" id="testimonials"><div class="section-header-v4"><h2><i class="fas fa-comment-dots"></i> What Our Customers Say</h2><span style="color: #666; font-size: 0.9rem;"><?php echo number_format((int)$stats['total_reviews']); ?> total reviews</span></div><div class="stats-grid-v4" style="padding: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));"><?php foreach($reviews as $review): ?><div class="stat-card-v4 testimonial-card" style="flex-direction: column; align-items: flex-start; gap: 1rem;"><div style="display: flex; justify-content: space-between; width: 100%; align-items: center;"><div class="testimonial-stars" style="color: var(--warning-color);"><?php for($i = 0; $i < (int)$review['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?></div><span style="font-size: 0.75rem; color: #999;"><?php echo timeAgo($review['created_at']); ?></span></div><p style="font-style: italic; color: var(--text-color-light);">"<?php echo htmlspecialchars($review['comment']); ?>"</p><div style="display: flex; justify-content: space-between; width: 100%; align-items: center;"><strong style="color: var(--text-color);">— <?php echo htmlspecialchars($review['full_name']); ?></strong><span style="font-size: 0.8rem; color: #666;"><i class="<?php echo htmlspecialchars($review['service_icon']); ?>"></i> <?php echo htmlspecialchars($review['service_name']); ?></span></div></div><?php endforeach; ?></div></div><?php endif; ?>
            <div class="section-card-v4 reveal" id="how-it-works"><div class="section-header-v4"><h2><i class="fas fa-rocket"></i> Get Help in 3 Simple Steps</h2></div><div class="how-it-works-container"><div class="how-it-works-step"><div class="step-icon-wrapper"><i class="fas fa-mouse-pointer"></i></div><h3 class="step-title">1. Choose a Service</h3><p class="step-description">Browse our wide range of categories and select the specific task you need done.</p></div><div class="how-it-works-step"><div class="step-icon-wrapper"><i class="fas fa-calendar-check"></i></div><h3 class="step-title">2. Book a Professional</h3><p class="step-description">Pick a time that works for you and we'll match you with a top-rated, verified professional.</p></div><div class="how-it-works-step"><div class="step-icon-wrapper"><i class="fas fa-check-circle"></i></div><h3 class="step-title">3. Relax & Rate</h3><p class="step-description">Your pro arrives and completes the job. Once done, you can rate your experience.</p></div></div></div>
            <div class="cta-banner reveal"><div class="welcome-content" style="text-align: center; flex-direction: column;"><div class="welcome-text"><h2>Ready to Simplify Your Life?</h2><p>Join <?php echo number_format((int)$stats['total_customers']); ?>+ satisfied customers and <?php echo number_format((int)$stats['total_workers']); ?>+ professional workers on DailyFix today.</p><?php if ((int)$trust_metrics['bookings_this_month'] > 0): ?><p style="margin-top: 0.5rem; font-size: 0.9rem;"><i class="fas fa-fire"></i> <?php echo number_format((int)$trust_metrics['bookings_this_month']); ?> bookings made this month</p><?php endif; ?></div><a href="/dailyfix/signup.php" class="btn-primary-v4 custom"><i class="fas fa-user-plus"></i> <span>Create Your Free Account</span></a></div></div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> DailyFix. All Rights Reserved.</p>
        <div class="social-icons">
            <a href="mailto:jayrajparmar1509@gmail.com" title="Email"><i class="fas fa-envelope"></i></a><a href="https://www.linkedin.com/in/jay-parmar-106195295/" target="_blank" title="LinkedIn"><i class="fab fa-linkedin"></i></a><a href="https://x.com/jayraj1509" target="_blank" title="X (Twitter)"><i class="fab fa-x-twitter"></i></a><a href="https://github.com/Jayraj1509" target="_blank" title="GitHub"><i class="fab fa-github-alt"></i></a><a href="https://www.instagram.com/_jayrajsinh_parmar_/" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => { window.scrollY > 50 ? navbar.classList.add('scrolled') : navbar.classList.remove('scrolled'); });
            const statNumbers = document.querySelectorAll('.stat-value'); let animated = false;
            function animateStats() {
                if (animated) return;
                statNumbers.forEach(statNumber => {
                    statNumber.classList.remove('loading');
                    const target = parseFloat(statNumber.dataset.target); if (isNaN(target)) return;
                    const isFloat = target % 1 !== 0; let current = 0; const duration = 1500; // Faster animation
                    const updateCount = () => {
                        const increment = target / (duration / 16); // Calculate increment per frame
                        current += increment;
                        if (current >= target) {
                           statNumber.innerText = isFloat ? target.toFixed(1) : target.toLocaleString();
                        } else {
                           statNumber.innerText = isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString();
                           requestAnimationFrame(updateCount);
                        }
                    };
                    requestAnimationFrame(updateCount);
                });
                animated = true;
            }
            const statsSection = document.querySelector('.stats-grid-v4');
            if (statsSection) {
                const statsObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => { if (entry.isIntersecting) { animateStats(); statsObserver.unobserve(entry.target); } });
                }, { threshold: 0.1 }); // Trigger earlier
                statsObserver.observe(statsSection);
            }
            const reveals = document.querySelectorAll('.reveal');
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('active'); revealObserver.unobserve(entry.target); } });
            }, { threshold: 0.1 }); // Trigger earlier
            reveals.forEach(reveal => { revealObserver.observe(reveal); });
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    // Remove '#' from targetId to prevent adding it to URL history
                    const cleanTargetId = targetId.substring(1); 
                    const target = document.getElementById(cleanTargetId); // Use getElementById
                    
                    if (target) {
                        const navbarHeight = navbar.offsetHeight;
                        const targetPosition = target.offsetTop - navbarHeight - 20;
                        window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                        
                        // Optionally update URL hash without causing page jump (if needed for deep linking)
                        // history.pushState(null, null, `#${cleanTargetId}`); 
                    }
                });
            });
        });
    </script>
</body>
</html>