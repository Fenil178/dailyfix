<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId and $role

// --- Security Checks ---

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo "Error: This endpoint only accepts POST requests.";
    exit;
}

// Ensure the user is logged in
if (!isset($userId)) {
    header("Location: /dailyfix/login.php?error=unauthorized");
    exit;
}

// --- Data Retrieval & Validation ---

$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$address_line1 = trim($_POST['address_line1'] ?? '');
$address_line2 = trim($_POST['address_line2'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$pincode = trim($_POST['pincode'] ?? '');

// Basic validation: ensure essential fields are not empty
if (empty($address_line1) || empty($city) || empty($state) || empty($pincode)) {
    header("Location: /dailyfix/profile.php?error=missing_fields#location");
    exit;
}

// --- Database Operation ---

try {
    $stmt = $conn->prepare(
        "UPDATE public.users SET 
            latitude = ?, 
            longitude = ?, 
            address_line1 = ?, 
            address_line2 = ?, 
            city = ?, 
            state = ?, 
            pincode = ? 
        WHERE id = ?"
    );
    
    $stmt->execute([
        $latitude,
        $longitude,
        $address_line1,
        $address_line2,
        $city,
        $state,
        $pincode,
        $userId
    ]);

    // Redirect on success, ensuring the user lands on the correct tab
    header("Location: /dailyfix/profile.php?success=location_updated#location");
    exit;

} catch (PDOException $e) {
    // Log the detailed error for the administrator
    error_log("Location update failed for user_id {$userId}: " . $e->getMessage());
    
    // Redirect back with a user-friendly error message
    header("Location: /dailyfix/profile.php?error=update_failed#location");
    exit;
}
?>