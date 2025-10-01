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
    // If user is logged in, redirect to their respective dashboard
    $redirect_path = ($role === 'admin') ? '/dailyfix/admin/index.php' : '/dailyfix/dashboard.php';
    header("Location: " . $redirect_path);
    exit();
}

// --- DYNAMIC DATA FETCHING ---
// This block connects to your database to pull live stats, services, and reviews.
include_once __DIR__ . "/api/connect.php";

// Initialize arrays and default values
$stats = [
    'workers' => 0,
    'customers' => 0,
    'services' => 0,
    'completed_bookings' => 0
];
$services = [];
$reviews = [];

try {
    // Fetch statistics from the database
    $stats['workers'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'worker' AND account_status = 'active'")->fetchColumn();
    $stats['customers'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'customer' AND account_status = 'active'")->fetchColumn();
    $stats['services'] = $conn->query("SELECT COUNT(*) FROM public.services")->fetchColumn();
    $stats['completed_bookings'] = $conn->query("SELECT COUNT(*) FROM public.bookings WHERE status = 'completed'")->fetchColumn();

    // Fetch up to 6 services to display on the landing page
    $services = $conn->query("SELECT name, icon, slug FROM public.services ORDER BY id LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch up to 3 recent, 5-star reviews with customer names
    $reviews = $conn->query("
        SELECT r.rating, r.comment, u.full_name 
        FROM public.reviews r
        JOIN public.users u ON r.reviewer_id = u.id
        WHERE r.rating = 5 AND LENGTH(r.comment) > 20
        ORDER BY r.created_at DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error, but don't break the page for the user.
    // The page will render gracefully with default values.
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <style>
        /* --- 1.0 General & Root Variables --- */
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #F59E0B;
            --dark: #111827;
            --light-dark: #1F2937;
            --light: #F9FAFB;
            --white: #FFFFFF;
            --gray: #6B7280;
            --border-light: #E5E7EB;
            --border-dark: #374151;
            --gradient: linear-gradient(135deg, #4F46E5 0%, #a25ce5 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Poppins', sans-serif; line-height: 1.7; color: var(--dark); background-color: var(--white); overflow-x: hidden; }

        /* --- 2.0 Navigation Bar --- */
        nav { position: fixed; top: 0; width: 100%; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); z-index: 1000; padding: 1rem 0; border-bottom: 1px solid var(--border-light); transition: all 0.3s ease; }
        nav.scrolled { padding: 0.75rem 0; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .logo-img { width: 45px; height: auto; transition: transform 0.3s ease; }
        .logo-img:hover { transform: rotate(5deg) scale(1.05); }
        .logo-text { font-size: 1.5rem; font-weight: 700; color: var(--dark); }
        .nav-links { display: flex; gap: 2.5rem; list-style: none; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--dark); font-weight: 500; transition: color 0.3s ease; position: relative; padding: 5px 0; }
        .nav-links a:after { content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px; background: var(--primary); transition: width 0.3s ease; }
        .nav-links a:hover { color: var(--primary); }
        .nav-links a:hover:after { width: 100%; }
        .nav-cta { display: flex; gap: 1rem; }
        .btn { padding: 0.7rem 1.4rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; cursor: pointer; border: none; font-size: 0.95rem; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3); }
        .btn-outline { background: transparent; color: var(--primary); border: 2px solid var(--border-light); }
        .btn-outline:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .mobile-menu-btn { display: none; }

        /* --- 3.0 Hero Section --- */
        .hero { min-height: 100vh; display: flex; align-items: center; background-color: var(--light); background-image: url('/dailyfix/assets/images/04.jpg'); background-size: cover; background-position: center; padding: 120px 2rem 4rem; position: relative; }
        .hero-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(249, 250, 251, 0.85); backdrop-filter: blur(3px); }
        .hero-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 0.8fr; gap: 4rem; align-items: center; position: relative; z-index: 1; }
        .hero-content h1 { font-size: 3.8rem; font-weight: 800; line-height: 1.2; margin-bottom: 1.5rem; color: var(--dark); }
        .hero-content .highlight { background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-content .highlight-custom { background: var(--secondary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-content p { font-size: 1.2rem; color: var(--gray); margin-bottom: 2.5rem; max-width: 500px; }
        .hero-search-form { background: var(--white); padding: 0.5rem; border-radius: 12px; display: flex; align-items: center; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 2.5rem; max-width: 500px; }
        .hero-search-form input { border: none; outline: none; padding: 0.8rem 1rem; font-size: 1rem; flex-grow: 1; background: transparent; }
        .hero-search-form .btn-primary { box-shadow: none; }

        /* --- 4.0 Stats Section --- */
        .stats { background: var(--white); padding: 4rem 2rem; }
        .stats-container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 3rem; font-weight: 700; color: var(--primary); display: block; }
        .stat-label { color: var(--gray); font-weight: 500; }

        /* --- 5.0 Services Section --- */
        .services { padding: 6rem 2rem; background: var(--light); }
        .section-header { text-align: center; max-width: 700px; margin: 0 auto 4rem; }
        .section-header h2 { font-size: 2.8rem; font-weight: 800; margin-bottom: 1rem; }
        .section-header p { font-size: 1.1rem; color: var(--gray); }
        .services-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .service-card { background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; text-decoration: none; border: 1px solid var(--border-light); }
        .service-card:hover { transform: translateY(-10px); box-shadow: 0 15px 45px rgba(0, 0, 0, 0.1); border-color: var(--primary); }
        .service-icon { width: 60px; height: 60px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(162, 92, 229, 0.1) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary); }
        .service-card h3 { font-size: 1.4rem; margin-bottom: 0.75rem; color: var(--dark); font-weight: 600; }

        /* --- 6.0 How It Works Section --- */
        .how-it-works { padding: 6rem 2rem; background: white; }
        .steps-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; }
        .step { text-align: center; }
        .step-number { width: 70px; height: 70px; background: var(--gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; color: white; margin: 0 auto 1.5rem; box-shadow: 0 10px 30px rgba(79, 70, 229, 0.2); }
        .step h3 { font-size: 1.4rem; margin-bottom: 0.75rem; font-weight: 600; }
        .step p { color: var(--gray); }

        /* --- 7.0 Testimonials Section --- */
        .testimonials { padding: 6rem 2rem; background: var(--light); }
        .testimonials-grid { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .testimonial-card { background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05); border: 1px solid var(--border-light); }
        .testimonial-stars { color: var(--secondary); margin-bottom: 1rem; }
        .testimonial-card p { font-style: italic; color: var(--gray); margin-bottom: 1.5rem; }
        .testimonial-author { display: flex; align-items: center; gap: 1rem; }
        .author-name { font-weight: 600; color: var(--dark); }

        /* --- 8.0 CTA Section --- */
        .cta { padding: 6rem 2rem; background: var(--gradient); color: white; text-align: center; }
        .cta-content { max-width: 800px; margin: 0 auto; }
        .cta h2 { font-size: 2.8rem; margin-bottom: 1.5rem; font-weight: 800; }
        .cta p { font-size: 1.2rem; margin-bottom: 2.5rem; opacity: 0.9; }
        .cta .btn { background: white; color: var(--primary); font-size: 1.1rem; padding: 1rem 2.5rem; }
        .cta .btn:hover { background: var(--light); transform: translateY(-2px) scale(1.05); }

        /* --- 9.0 Footer --- */
        footer { background: var(--dark); color: white; padding: 4rem 2rem 2rem; }
        .footer-container { max-width: 1200px; margin: 0 auto; text-align: center; }
        .footer-bottom { padding-top: 2rem; margin-top: 2rem; border-top: 1px solid var(--border-dark); color: var(--gray); }
        .social-links { display: flex; justify-content: center; gap: 1.5rem; margin-top: 1rem; }
        .social-links a { color: var(--gray); text-decoration: none; font-size: 1.5rem; transition: color 0.3s ease, transform 0.3s ease; }
        .social-links a:hover { color: white; transform: translateY(-3px); }
        .custom-css { font-size: 0.9rem; text-align: center; }

        /* --- 10.0 Animations & Responsive --- */
        .animate-on-scroll { opacity: 0; transform: translateY(30px); transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        .animate-on-scroll.visible { opacity: 1; transform: translateY(0); }
        
        @media (max-width: 968px) {
            .nav-links { display: none; }
            .mobile-menu-btn { display: block; background: none; border: none; font-size: 1.5rem; color: var(--dark); cursor: pointer; }
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-content p, .hero-search-form { max-width: 100%; margin-left: auto; margin-right: auto; }
            .hero-image { display: none; }
            .hero-content h1 { font-size: 3rem; }
            .cta h2 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <div class="nav-container">
            <a href="home.php" class="logo-section">
                <img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo" class="logo-img">
                <span class="logo-text"></span>
            </a>
            <ul class="nav-links">
                <li><a href="#services">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
            </ul>
            <div class="nav-cta">
                <a href="/dailyfix/login.php" class="btn btn-outline">Login</a>
                <a href="/dailyfix/signup.php" class="btn btn-primary">Sign Up</a>
            </div>
            <button class="mobile-menu-btn" aria-label="Menu"><i class="fas fa-bars"></i></button>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-container">
                <div class="hero-content animate-on-scroll">
                    <h1> <span class="highlight-custom">DailyFix</span> Your <span class="highlight">Trusted Partner</span> for Home Services</h1>
                    <p>From plumbing leaks to a sparkling clean home, connect with verified local professionals in minutes.</p>
                    <form class="hero-search-form" action="/dailyfix/login.php" method="get">
                        <input type="text" placeholder="What service do you need today?" readonly>
                        <button type="submit" class="btn btn-primary">Find Help</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="stats animate-on-scroll">
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['customers']; ?>">0</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['workers']; ?>">0</span>
                    <span class="stat-label">Verified Workers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['services']; ?>">0</span>
                    <span class="stat-label">Service Categories</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['completed_bookings']; ?>">0</span>
                    <span class="stat-label">Jobs Completed</span>
                </div>
            </div>
        </section>

        <section class="services" id="services">
            <div class="section-header animate-on-scroll">
                <h2>Our Most Popular Services</h2>
                <p>Find reliable help from our wide range of professional services tailored to meet your needs.</p>
            </div>
            <div class="services-grid">
                <?php if (empty($services)): ?>
                    <p style="text-align: center; grid-column: 1 / -1;">Services are being updated. Please check back soon!</p>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <a href="/dailyfix/login.php" class="service-card animate-on-scroll">
                            <div class="service-icon"><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i></div>
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="how-it-works" id="how-it-works">
            <div class="section-header animate-on-scroll">
                <h2>Simple, Fast, and Reliable</h2>
                <p>Getting the help you need is just three simple steps away.</p>
            </div>
            <div class="steps-container">
                <div class="step animate-on-scroll">
                    <div class="step-number">1</div>
                    <h3>Choose a Service</h3>
                    <p>Browse our diverse categories and select the specific task you need help with.</p>
                </div>
                <div class="step animate-on-scroll">
                    <div class="step-number">2</div>
                    <h3>Book a Professional</h3>
                    <p>Pick a verified and reviewed professional that fits your schedule and budget.</p>
                </div>
                <div class="step animate-on-scroll">
                    <div class="step-number">3</div>
                    <h3>Get It Done</h3>
                    <p>Relax as our expert completes the job to your utmost satisfaction, guaranteed.</p>
                </div>
            </div>
        </section>
        
        <?php if (!empty($reviews)): ?>
        <section class="testimonials" id="testimonials">
            <div class="section-header animate-on-scroll">
                <h2>What Our Customers Say</h2>
                <p>We are proud to have earned the trust of our community. Here's what they think.</p>
            </div>
            <div class="testimonials-grid">
                <?php foreach($reviews as $review): ?>
                <div class="testimonial-card animate-on-scroll">
                    <div class="testimonial-stars">
                        <?php for($i = 0; $i < $review['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    </div>
                    <p>"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                    <div class="testimonial-author">
                        <div class="author-name">- <?php echo htmlspecialchars($review['full_name']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="cta">
            <div class="cta-content animate-on-scroll">
                <h2>Ready to Simplify Your Life?</h2>
                <p>Join thousands of satisfied customers and professional workers on DailyFix today. Your next service is just a click away.</p>
                <a href="/dailyfix/signup.php" class="btn">Create Your Free Account</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-container">
            <a href="home.php" class="logo-section" style="justify-content: center;">
                <img src="/dailyfix/assets/images/logo.png" alt="DailyFix Logo" class="logo-img">
                <span class="logo-text" style="color: var(--white);"></span>
            </a>
            <div class="social-links">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="custom-css">&copy; <?php echo date("Y"); ?> DailyFix. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Intersection Observer for animations
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            animatedElements.forEach(el => observer.observe(el));

            // Stat counter animation
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(statNumber => {
                const target = parseInt(statNumber.dataset.target, 10);
                let current = 0;
                const increment = Math.max(1, Math.ceil(target / 100)); // Animate in ~100 steps
                const updateCount = () => {
                    if (current < target) {
                        current += increment;
                        if (current > target) current = target;
                        statNumber.innerText = Math.floor(current);
                        requestAnimationFrame(updateCount);
                    } else {
                        statNumber.innerText = target;
                    }
                };
                // Use observer to start animation only when visible
                new IntersectionObserver((entries, obs) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCount();
                            obs.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 }).observe(statNumber);
            });
        });
    </script>
</body>
</html>