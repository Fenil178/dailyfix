<?php
// This block checks if the user is logged in and redirects to the login page if not.
include_once __DIR__ . "/../api/encryption.php"; // Path to encryption file

$isLoggedIn = false;
$role = null; // Initialize role variable

// Check for user cookies
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $role = decrypt_id($_COOKIE['encrypted_user_role']);

    // If the decrypted values are valid, the user is considered logged in
    if ($userId && $role) {
        $isLoggedIn = true;
    }
}

// If the user is NOT logged in, redirect them to the login page and stop the script
if (!$isLoggedIn) {
    header("Location: /dailyfix/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us - DailyFix</title>
  <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/about.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<script defer src="/dailyfix/assets/js/app.js"></script>

<body class="light-mode">
<?php include_once __DIR__ . "/../api/header.php"; ?>

<main class="page-content">
  <section class="about-hero">
    <h1>About DailyFix</h1>
    <p>Your Everyday Expert – Built for Every Home</p>
  </section>
  
  <section class="about-section section-fly">
    <div class="content-panel">
      <h2>Who We Are</h2>
      <p>
        DailyFix is your everyday solution for household services — a modern,
        local-first platform connecting people with verified workers for daily
        tasks. Whether it's a last-minute plumbing fix or a long-term cook, we
        match you with trusted professionals in your neighborhood.
      </p>
    </div>
    <div class="content-panel">
      <h2>What We Do</h2>
      <p>
        We simplify the process of finding help for your home. Our digital
        platform allows you to browse, compare, and book local workers based on
        ratings, availability, and skill — all from your phone or desktop. With
        DailyFix, getting help is just a few clicks away.
      </p>
    </div>
  </section>

  <section class="mission-vision section-fly">
    <div class="card-mission-vision">
      <i class="fas fa-bullseye"></i>
      <h3>Our Mission</h3>
      <p>
        To empower households with easy access to reliable services, and uplift
        skilled local workers by opening new income opportunities — all while
        fostering safety, trust, and simplicity.
      </p>
    </div>
    <div class="card-mission-vision">
      <i class="fas fa-eye"></i>
      <h3>Our Vision</h3>
      <p>
        We envision a future where household help is seamless, secure, and
        accessible to every family, everywhere. DailyFix stands for dignity in
        work and comfort at home.
      </p>
    </div>
  </section>

  <section class="team-section section-fly">
    <h2 class="team-title">Meet Our Team</h2>
    <div class="team-list">
      <div class="team-member">
        <img
          src="https://img.icons8.com/color/96/000000/manager--v1.png"
          alt="Meet Patel" />
        <h3>Meet Patel</h3>
        <p>Backend Developer & Database Manager</p>
      </div>
      <div class="team-member">
        <img
          src="https://img.icons8.com/color/96/000000/user-male-circle--v2.png"
          alt="Fenil Pastagia" />
        <h3>Fenil Pastagia</h3>
        <p>Backend Developer</p>
      </div>
      <div class="team-member">
        <img
          src="https://img.icons8.com/color/96/000000/user-male-circle--v1.png"
          alt="Jay Parmar" />
        <h3>Jay Parmar</h3>
        <p>Frontend Developer</p>
      </div>
    </div>
  </section>

  <section class="about-footer section-fly">
    <div class="cta-box">
    <?php if ($isLoggedIn && $role === 'customer'): ?>
        <h2>Ready for a new service?</h2>
        <p>Explore our range of services and book a skilled worker today.</p>
        <div class="cta-buttons">
            <a class="btn-main" href="/dailyfix/customer/services.php">Explore Services</a>
        </div>
    <?php elseif ($isLoggedIn && $role === 'worker'): ?>
        <h2>You're all set!</h2>
        <p>Check your dashboard for new job requests and manage your profile.</p>
        <div class="cta-buttons">
            <a class="btn-main" href="/dailyfix/dashboard.php">Go to Dashboard</a>
        </div>
    <?php else: ?>
        <h2>Ready to experience the convenience?</h2>
        <p>Join thousands of satisfied users. Log in or sign up today and discover how we can make your daily life easier.</p>
        <div class="cta-buttons">
            <a class="btn-main" href="/dailyfix/login.php">Log In</a>
            <a class="btn-secondary" href="/dailyfix/signup.php">Sign Up</a>
        </div>
    <?php endif; ?>
    </div>
  </section>
</main>

<?php include_once __DIR__ . "/../api/footer.php"; ?>

</body>

</html>