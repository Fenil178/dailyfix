<?php
// File: /admin/logout.php
// ---
// Securely logs out the admin by destroying the session
// and clearing any associated cookies.

// 1. Start the session to access session data.
session_start();

// 2. Unset all of the session variables.
$_SESSION = [];

// 3. Destroy the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session on the server.
session_destroy();

// 5. Clear any custom application cookies (if they exist).
// This part is from your original code and is good practice.
setcookie("encrypted_user_id", "", time() - 3600, "/");
setcookie("encrypted_user_role", "", time() - 3600, "/");
setcookie("encrypted_user_name", "", time() - 3600, "/");

// 6. Redirect to the login page.
header("Location: /dailyfix/admin/login.php");
exit();
?>