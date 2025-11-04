<?php
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
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in or not a customer.']);
    exit;
}

// --- Get Data ---
$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
$sub_service_item_id = filter_input(INPUT_POST, 'sub_service_item_id', FILTER_VALIDATE_INT);
$sub_service_name = trim($_POST['sub_service_name'] ?? '');
$service_item_name = trim($_POST['service_item_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';

// This 'price' is the worker's original price
$worker_earning = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT); 

// Get coupon details
$applied_offer_id = filter_input(INPUT_POST, 'applied_offer_id', FILTER_VALIDATE_INT);
$discount_amount = filter_input(INPUT_POST, 'discount_amount', FILTER_VALIDATE_FLOAT);

// Calculate new platform fee and final cost
$platform_fee_to_save = PLATFORM_FEE;
$total_cost_to_customer = $worker_earning + $platform_fee_to_save;
// --- Basic Validation ---
if (!$worker_id || !$customer_id || !$sub_service_item_id || empty($sub_service_name) || empty($service_item_name) || empty($address) || empty($booking_date) || empty($booking_time) || $worker_earning === null || $worker_earning < 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid required fields (sub_service_item_id needed, price >= 0).']);
    exit;
}
if ($userId != $customer_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action.']);
    exit;
}
// Discount validation
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
if (!$applied_offer_id) {
    $discount_amount = 0.00;
}


// --- Time Processing ---
try {
    $datetime_string = $booking_date . ' ' . $booking_time;
    $timezone = new DateTimeZone('Asia/Kolkata');
    $booking_datetime = new DateTime($datetime_string, $timezone);
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
    // === Payment Enforcement Check ===
    $stmt_debt_check = $conn->prepare("
        SELECT COUNT(*) FROM public.bookings
        WHERE customer_id = ? 
        AND status = 'completed'
        AND payment_status = 'pending' 
    ");
    $stmt_debt_check->execute([$customer_id]);
    if ($stmt_debt_check->fetchColumn() > 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You have outstanding payments. Please settle them before booking.']);
        exit;
    }
    // === End Payment Check ===

    $conn->beginTransaction();

    $full_service_details = "Service: {$sub_service_name}\nItem: {$service_item_name}\nAddress: {$address}";
    $usage_recorded = false; // Flag to check if usage was newly recorded

    // If an offer is applied, validate it again, record usage, and increment count
    if ($applied_offer_id) {
        // Lock the offer row and re-validate global count
        $stmt_offer_check = $conn->prepare(
            "SELECT max_uses, uses_count FROM public.worker_offers WHERE id = ? AND worker_id = ? AND is_active = true FOR UPDATE"
        );
        $stmt_offer_check->execute([$applied_offer_id, $worker_id]);
        $offer_details = $stmt_offer_check->fetch(PDO::FETCH_ASSOC);

        if (!$offer_details) {
            throw new Exception("Applied offer is no longer valid or available.");
        }
        if ($offer_details['max_uses'] !== null && $offer_details['uses_count'] >= $offer_details['max_uses']) {
            throw new Exception("Coupon global usage limit reached just before booking confirmation.");
        }

        // <<< Attempt to record usage for this user, offer, and ITEM >>>
        $stmt_record_usage = $conn->prepare(
            "INSERT INTO public.user_coupon_usage (user_id, offer_id, sub_service_item_id) VALUES (?, ?, ?)
             ON CONFLICT (user_id, offer_id, sub_service_item_id) DO NOTHING RETURNING id" // <<< Use item_id
        );
        $stmt_record_usage->execute([$customer_id, $applied_offer_id, $sub_service_item_id]); // <<< Use item_id

        // Check if a new row was inserted (first time use for this combo)
        if ($stmt_record_usage->fetchColumn()) {
            $usage_recorded = true;
            // Increment the global usage count
            $stmt_increment = $conn->prepare(
                "UPDATE public.worker_offers SET uses_count = uses_count + 1 WHERE id = ?"
            );
            if (!$stmt_increment->execute([$applied_offer_id])) {
                 throw new Exception("Failed to update coupon usage count.");
            }
        } else {
             // Conflict occurred: User already used this coupon for this ITEM
             throw new Exception("You have already used this coupon code for this specific service item."); // <<< Adjusted message
        }
    }

    // <<< Insert the booking record (including sub_service_item_id) >>>
    $sql_insert = "INSERT INTO public.bookings (customer_id, worker_id, sub_service_item_id, service_details, booking_time, status, worker_earning, platform_fee, final_cost";
    $params = [$customer_id, $worker_id, $sub_service_item_id, $full_service_details, $formatted_for_db, $worker_earning, $platform_fee_to_save, $total_cost_to_customer];

    if ($applied_offer_id) {
        $sql_insert .= ", applied_offer_id, discount_amount";
        $params[] = $applied_offer_id;
        $params[] = $discount_amount;
    }
    $sql_insert .= ") VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?";
    if ($applied_offer_id) {
        $sql_insert .= ", ?, ?";
    }
    $sql_insert .= ") RETURNING id"; // Get the new booking ID

    $stmt_insert = $conn->prepare($sql_insert);
    $insert_success = $stmt_insert->execute($params);
    $new_booking_id = $stmt_insert->fetchColumn();

    // Handle potential booking insertion failure
    if (!$insert_success || !$new_booking_id) {
         if ($usage_recorded) { // Rollback count increment if booking failed
             $stmt_decrement = $conn->prepare("UPDATE public.worker_offers SET uses_count = GREATEST(0, uses_count - 1) WHERE id = ?");
             $stmt_decrement->execute([$applied_offer_id]);
         }
         throw new Exception("Failed to create booking record.");
    }

    // <<< Link usage record to booking ID if usage was recorded >>>
    if ($usage_recorded) {
        $stmt_update_usage_booking = $conn->prepare(
            "UPDATE public.user_coupon_usage SET booking_id = ? WHERE user_id = ? AND offer_id = ? AND sub_service_item_id = ?" // <<< Use item_id
        );
        $stmt_update_usage_booking->execute([$new_booking_id, $customer_id, $applied_offer_id, $sub_service_item_id]); // <<< Use item_id
    }

    // Commit transaction
    $conn->commit();

    // --- NOTIFICATION LOGIC ---
    include_once __DIR__ . "/notification_handler.php";
    $link = "booking-details.php?id=$new_booking_id";
    
    // 1. Notify Worker
    // We need the customer's name (which is $userName from user_session.php, already included)
    $message_for_worker = "$userName has sent you a new booking request.";
    create_notification($conn, $worker_id, $customer_id, $message_for_worker, $link);
    
    // 2. Notify Admin
    $message_for_admin = "New booking (#$new_booking_id) from $userName for worker ID $worker_id.";
    create_notification($conn, 'admin', $customer_id, $message_for_admin, $link);
    // --- END NOTIFICATION ---

    echo json_encode(['status' => 'success', 'message' => 'Booking created successfully.']);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Booking creation PDO failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error during booking creation.']);
    exit;
} catch (Exception $e) {
     if ($conn->inTransaction()) $conn->rollBack();
    error_log("Booking creation failed: " . $e->getMessage());
    // Use 409 Conflict if it's the specific coupon usage error
    $http_code = (strpos($e->getMessage(), "already used this coupon") !== false) ? 409 : 400;
    http_response_code($http_code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>