<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php"; // This should start the session and provide $userId

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /dailyfix/dashboard.php?error=invalid_request");
    exit;
}

// Ensure the user is logged in (header.php provides $userId)
if (!isset($userId) || !$userId) {
    header("Location: /dailyfix/login.php?error=not_logged_in");
    exit;
}

// --- Data Validation ---
$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$service_details_raw = trim($_POST['service_details'] ?? '');
$address = trim($_POST['address'] ?? '');
$booking_time = $_POST['booking_time'] ?? '';

// Check if all required fields are present and valid
if (!$worker_id || !$customer_id || empty($service_details_raw) || empty($address) || empty($booking_time)) {
    header("Location: /dailyfix/customer/book_worker.php?id={$worker_id}&error=missing_fields");
    exit;
}

// Security check: Ensure the logged-in user is the one creating the booking
if ($userId != $customer_id) {
    header("Location: /dailyfix/dashboard.php?error=unauthorized");
    exit;
}

// --- Database Operation ---
try {
    // Combine the service description and address into one field for the database
    $full_service_details = "Work Details: " . $service_details_raw . "\nAddress: " . $address;

    $stmt = $conn->prepare(
        "INSERT INTO public.bookings (customer_id, worker_id, service_details, booking_time, status) 
         VALUES (?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $customer_id,
        $worker_id,
        $full_service_details,
        $booking_time
    ]);

    // Redirect to the dashboard with a success message
    header("Location: /dailyfix/dashboard.php?success=booking_created");
    exit;

} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Booking creation failed: " . $e->getMessage());
    
    // Redirect back with a generic error message
    header("Location: /dailyfix/customer/book_worker.php?id={$worker_id}&error=database_error");
    exit;
}