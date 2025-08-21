<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Use your session file

// --- Security & Validation ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /dailyfix/dashboard.php?error=invalid_request");
    exit;
}

if (!isset($userId) || $role !== 'customer') {
    header("Location: /dailyfix/login.php?error=not_logged_in");
    exit;
}

$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$service_details_raw = trim($_POST['service_details'] ?? '');
$address = trim($_POST['address'] ?? '');
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';

if (!$worker_id || !$customer_id || empty($service_details_raw) || empty($address) || empty($booking_date) || empty($booking_time)) {
    header("Location: /dailyfix/customer/book_worker.php?id={$worker_id}&error=missing_fields");
    exit;
}

if ($userId != $customer_id) {
    header("Location: /dailyfix/dashboard.php?error=unauthorized");
    exit;
}

// --- Time Processing ---
try {
    // Combine date and time. It will now automatically use 'Asia/Kolkata'.
    $datetime_string = $booking_date . ' ' . $booking_time;
    $booking_datetime = new DateTime($datetime_string);
    
    // Format for PostgreSQL
    $formatted_for_db = $booking_datetime->format('Y-m-d H:i:s P');

} catch (Exception $e) {
    header("Location: /dailyfix/customer/book_worker.php?id={$worker_id}&error=invalid_date");
    exit;
}


// --- Database Operation ---
try {
    $full_service_details = "Work Details: " . $service_details_raw . "\nAddress: " . $address;

    $stmt = $conn->prepare(
        "INSERT INTO public.bookings (customer_id, worker_id, service_details, booking_time, status) 
         VALUES (?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $customer_id,
        $worker_id,
        $full_service_details,
        $formatted_for_db 
    ]);

    header("Location: /dailyfix/customer/bookings.php?success=booking_created");
    exit;

} catch (PDOException $e) {
    error_log("Booking creation failed: " . $e->getMessage());
    header("Location: /dailyfix/customer/book_worker.php?id={$worker_id}&error=database_error");
    exit;
}