<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

// --- Security & Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($userId) || $role !== 'customer') {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in or not a customer.']);
    exit;
}

$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$sub_service_name = trim($_POST['sub_service_name'] ?? '');
$service_item_name = trim($_POST['service_item_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';

if (!$worker_id || !$customer_id || empty($sub_service_name) || empty($service_item_name) || empty($address) || empty($booking_date) || empty($booking_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

if ($userId != $customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}

// --- Time Processing ---
try {
    $datetime_string = $booking_date . ' ' . $booking_time;
    // Set the timezone to IST first to interpret the user's input correctly
    $timezone = new DateTimeZone('Asia/Kolkata');
    $booking_datetime = new DateTime($datetime_string, $timezone);
    
    // Now convert it to UTC for storage
    $booking_datetime->setTimezone(new DateTimeZone('UTC'));
    
    // Format for storage without offset, as the database column should be `timestamp without time zone` or similar
    $formatted_for_db = $booking_datetime->format('Y-m-d H:i:s');
} catch (Exception $e) {
    error_log("Booking time processing error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Invalid date or time.']);
    exit;
}

// --- Database Operation ---
try {
    // Construct the service details string from the passed parameters
    $full_service_details = "Service: {$sub_service_name}\nItem: {$service_item_name}\nAddress: {$address}";

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

    echo json_encode(['status' => 'success']);
    exit;

} catch (PDOException $e) {
    error_log("Booking creation failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}
?>