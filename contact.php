<?php

include_once __DIR__ . "/api/encryption.php";
include_once __DIR__ . "/api/connect.php";
// The api/header.php include has been removed from the top, to be placed in the body
// include_once __DIR__ . "/api/header.php";

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

  /* Page-specific skeleton layout for contact.php */
  .skeleton-contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
  }
  .skeleton-panel {
    padding: 1.5rem;
    background-color: var(--background-color-card, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
  }
  .skeleton-title { height: 32px; width: 50%; margin-bottom: 2rem; }
  .skeleton-label { height: 14px; width: 100px; margin-bottom: 0.5rem; }
  .skeleton-input { height: 40px; width: 100%; margin-bottom: 1.5rem; }
  .skeleton-textarea { height: 120px; width: 100%; margin-bottom: 1.5rem; }
  .skeleton-button { height: 45px; width: 100%; }
  .skeleton-line { height: 16px; margin-bottom: 1rem; border-radius: 4px; }
  .skeleton-faq-item { height: 50px; width: 100%; margin-bottom: 1rem; }
  
  @media (max-width: 900px) {
    .skeleton-contact-grid { grid-template-columns: 1fr; }
  }
</style>
</head>

<script defer src="/dailyfix/assets/js/app.js"></script>

<body class="light-mode">
<?php include_once __DIR__ . "/api/header.php"; ?>

<div class="skeleton-loader" id="page-loader">
  <div class="skeleton-container">
    <div class="skeleton-contact-grid">
      <div class="skeleton-panel">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-input"></div>
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-input"></div>
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-textarea"></div>
        <div class="skeleton skeleton-button"></div>
      </div>
      <div class="skeleton-panel">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-faq-item"></div>
        <div class.skeleton skeleton-faq-item"></div>
        <div class.skeleton skeleton-faq-item"></div>
        <div class.skeleton skeleton-faq-item"></div>
      </div>
    </div>
  </div>
</div>
<main class="page-content">
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