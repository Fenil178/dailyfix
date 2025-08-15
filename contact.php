<?php

include_once __DIR__ . "/api/encryption.php";
include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

$isLoggedIn = false;

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
  <title>Help & Contact - DailyFix</title>
  <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
  <link rel="stylesheet" href="/dailyfix/assets/css/contact.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>

<script defer src="/dailyfix/assets/js/app.js"></script>

<body class="light-mode">
<?php include_once __DIR__ . "/api/header.php"; ?>

<main class="page-content">
  <section class="contact-hero">
    <h1>Help & Contact</h1>
    <p>Need assistance? We’re here to help!</p>
  </section>

  <section class="contact-grid section-fly">
    <div class="contact-form">
      <h2>Contact Us</h2>
      <p>Send us a message and we'll get back to you as soon as possible.</p>
      <form action="#" method="post">
        <div class="form-group">
            <label for="name">Your Name</label>
            <input type="text" id="name" name="name" placeholder="Your Name" required />
        </div>
        <div class="form-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Your Email" required />
        </div>
        <div class="form-group">
            <label for="message">Your Message</label>
            <textarea id="message" name="message" placeholder="Your Message..." required></textarea>
        </div>
        <button type="submit" class="btn-main">Send Message</button>
      </form>
    </div>

    <div class="faq-section">
      <h2>Frequently Asked Questions</h2>
      <details>
        <summary>How do I book a service?</summary>
        <p>
          Simply go to the Services page, choose your service, and click "Book
          Now". Log in or sign up if prompted.
        </p>
      </details>
      <details>
        <summary>Can I cancel a booking?</summary>
        <p>
          Yes. After booking, you can cancel from your dashboard up to 1 hour
          before service time.
        </p>
      </details>
      <details>
        <summary>Are the workers verified?</summary>
        <p>
          All workers go through a thorough background and ID check before
          being listed.
        </p>
      </details>
      <details>
        <summary>How can I become a worker?</summary>
        <p>
          Sign up with the worker role on our signup page and fill in your profile details to get started.
        </p>
      </details>
    </div>
  </section>
</main>

<?php include_once __DIR__ . "/api/footer.php"; ?>

</body>
</html>