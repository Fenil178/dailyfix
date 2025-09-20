<?php
// Final version of the authentication check file

include_once __DIR__ . "/../../api/encryption.php";

$isAdmin = false;
$adminName = 'Admin';

// Check if the role cookie exists and if it decrypts to 'admin'
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
    
    if ($role === 'admin') {
        $isAdmin = true;
        if (isset($_COOKIE['encrypted_user_name'])) {
            $adminName = decrypt_id($_COOKIE['encrypted_user_name']);
        }
    }
}

// If the user is not an admin, redirect them to the login page.
if (!$isAdmin) {
    header("Location: login.php?auth_failed=true");
    exit;
}
?>