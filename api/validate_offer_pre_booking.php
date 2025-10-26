<?php
// /dailyfix/api/validate_offer_pre_booking.php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId, $role

// Security: Only allow logged-in customers via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($userId) || $role !== 'customer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];
$sub_service_item_id = filter_input(INPUT_POST, 'sub_service_item_id', FILTER_VALIDATE_INT); // <<< Get item_id
$worker_id = filter_input(INPUT_POST, 'worker_id', FILTER_VALIDATE_INT);
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
$item_price = filter_input(INPUT_POST, 'item_price', FILTER_VALIDATE_FLOAT);

if (!$worker_id || empty($coupon_code) || $item_price === null || $item_price < 0 || !$sub_service_item_id) {
    http_response_code(400);
    $response['message'] = 'Worker ID, Coupon Code, valid Item Price, and Sub-Service Item ID are required.';
    echo json_encode($response);
    exit;
}

try {
    // Find the Offer based on code and worker_id (NO LOCKING here)
    $stmt_offer = $conn->prepare(
        "SELECT * FROM public.worker_offers
         WHERE worker_id = ? AND coupon_code = ? AND is_active = true"
    );
    $stmt_offer->execute([$worker_id, $coupon_code]);
    $offer = $stmt_offer->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        throw new Exception("Invalid or inactive coupon code for this worker.");
    }

    // <<< START: NEW USAGE CHECK (using item_id) >>>
    $stmt_check_usage = $conn->prepare(
        "SELECT 1 FROM public.user_coupon_usage WHERE user_id = ? AND offer_id = ? AND sub_service_item_id = ?" // <<< Use item_id
    );
    $stmt_check_usage->execute([$userId, $offer['id'], $sub_service_item_id]); // <<< Use item_id
    if ($stmt_check_usage->fetchColumn()) {
        throw new Exception("You have already used this coupon code for this specific service item."); // <<< Adjusted message
    }
    // <<< END: NEW USAGE CHECK >>>

    // Validate the Offer
    if ($offer['valid_from'] && strtotime($offer['valid_from']) > time()) {
        throw new Exception("This coupon is not yet valid.");
    }
    if ($offer['valid_until'] && strtotime($offer['valid_until']) < time()) {
       throw new Exception("This coupon has expired.");
    }
    if ($offer['max_uses'] !== null && $offer['uses_count'] >= $offer['max_uses']) {
       throw new Exception("This coupon has reached its usage limit.");
    }
     // Validate against item_price, not booking's final_cost
    if ($item_price < (float)$offer['min_booking_amount']) {
       throw new Exception("Selected service amount (₹" . number_format($item_price, 2) . ") does not meet the minimum requirement (₹" . number_format($offer['min_booking_amount'], 2) . ") for this coupon.");
    }

    // Calculate Discount based on item_price
    $discount_amount = 0;
    $discount_value = (float)$offer['discount_value'];

    if ($offer['discount_type'] === 'percentage') {
        $discount_amount = ($item_price * $discount_value) / 100;
    } elseif ($offer['discount_type'] === 'fixed') {
        $discount_amount = $discount_value;
    }

    // Ensure discount doesn't exceed item price
    $discount_amount = round(min($discount_amount, $item_price), 2);
    $final_cost_after_discount = round($item_price - $discount_amount, 2);

    $response = [
        'status' => 'success',
        'message' => 'Coupon is valid!',
        'offer_id' => $offer['id'], // Send back the ID
        'original_price' => number_format($item_price, 2),
        'discount_amount' => number_format($discount_amount, 2),
        'final_price' => number_format($final_cost_after_discount, 2),
        'coupon_code' => $offer['coupon_code'] // Send back the code for display consistency
    ];

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Validate Offer Pre-Booking PDO Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred while validating the coupon.';
} catch (Exception $e) {
    $http_code = (strpos($e->getMessage(), "already used this coupon") !== false) ? 409 : 400;
    http_response_code(400); // Use Bad Request for validation logic errors
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>