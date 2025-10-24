<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

// --- Security & Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($userId) || $role !== 'customer') {
    http_response_code(403); // Forbidden
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
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT); // Original item price

// NEW: Get potential offer details
$applied_offer_id = filter_input(INPUT_POST, 'applied_offer_id', FILTER_VALIDATE_INT);
$discount_amount = filter_input(INPUT_POST, 'discount_amount', FILTER_VALIDATE_FLOAT);

// Basic validation
if (!$worker_id || !$customer_id || empty($sub_service_name) || empty($service_item_name) || empty($address) || empty($booking_date) || empty($booking_time) || $price === null || $price < 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid required fields (price must be >= 0).']);
    exit;
}

if ($userId != $customer_id) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}

// Validate discount amount if offer ID is present
if ($applied_offer_id && ($discount_amount === null || $discount_amount < 0 || $discount_amount > $price)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid discount amount provided with offer ID.']);
    exit;
}
if (!$applied_offer_id && $discount_amount !== null && $discount_amount != 0) {
     http_response_code(400);
     echo json_encode(['status' => 'error', 'message' => 'Discount amount provided without an offer ID.']);
     exit;
}
// Ensure discount is 0 if no offer ID
if (!$applied_offer_id) {
    $discount_amount = 0.00;
}


// --- Time Processing ---
try {
    $datetime_string = $booking_date . ' ' . $booking_time;
    $timezone = new DateTimeZone('Asia/Kolkata'); // Or your server's local timezone
    $booking_datetime = new DateTime($datetime_string, $timezone);
    // Convert to UTC for database storage
    $booking_datetime->setTimezone(new DateTimeZone('UTC'));
    $formatted_for_db = $booking_datetime->format('Y-m-d H:i:s');
} catch (Exception $e) {
    error_log("Booking time processing error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid date or time format.']);
    exit;
}

// --- Database Operation ---
try {
    $conn->beginTransaction();

    $full_service_details = "Service: {$sub_service_name}\nItem: {$service_item_name}\nAddress: {$address}";

    // If an offer is applied, verify it again and increment count
    if ($applied_offer_id) {
        // Lock the offer row and re-validate count
        $stmt_offer_check = $conn->prepare(
            "SELECT max_uses, uses_count FROM public.worker_offers WHERE id = ? AND worker_id = ? AND is_active = true FOR UPDATE"
        );
        $stmt_offer_check->execute([$applied_offer_id, $worker_id]);
        $offer_details = $stmt_offer_check->fetch(PDO::FETCH_ASSOC);

        if (!$offer_details) {
            throw new Exception("Applied offer is no longer valid or available.");
        }

        if ($offer_details['max_uses'] !== null && $offer_details['uses_count'] >= $offer_details['max_uses']) {
            throw new Exception("Coupon usage limit reached just before booking confirmation.");
        }

        // Increment the usage count
        $stmt_increment = $conn->prepare(
            "UPDATE public.worker_offers SET uses_count = uses_count + 1 WHERE id = ?"
        );
        $increment_success = $stmt_increment->execute([$applied_offer_id]);
        if (!$increment_success) {
             throw new Exception("Failed to update coupon usage count.");
        }
    }

    // Insert the booking record
    $sql_insert = "INSERT INTO public.bookings (customer_id, worker_id, service_details, booking_time, status, final_cost";
    $params = [$customer_id, $worker_id, $full_service_details, $formatted_for_db, $price]; // Use original price here

    if ($applied_offer_id) {
        $sql_insert .= ", applied_offer_id, discount_amount";
        $params[] = $applied_offer_id;
        $params[] = $discount_amount;
    }

    $sql_insert .= ") VALUES (?, ?, ?, ?, 'pending', ?";
    if ($applied_offer_id) {
        $sql_insert .= ", ?, ?";
    }
    $sql_insert .= ")";

    $stmt_insert = $conn->prepare($sql_insert);
    $insert_success = $stmt_insert->execute($params);

    if (!$insert_success) {
         throw new Exception("Failed to create booking record.");
    }

    // If everything succeeded, commit the transaction
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Booking created successfully.']);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Booking creation PDO failed: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database error during booking creation.']);
    exit;
} catch (Exception $e) {
     if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Booking creation failed: " . $e->getMessage());
    http_response_code(400); // Bad Request for logical errors like coupon invalidation
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>