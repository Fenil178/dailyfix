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
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    
    <style>
        /* Existing base styles remain the same... */
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%); }
        .navbar { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.9); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .navbar.scrolled { box-shadow: 0 8px 40px rgba(0, 0, 0, 0.1); background: rgba(255, 255, 255, 0.95); }
        .navbar .guest-actions { display: flex; align-items: center; gap: 15px; }
        .navbar .guest-actions .btn-login, .navbar .guest-actions .btn-signup { text-decoration: none; padding: 8px 20px; border-radius: 20px; font-weight: 500; transition: all 0.3s ease; border: 1px solid #ddd; }
        .navbar .guest-actions .btn-login { background-color: transparent; color: #333; }
        .navbar .guest-actions .btn-login:hover { background-color: #2563eb; color: white; }
        .navbar .guest-actions .btn-signup { color: #fff; background-color: #2563eb; border-color: #2563eb; }
        .navbar .guest-actions .btn-signup:hover { opacity: 0.9; }
        .welcome-banner { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%); background-size: 200% 200%; animation: gradientShift 8s ease infinite; position: relative; overflow: hidden; margin-top: 2rem; }
        .welcome-banner::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: float 20s ease-in-out infinite; }
        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        @keyframes float { 0%, 100% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(-30px, -30px) rotate(180deg); } }
        .welcome-content { position: relative; z-index: 1; }
        .welcome-text h1 { animation: fadeInUp 0.8s ease; text-shadow: 2px 4px 8px rgba(0, 0, 0, 0.2); }
        .welcome-text p { animation: fadeInUp 1s ease; text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.15); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .stat-card-v4 { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        .stat-card-v4::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s ease; }
        .stat-card-v4:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); }
        .stat-card-v4:hover::before { left: 100%; }
        .stat-icon { transition: all 0.3s ease; }
        .stat-card-v4:hover .stat-icon { transform: scale(1.15) rotate(5deg); }
        .stat-value { font-weight: 800; }
        .section-card-v4 { transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid rgba(255, 255, 255, 0.5); }
        .section-card-v4:hover { box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12); transform: translateY(-4px); }
        .how-it-works-container { display: flex; justify-content: space-between; align-items: flex-start; padding: 2.5rem 1.5rem; gap: 2rem; position: relative; }
        .how-it-works-step { flex: 1; text-align: center; position: relative; padding: 0 1rem; z-index: 1; }
        .how-it-works-step:not(:last-child)::after { content: ''; position: absolute; top: 40px; left: 50%; width: 100%; height: 2px; background: linear-gradient(to right, #dbeafe, #60a5fa, #dbeafe); z-index: -1; }
        .step-icon-wrapper { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #60a5fa, #2563eb); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem; box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3); transition: all 0.3s ease; }
        .how-it-works-step:hover .step-icon-wrapper { transform: scale(1.1) rotate(10deg); box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4); }
        .step-title { font-size: 1.25rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.5rem; }
        .step-description { color: var(--text-color-light); font-size: 0.95rem; line-height: 1.6; }
        .testimonial-card { transition: all 0.3s ease; position: relative; }
        .testimonial-card::before { content: '"'; position: absolute; top: -10px; left: 10px; font-size: 4rem; color: rgba(102, 126, 234, 0.1); font-family: Georgia, serif; line-height: 1; }
        .testimonial-stars { animation: sparkle 2s ease-in-out infinite; }
        @keyframes sparkle { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .trust-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 20px; color: #f59e0b; font-weight: 600; font-size: 0.9rem; }
        .btn-primary-v4 { transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); position: relative; overflow: hidden; }
        .btn-primary-v4::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%; background: rgba(255, 255, 255, 0.3); transform: translate(-50%, -50%); transition: width 0.6s ease, height 0.6s ease; }
        .btn-primary-v4:hover::before { width: 300px; height: 300px; }
        .btn-primary-v4:hover { transform: scale(1.05); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3); }
        .btn-primary-v4 span { position: relative; z-index: 1; }
        .reveal { opacity: 0; transform: translateY(40px); transition: all 0.8s ease; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .cta-banner { background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%); background-size: 200% 200%; animation: gradientShift 6s ease infinite; position: relative; overflow: hidden; border-radius: 1rem; }
        .cta-banner::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); animation: rotate 30s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .custom { margin-bottom: 1.5rem; }
        .stat-value.loading { animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .nav-links a { transition: all 0.3s ease; position: relative; color: #333; text-decoration: none; }
        .nav-links a:hover { color: #2563eb; }
        .nav-links a::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: currentColor; transition: width 0.3s ease; }
        .nav-links a:hover::after { width: 100%; }
        footer { background-color: #ffffff; color: #333; padding: 0.5rem 0; text-align: center; border-top: 1px solid #e0e0e0; transition: background-color 0.3s ease, color 0.3s ease; }
        .social-icons { margin-top: 1rem; }
        .social-icons a { color: #333; margin: 0 0.8rem; font-size: 1.5rem; transition: color 0.3s ease; }
        .social-icons a:hover { color: var(--primary-color); }
        .stat-card-v4:nth-child(1) { animation-delay: 0.1s; } .stat-card-v4:nth-child(2) { animation-delay: 0.2s; }
        .stat-card-v4:nth-child(3) { animation-delay: 0.3s; } .stat-card-v4:nth-child(4) { animation-delay: 0.4s; }
        .empty-state { text-align: center; padding: 2rem; color: #999; animation: fadeIn 1s ease; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; } .empty-state p { font-size: 1.2rem; font-weight: 500; }
        .empty-state small { font-size: 0.9rem; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) { .how-it-works-container { flex-direction: column; align-items: center; } .how-it-works-step:not(:last-child)::after { width: 2px; height: 100%; left: calc(50% - 1px); top: 50%; background: linear-gradient(to bottom, #dbeafe, #60a5fa, #dbeafe); } }
        .service-grid-new { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; padding: 1.5rem; }
        .service-card-new { background: #fff; border-radius: 12px; padding: 1.5rem; text-align: center; border: 1px solid #e5e7eb; transition: all 0.3s ease; text-decoration: none; color: inherit; display: flex; flex-direction: column; justify-content: space-between; }
        .service-card-new:hover { transform: translateY(-8px); box-shadow: 0 10px 30px -5px rgba(37, 99, 235, 0.2); border-color: #60a5fa; }
        .service-icon-new { width: 60px; height: 60px; margin: 0 auto 1.25rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #e0f2fe, #dbeafe); color: #2563eb; font-size: 1.75rem; }
        .service-title-new { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .service-description-new { font-size: 0.9rem; color: #6b7280; line-height: 1.5; flex-grow: 1; margin-bottom: 1rem; }
        .service-meta-new { display: flex; justify-content: space-around; font-size: 0.85rem; color: #4b5563; border-top: 1px solid #f3f4f6; padding-top: 1rem; margin-top: auto; }
        .service-meta-new span { display: flex; align-items: center; gap: 0.3rem; }

        /* =========== NEW v3 PROFESSIONALS UI =========== */
        .top-workers-grid { /* Changed class name */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Slightly wider minmax */
            gap: 2rem; /* Increased gap */
            padding: 2rem; /* Increased padding */
        }
        .top-worker-card { /* Changed class name */
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 2rem; /* Increased padding */
            text-align: center;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Subtle base shadow */
        }
        .top-worker-card:hover {
            transform: translateY(-8px); /* Less dramatic lift */
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1); /* Softer hover shadow */
            border-color: #bfdbfe; /* Lighter blue border */
        }
        .top-worker-avatar { /* Changed class name */
            width: 100px; /* Larger avatar */
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.25rem; /* Increased bottom margin */
            border: 5px solid #fff; /* White border */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); /* Shadow for depth */
        }
        .top-worker-name { /* Changed class name */
            font-size: 1.35rem; /* Larger name */
            font-weight: 700;
            margin: 0;
            color: #1f2937; /* Darker text */
        }
        .top-worker-location { /* Changed class name */
            font-size: 0.95rem; /* Slightly larger location */
            color: #6b7280;
            margin-bottom: 1.5rem; /* Increased spacing */
        }
        .top-worker-stats { /* Changed class name */
            display: flex;
            justify-content: space-evenly; /* Evenly space items */
            gap: 1rem;
            padding: 1rem 0;
            margin-bottom: 1.5rem; /* Increased spacing */
            border-top: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.9rem;
            color: #4b5563;
        }
        .top-worker-stats span {
             display: flex;
             align-items: center;
             gap: 0.4rem; /* More gap for icons */
        }
        .top-worker-stats .fa-star {
            color: #f59e0b; /* Amber color for star */
        }
         .top-worker-stats .fa-briefcase {
            color: #60a5fa; /* Blue color for briefcase */
        }
        .top-worker-specializations { /* Changed class name */
            flex-grow: 1; /* Pushes button to bottom */
            margin-bottom: 1.5rem;
        }
        .top-worker-specializations h4 {
            font-size: 0.8rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        .top-worker-pills { /* Changed class name */
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem; /* Slightly more gap */
        }
        .top-worker-pill { /* Changed class name */
            background-color: #f0f9ff; /* Lighter blue background */
            color: #0284c7; /* Sky blue text */
            padding: 0.3rem 0.8rem; /* Adjusted padding */
            border-radius: 16px; /* Pill shape */
            font-size: 0.8rem;
            font-weight: 500;
        }
        .top-worker-profile-link { /* Changed class name */
            display: inline-block; /* Allows width setting */
            width: auto; /* Auto width based on content */
            padding: 0.7rem 1.5rem; /* Adjusted padding */
            background-color: #3b82f6; /* Brighter blue */
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: auto; /* Pushes to bottom */
            transition: all 0.25s ease-in-out;
            border: 1px solid transparent;
        }
        .top-worker-profile-link:hover {
            background-color: #2563eb; /* Darker blue */
            transform: scale(1.03); /* Subtle scale */
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

    </style>
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
            <div class="section-card-v4 reveal" id="services"><div class="section-header-v4"><h2><i class="fas fa-star"></i> Our Most Popular Services</h2><span style="color: #666; font-size: 0.9rem;"><?php echo (int)$stats['total_services']; ?> services available</span></div><?php if (empty($services)): ?><div class="empty-state" style="padding: 1.5rem;"><i class="fas fa-wrench"></i><p>Services are being updated</p><small>Please check back soon!</small></div><?php else: ?><div class="service-grid-new"><?php foreach ($services as $service): ?><a href="/dailyfix/login.php" class="service-card-new"><div><div class="service-icon-new"><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i></div><h3 class="service-title-new"><?php echo htmlspecialchars($service['name']); ?></h3><p class="service-description-new">Reliable and professional <?php echo strtolower(htmlspecialchars($service['name'])); ?> at your doorstep.</p></div><div class="service-meta-new"><span><i class="fas fa-users"></i> <?php echo htmlspecialchars($service['available_workers']); ?>+ Pros</span><?php if ($service['avg_rating'] > 0): ?><span><i class="fas fa-star" style="color: #facc15;"></i> <?php echo number_format($service['avg_rating'], 1); ?> Rating</span><?php endif; ?></div></a><?php endforeach; ?></div><?php endif; ?></div>

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