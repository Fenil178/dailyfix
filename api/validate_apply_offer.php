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

    // 1. Get Booking Details (including worker_id, final_cost, sub_service_item_id), locking the row
    $stmt_booking = $conn->prepare(
        "SELECT worker_id, final_cost, applied_offer_id, status, payment_status, work_completed_by_worker, sub_service_item_id
         FROM public.bookings
         WHERE id = ? AND customer_id = ? FOR UPDATE" // <<< Added item_id
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
     // <<< Check if sub_service_item_id exists >>>
     if (!$booking['sub_service_item_id']) {
         throw new Exception("Cannot validate coupon: Booking is missing service item information."); // <<< Adjusted message
     }

    $original_cost = (float)$booking['final_cost'];
    $worker_id = $booking['worker_id'];
    $sub_service_item_id = $booking['sub_service_item_id']; // <<< Get item_id from booking
    $old_applied_offer_id = $booking['applied_offer_id'];

    // --- LOGIC TO HANDLE PREVIOUSLY APPLIED OFFER ---
    // If an old offer exists, we need to remove its specific usage record
    // and potentially decrement the global count (if the usage record was actually found and deleted).
    $old_usage_deleted = false;
    if ($old_applied_offer_id !== null) {
        // Find the sub_service_item_id associated with the old application (should be the same as current booking's item)
         $stmt_delete_old_usage = $conn->prepare(
             "DELETE FROM public.user_coupon_usage
              WHERE user_id = ? AND offer_id = ? AND sub_service_item_id = ? AND booking_id = ?"
         );
         $stmt_delete_old_usage->execute([$userId, $old_applied_offer_id, $sub_service_item_id, $booking_id]);
         $old_usage_deleted = $stmt_delete_old_usage->rowCount() > 0;

         // Only decrement global count if we actually removed a usage record
         if ($old_usage_deleted) {
             $stmt_decrement_old_offer = $conn->prepare(
                 "UPDATE public.worker_offers SET uses_count = GREATEST(0, uses_count - 1) WHERE id = ?"
             );
             $stmt_decrement_old_offer->execute([$old_applied_offer_id]);
         }
    }
    // --- End handling previous offer ---


    // 2. Find the NEW Offer based on code and worker_id
    $stmt_offer = $conn->prepare(
        "SELECT * FROM public.worker_offers
         WHERE worker_id = ? AND coupon_code = ? AND is_active = true FOR UPDATE" // Lock offer row
    );
    $stmt_offer->execute([$worker_id, $coupon_code]);
    $offer = $stmt_offer->fetch(PDO::FETCH_ASSOC);

    if (!$offer) {
        // Rollback changes made to the old offer if the new one is invalid
        $conn->rollBack();
        throw new Exception("Invalid coupon code.");
    }

    // 3. Validate the NEW Offer (Dates, Global Limit, Min Amount)
    if ($offer['id'] === $old_applied_offer_id) {
         // No need to decrement then increment. Just recalculate and update booking.
         // We'll proceed normally, the decrement already happened, increment will happen below.
         // OR, alternatively, could skip decrement/increment logic if IDs match. Let's proceed normally for simplicity.
    }
    if ($offer['valid_from'] && strtotime($offer['valid_from']) > time()) { $conn->rollBack(); throw new Exception("This coupon is not yet valid."); }
     if ($offer['valid_until'] && strtotime($offer['valid_until']) < time()) { $conn->rollBack(); throw new Exception("This coupon has expired."); }
     if ($offer['max_uses'] !== null && $offer['uses_count'] >= $offer['max_uses']) { $conn->rollBack(); throw new Exception("This coupon has reached its global usage limit."); }
     if ($original_cost < (float)$offer['min_booking_amount']) { $conn->rollBack(); throw new Exception("Booking amount does not meet the minimum requirement (₹" . number_format($offer['min_booking_amount'], 2) . ") for this coupon."); }


    // <<< START: NEW USAGE CHECK (using item_id) >>>
    $stmt_check_usage = $conn->prepare(
        "SELECT 1 FROM public.user_coupon_usage WHERE user_id = ? AND offer_id = ? AND sub_service_item_id = ?" // <<< Use item_id
    );
    $stmt_check_usage->execute([$userId, $offer['id'], $sub_service_item_id]); // <<< Use item_id
    if ($stmt_check_usage->fetchColumn()) {
        $conn->rollBack(); // Rollback changes made to old offer
        throw new Exception("You have already used this coupon code for this specific service item."); // <<< Adjusted message
    }
    // <<< END: NEW USAGE CHECK >>>

    // 4. Calculate Discount for the NEW offer
    // ... (discount calculation logic remains the same) ...
    $discount_amount = 0;
    $discount_value = (float)$offer['discount_value'];
    if ($offer['discount_type'] === 'percentage') { $discount_amount = ($original_cost * $discount_value) / 100; }
    elseif ($offer['discount_type'] === 'fixed') { $discount_amount = $discount_value; }
    $discount_amount = round(min($discount_amount, $original_cost), 2);
    $final_cost_after_discount = round($original_cost - $discount_amount, 2);

    // 5. Update the Booking Record with NEW offer details
    $stmt_update_booking = $conn->prepare(
        "UPDATE public.bookings SET applied_offer_id = ?, discount_amount = ? WHERE id = ?"
    );
    $stmt_update_booking->execute([$offer['id'], $discount_amount, $booking_id]);

    // 6. Record the NEW usage in user_coupon_usage
    $stmt_record_new_usage = $conn->prepare(
        "INSERT INTO public.user_coupon_usage (user_id, offer_id, sub_service_item_id, booking_id) VALUES (?, ?, ?, ?)"
    );
    $stmt_record_new_usage->execute([$userId, $offer['id'], $sub_service_item_id, $booking_id]);

    // 7. Increment the global count for the NEW offer
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
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    error_log("Validate Apply Offer PDO Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred while applying the coupon.';
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $http_code = (strpos($e->getMessage(), "already used this coupon") !== false) ? 409 : 400; // <<< Adjusted message check
    http_response_code($http_code);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>