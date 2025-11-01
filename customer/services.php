<?php
// This block checks if the user is logged in and redirects to the login page if not.
include_once __DIR__ . "/../api/encryption.php";
include_once __DIR__ . "/../api/connect.php"; // Include your database connection

$isLoggedIn = false;

// Check for user cookies
if (isset($_COOKIE['encrypted_user_id']) && isset($_COOKIE['encrypted_user_role'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
    $role = decrypt_id($_COOKIE['encrypted_user_role']);

    if ($userId && $role) {
        $isLoggedIn = true;
    }
}

if (!$isLoggedIn) {
    header("Location: /dailyfix/login.php");
    exit;
}

// --- Fetch Services Data from Database ---
$mainServices = [];
$subServices = [];

try {
    // Fetch main services
    $stmt = $conn->prepare("SELECT id, name, icon, slug FROM public.services ORDER BY id");
    $stmt->execute();
    $mainServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all sub-services and organize them by service_id
    $stmt = $conn->prepare("SELECT service_id, name, icon, slug FROM public.sub_services ORDER BY name");
    $stmt->execute();
    $allSubServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allSubServices as $sub) {
      $subServices[$sub['service_id']][] = [
          'name' => $sub['name'],
          'icon' => $sub['icon'],
          'slug' => $sub['slug']
      ];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $mainServices = [];
}

// // In a real app, this might come from the database.
$serviceDescriptions = [
    "Cleaning Services" => "Spotless cleaning for your home and office.",
    "Home Services" => "Plumbing, electrical, and carpentry experts.",
    "Vehicle Services" => "Keep your car in pristine condition.",
    "Cooling Services" => "AC repair, service, and installation.",
    "Refrigerator Services" => "Fast and reliable fridge repairs.",
    "Washing Machine Services" => "Fixing all models and brands.",
    "Water Purifier Services" => "Ensuring you get clean, safe water.",
    "Elevator Services" => "Maintenance and repair for elevators."
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DailyFix - Services</title>
  <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/header.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/services.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
  <style>
  /* Common skeleton styles (loader, shimmer, dark-mode) */
  .skeleton-loader {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background-color: var(--background-color-body, #f9f9f9);
    z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
  }
  .skeleton-loader.hidden { opacity: 0; pointer-events: none; }
  .skeleton-container {
    max-width: 1100px; width: 100%;
    padding: 0 1rem;
    margin: 1rem auto;
    margin-top: 80px; /* Adjust to match your header's height */
  }
  @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
  .skeleton {
    animation: shimmer 1.5s infinite linear;
    background: linear-gradient(to right, 
      var(--hover-color, #f0f0f0) 8%, 
      var(--border-color, #e2e8f0) 18%, 
      var(--hover-color, #f0f0f0) 33%);
    background-size: 800px 104px; border-radius: 6px;
  }

  /* Page-specific skeleton layout for services.php */
  .skeleton-hero {
    height: 180px;
    width: 100%;
    margin-bottom: 2rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 1rem;
  }
  .skeleton-hero-title { height: 38px; width: 300px; margin-bottom: 1rem; }
  .skeleton-hero-p { height: 16px; width: 400px; }
  
  .skeleton-services-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
  }
  .skeleton-service-card {
    height: 160px;
    border: 1px solid var(--border-color, #e2e8f0);
    background-color: var(--background-color-card, #fff);
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .skeleton-card-icon { height: 40px; width: 40px; border-radius: 50%; margin-bottom: 1rem; }
  .skeleton-card-title { height: 20px; width: 80%; margin-bottom: 0.5rem; }
  .skeleton-card-p { height: 14px; width: 100%; }

  @media (max-width: 900px) { .skeleton-services-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 600px) { .skeleton-services-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
</head>

  <style>
    /* Add delays to each card for the stagger effect */
    <?php for ($i = 1; $i <= 12; $i++): ?>
      .services-grid.visible .service-card:nth-child(<?php echo $i; ?>) {
        animation-delay: <?php echo $i * 0.07; ?>s;
      }
    <?php endfor; ?>
  </style>

<script defer src="/dailyfix/assets/js/app.js"></script>
<script defer>
  // Pass the PHP data to JavaScript
  const subServicesData = <?php echo json_encode($subServices); ?>;
</script>
<script defer src="/dailyfix/assets/js/services.js"></script>

<body class="light-mode">
<?php include_once __DIR__ . "/../api/header.php"; ?>

<div class="skeleton-loader" id="page-loader">
  <div class="skeleton-container" style="margin-top: 0; padding: 0; max-width: none;">
    <div class.skeleton skeleton-hero">
        <div class="skeleton skeleton-hero-title"></div>
        <div class="skeleton skeleton-hero-p"></div>
    </div>
    
    <div class="skeleton-container" style="margin-top: 2rem;">
      <div class="skeleton-services-grid">
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
        <div class="skeleton-service-card">
          <div class="skeleton skeleton-card-icon"></div>
          <div class="skeleton skeleton-card-title"></div>
          <div class="skeleton skeleton-card-p"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<main class="page-content">
  <section class="services-hero">
    <h1>Our Services</h1>
    <p>Find reliable and professional help for all your daily needs.</p>
  </section>

  <section class="main-services-container section-fly">
    <div class="services-grid">
      <?php foreach ($mainServices as $service): ?>
        <div class="service-card" data-service-id="<?php echo htmlspecialchars($service['id']); ?>">
          <i class="<?php echo htmlspecialchars($service['icon']); ?>"></i>
          <h3><?php echo htmlspecialchars($service['name']); ?></h3>
          <p><?php echo htmlspecialchars($serviceDescriptions[$service['name']] ?? 'Your Apparel is our responsiblity.'); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sub-services-container section-fly hidden">
    <a href="#" id="back-to-main" class="back-link"><i class="fas fa-arrow-left"></i> Back to Main Services</a>
    <h2><span id="sub-service-title"></span></h2>
    <div class="sub-services-grid" id="sub-services-grid">
      </div>
  </section>
</main>

<?php include_once __DIR__ . "/../api/footer.php"; ?>

</body>
</html>