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
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

if (!$booking_id || empty($coupon_code)) {
    http_response_code(400);
    $response['message'] = 'Booking ID and Coupon Code are required.';
    echo json_encode($response);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Get Booking Details (including worker_id and final_cost), locking the row
    $stmt_booking = $conn->prepare(
        "SELECT worker_id, final_cost, applied_offer_id, status, payment_status, work_completed_by_worker
         FROM public.bookings
         WHERE id = ? AND customer_id = ? FOR UPDATE" // Added work_completed_by_worker
    );
    $stmt_booking->execute([$booking_id, $userId]);
    $booking = $stmt_booking->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or access denied.");
    }
    
    // Check payment status and work completion
     if ($booking['payment_status'] !== 'unpaid' || !$booking['work_completed_by_worker']) {
        throw new Exception("Coupon cannot be applied or changed at this stage.");
     }

    $original_cost = (float)$booking['final_cost'];
    $worker_id = $booking['worker_id'];
    $old_applied_offer_id = $booking['applied_offer_id']; // Get the currently applied offer ID

    // --- NEW: Handle currently applied offer ---
    if ($old_applied_offer_id !== null) {
        // Fetch the old offer details to ensure it exists before decrementing
        $stmt_old_offer_check = $conn->prepare("SELECT id FROM public.worker_offers WHERE id = ?");
        $stmt_old_offer_check->execute([$old_applied_offer_id]);
        if ($stmt_old_offer_check->fetch()) {
             // Decrement the usage count of the OLD offer
            $stmt_decrement_old_offer = $conn->prepare(
                "UPDATE public.worker_offers SET uses_count = uses_count - 1
                 WHERE id = ? AND uses_count > 0" // Safety check
            );
            $stmt_decrement_old_offer->execute([$old_applied_offer_id]);
        } else {
             error_log("Attempted to decrement count for non-existent old offer ID: $old_applied_offer_id on booking ID: $booking_id");
             // Don't throw an error, just proceed, but log it.
             // The booking record might be inconsistent if the offer was deleted improperly.
        }
    }
    // --- End NEW section ---


    // 2. Find the NEW Offer based on code and worker_id
    $stmt_offer = $conn->prepare(
        // Add FOR UPDATE to lock the offer row during validation and update
        "SELECT * FROM public.worker_offers 
         WHERE worker_id = ? AND coupon_code = ? AND is_active = true FOR UPDATE" 
    );
    $stmt_offer->execute([$worker_id, $coupon_code]);
    $offer = $stmt_offer->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        // If the new offer is invalid, we need to rollback the decrement of the old offer count!
        $conn->rollBack(); // Rollback the whole transaction
        throw new Exception("Invalid or inactive coupon code for this worker.");
    }
    
    // Check if user is trying to apply the SAME offer again (edge case)
    if ($offer['id'] === $old_applied_offer_id) {
         // No need to decrement then increment. Just recalculate and update booking.
         // We'll proceed normally, the decrement already happened, increment will happen below.
         // OR, alternatively, could skip decrement/increment logic if IDs match. Let's proceed normally for simplicity.
    }


    // 3. Validate the NEW Offer
    if ($offer['valid_from'] && strtotime($offer['valid_from']) > time()) {
        $conn->rollBack(); throw new Exception("This coupon is not yet valid.");
    }
    if ($offer['valid_until'] && strtotime($offer['valid_until']) < time()) {
       $conn->rollBack(); throw new Exception("This coupon has expired.");
    }
    // CRITICAL: Check count BEFORE incrementing
    if ($offer['max_uses'] !== null && $offer['uses_count'] >= $offer['max_uses']) {
       $conn->rollBack(); throw new Exception("This coupon has reached its usage limit.");
    }
    if ($original_cost < (float)$offer['min_booking_amount']) {
       $conn->rollBack(); throw new Exception("Booking amount does not meet the minimum requirement (₹" . number_format($offer['min_booking_amount'], 2) . ") for this coupon.");
    }

    // 4. Calculate Discount for the NEW offer
    $discount_amount = 0;
    $discount_value = (float)$offer['discount_value'];

    if ($offer['discount_type'] === 'percentage') {
        $discount_amount = ($original_cost * $discount_value) / 100;
    } elseif ($offer['discount_type'] === 'fixed') {
        $discount_amount = $discount_value;
    }

    // Ensure discount doesn't exceed original cost
    $discount_amount = round(min($discount_amount, $original_cost), 2); // Round here
    $final_cost_after_discount = round($original_cost - $discount_amount, 2); // Round final cost

    // 5. Update the Booking Record with NEW offer details
    $stmt_update_booking = $conn->prepare(
        "UPDATE public.bookings SET applied_offer_id = ?, discount_amount = ? WHERE id = ?"
    );
    $stmt_update_booking->execute([$offer['id'], $discount_amount, $booking_id]);

    // 6. Increment the NEW offer usage count
    $stmt_increment_new_offer = $conn->prepare(
       "UPDATE public.worker_offers SET uses_count = uses_count + 1 WHERE id = ?"
    );
    $stmt_increment_new_offer->execute([$offer['id']]);


    $conn->commit(); // Commit all changes

    $response = [
        'status' => 'success',
        'message' => 'Coupon applied successfully!',
        'original_cost' => number_format($original_cost, 2),
        'discount_amount' => number_format($discount_amount, 2),
        'final_cost_after_discount' => number_format($final_cost_after_discount, 2),
        'offer_id' => $offer['id'] 
    ];

} catch (PDOException $e) {
    $conn->rollBack(); // Ensure rollback on PDO errors too
    http_response_code(500);
    error_log("Validate Apply Offer PDO Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred while applying the coupon.';
} catch (Exception $e) {
    // Rollback might have already happened if error was thrown before commit
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400); // Use Bad Request for validation logic errors
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>