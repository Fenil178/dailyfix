<?php
include_once __DIR__ . "/encryption.php";

$role = null;
$userId = null;
$userName = 'Guest';
$profile_imagePath = null;

// Decrypt user data from cookies to establish the session
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (isset($_COOKIE['encrypted_user_id'])) {
    $userId = decrypt_id($_COOKIE['encrypted_user_id']);
}
if (isset($_COOKIE['encrypted_user_name'])) {
    $userName = decrypt_id($_COOKIE['encrypted_user_name']);
}
if (isset($_COOKIE['encrypted_profile_image'])) {
    $profile_imagePath = decrypt_id($_COOKIE['encrypted_profile_image']);
}

// If the role or user ID can't be verified, redirect to the login page
if (!$role || !$userId) {
    header("Location: /dailyfix/login.php");
    exit;
}
?>