<?php
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
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

if (!$booking_id) {
    http_response_code(400);
    $response['message'] = 'Booking ID is required.';
    echo json_encode($response);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Get Booking's Original Cost and check ownership, locking the row
    $stmt_booking = $conn->prepare(
        "SELECT final_cost, applied_offer_id, payment_status, work_completed_by_worker
         FROM public.bookings
         WHERE id = ? AND customer_id = ? FOR UPDATE"
    );
    $stmt_booking->execute([$booking_id, $userId]);
    $booking = $stmt_booking->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or access denied.");
    }
     // Can only remove if unpaid and worker has completed
    if ($booking['payment_status'] !== 'unpaid' || !$booking['work_completed_by_worker']) {
        throw new Exception("Cannot modify offer status at this stage.");
    }

    $applied_offer_id = $booking['applied_offer_id'];
    
    // Check if an offer was actually applied before trying to remove
    if ($applied_offer_id === null) {
        throw new Exception("No offer is currently applied to this booking.");
    }

    $original_cost = (float)($booking['final_cost'] ?? 0.00);

    // 2. Update the Booking Record to remove offer details
    $stmt_update_booking = $conn->prepare(
        "UPDATE public.bookings SET applied_offer_id = NULL, discount_amount = NULL WHERE id = ?"
    );
    $stmt_update_booking->execute([$booking_id]);
    
    // 3. NEW: Decrement the offer usage count
    // This reverses the increment from validate_apply_offer.php
    $stmt_decrement_offer = $conn->prepare(
        "UPDATE public.worker_offers SET uses_count = uses_count - 1
         WHERE id = ? AND uses_count > 0" // Safety check
    );
    $stmt_decrement_offer->execute([$applied_offer_id]);
    

    $conn->commit();

    $response = [
        'status' => 'success',
        'message' => 'Coupon removed successfully!',
        'original_cost' => number_format($original_cost, 2) // Send back original cost for UI update
    ];

} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    error_log("Remove Worker Offer PDO Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred while removing the coupon.';
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>