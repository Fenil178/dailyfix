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
    $stmt = $conn->prepare("SELECT id, name, icon, slug FROM dailyfix.services ORDER BY id");
    $stmt->execute();
    $mainServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all sub-services and organize them by service_id
    $stmt = $conn->prepare("SELECT service_id, name, icon, link FROM dailyfix.sub_services ORDER BY name");
    $stmt->execute();
    $allSubServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allSubServices as $sub) {
        $subServices[$sub['service_id']][] = [
            'name' => $sub['name'],
            'icon' => $sub['icon'],
            'link' => $sub['link']
        ];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $mainServices = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DailyFix - Services</title>
  <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/services.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<script defer src="/dailyfix/assets/js/app.js"></script>
<script defer>
  // Pass the PHP data to JavaScript
  const subServicesData = <?php echo json_encode($subServices); ?>;
</script>
<script defer src="/dailyfix/assets/js/services.js"></script>

<body class="light-mode">
<?php include_once __DIR__ . "/../api/header.php"; ?>

<main class="page-content">
  <section class="services-hero">
    <h1>Our Services</h1>
    <p>Select from our range of expert household help</p>
  </section>

  <section class="main-services-container section-fly">
    <div class="services-grid">
      <?php foreach ($mainServices as $service): ?>
        <div class="service-card" data-service-id="<?php echo htmlspecialchars($service['id']); ?>">
          <i class="<?php echo htmlspecialchars($service['icon']); ?>"></i>
          <h3><?php echo htmlspecialchars($service['name']); ?></h3>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sub-services-container section-fly hidden">
    <a href="#" id="back-to-main" class="back-link"><i class="fas fa-arrow-left"></i> Back to Main Services</a>
    <h2>Sub-services for <span id="sub-service-title"></span></h2>
    <div class="sub-services-grid" id="sub-services-grid">
      </div>
  </section>
</main>

<?php include_once __DIR__ . "/../api/footer.php"; ?>

</body>
</html>