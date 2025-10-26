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

    // 1. Get Booking details including applied offer and sub_service_item_id, lock row
    $stmt_booking = $conn->prepare(
        "SELECT final_cost, applied_offer_id, payment_status, work_completed_by_worker, sub_service_item_id
         FROM public.bookings
         WHERE id = ? AND customer_id = ? FOR UPDATE" // <<< Added item_id
    );
    $stmt_booking->execute([$booking_id, $userId]);
    $booking = $stmt_booking->fetch(PDO::FETCH_ASSOC);

    // Validation checks
    if (!$booking) { 
        throw new Exception("Booking not found or access denied."); 
    }

    if ($booking['payment_status'] !== 'unpaid' || !$booking['work_completed_by_worker']) { 
        throw new Exception("Cannot modify offer status at this stage."); 
    }

    if ($booking['applied_offer_id'] === null) { 
        throw new Exception("No offer is currently applied to this booking."); 
    }

    if (!$booking['sub_service_item_id']) { 
        throw new Exception("Cannot remove coupon: Booking is missing service item information."); 
    } // <<< Added item_id check

    $applied_offer_id = $booking['applied_offer_id'];
    $sub_service_item_id = $booking['sub_service_item_id']; // <<< Get item_id
    $original_cost = (float)($booking['final_cost'] ?? 0.00);

    // 2. <<< Delete the specific usage record using item_id >>>
    $stmt_delete_usage = $conn->prepare(
        "DELETE FROM public.user_coupon_usage WHERE user_id = ? AND offer_id = ? AND sub_service_item_id = ? AND booking_id = ?" // <<< Use item_id
    );
    $stmt_delete_usage->execute([$userId, $applied_offer_id, $sub_service_item_id, $booking_id]); // <<< Use item_id
    $was_usage_deleted = $stmt_delete_usage->rowCount() > 0; // Check if a row was actually deleted

    // 3. Update the Booking Record to remove offer details
    $stmt_update_booking = $conn->prepare(
        "UPDATE public.bookings SET applied_offer_id = NULL, discount_amount = NULL WHERE id = ?"
    );
    $stmt_update_booking->execute([$booking_id]);

    // 4. <<< Conditionally Decrement the global offer usage count >>>
    // Only decrement if we successfully removed a usage record in step 2
    if ($was_usage_deleted) {
        $stmt_decrement_offer = $conn->prepare(
            "UPDATE public.worker_offers SET uses_count = GREATEST(0, uses_count - 1) -- Prevent going below 0
             WHERE id = ?"
        );
        $stmt_decrement_offer->execute([$applied_offer_id]);
    } else {
        // Log a warning if no usage record was found to delete - might indicate inconsistency
        error_log("Warning: Could not find user_coupon_usage record to delete for user $userId, offer $applied_offer_id, item $sub_service_item_id, booking $booking_id during offer removal.");
    }

    $conn->commit();

    $response = [
        'status' => 'success',
        'message' => 'Coupon removed successfully!',
        'original_cost' => number_format($original_cost, 2) // Send back original cost for UI update
    ];

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    error_log("Remove Worker Offer PDO Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred while removing the coupon.';
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>