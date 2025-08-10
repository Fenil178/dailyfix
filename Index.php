<?php
/**
 * index.php
 * This file acts as a router for the DailyFix application.
 * It checks if a user is logged in and redirects them accordingly.
 */

// Include the necessary encryption functions to read the session cookies.
include_once __DIR__ . "/api/encryption.php";

// Define the list of valid roles within your application.
$allowed_roles = ['customer', 'worker'];
$role = null;

// Check if the encrypted role cookie exists.
if (isset($_COOKIE['encrypted_user_role'])) {
    // Decrypt the cookie to get the user's role.
    $decrypted_role = decrypt_id($_COOKIE['encrypted_user_role']);

    // Validate that the decrypted role is one of the allowed roles.
    if ($decrypted_role && in_array($decrypted_role, $allowed_roles)) {
        $role = $decrypted_role;
    }
}

// If the user has a valid, recognized role, they are considered logged in.
if ($role) {
    // Redirect the logged-in user to their dashboard.
    header("Location: dashboard.php");
} else {
    // If the user is not logged in or has an invalid role, redirect them to the login page.
    header("Location: login.php");
}

// It's a best practice to call exit() after a header redirect to ensure no further code is executed.
exit();
?>