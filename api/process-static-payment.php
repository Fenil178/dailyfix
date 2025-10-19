<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

// --- Basic Request Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// --- Authorization Check ---
if ($role !== 'customer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];

try {
    // --- Start Transaction ---
    $conn->beginTransaction();

    // --- Fetch and Lock Booking ---
    // Select necessary fields including work_completed_by_worker
    $stmt = $conn->prepare("
        SELECT 
            worker_id, 
            final_cost, 
            applied_offer_id, 
            discount_amount, 
            payment_status, 
            work_completed_by_worker 
        FROM public.bookings 
        WHERE id = ? AND customer_id = ? 
        FOR UPDATE 
    ");
    $stmt->execute([$booking_id, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Booking Validation ---
    if (!$booking) {
        throw new Exception("Booking not found or you are not authorized to pay for it.");
    }
    if ($booking['payment_status'] === 'paid') {
        throw new Exception("This booking has already been paid.");
    }
    // Ensure worker has marked the job as complete before allowing payment
    if (!$booking['work_completed_by_worker']) {
        throw new Exception("Payment cannot be processed until the worker marks the job as complete.");
    }

    // --- Get Booking Details ---
    $worker_id = $booking['worker_id'];
    $original_cost = (float)$booking['final_cost'];
    $discount_amount = (float)($booking['discount_amount'] ?? 0.00);
    $applied_offer_id = $booking['applied_offer_id']; // Needed for transaction description

    // --- Calculate Final Amount (Server-Side) ---
    $amount_paid = round(max(0, $original_cost - $discount_amount), 2);

    // --- Critical Amount Validation ---
    if (!is_numeric($original_cost) || $original_cost <= 0) {
        throw new Exception("The original cost (₹" . number_format($original_cost, 2) . ") is invalid. Contact support.");
    }
    if (!is_numeric($amount_paid) || $amount_paid < 0) {
        // This should ideally not happen with max(0, ...) but good as a safeguard
        error_log("Payment failed for booking $booking_id: Calculated final amount negative ($amount_paid). Original: $original_cost, Discount: $discount_amount.");
        throw new Exception("The final amount to pay (₹" . number_format($amount_paid, 2) . ") is invalid after discount. Contact support.");
    }

    // --- Update Booking Status ---
    $stmt_update_booking = $conn->prepare("
        UPDATE public.bookings 
        SET payment_status = 'paid', status = 'completed' 
        WHERE id = ?
    ");
    $stmt_update_booking->execute([$booking_id]);

    // --- Get or Create Worker's Wallet (Locked) ---
    $stmt_wallet = $conn->prepare("SELECT id FROM public.wallets WHERE worker_id = ? FOR UPDATE");
    $stmt_wallet->execute([$worker_id]);
    $wallet_id = $stmt_wallet->fetchColumn();

    if (!$wallet_id) {
        $stmt_create_wallet = $conn->prepare("INSERT INTO public.wallets (worker_id) VALUES (?) RETURNING id");
        $stmt_create_wallet->execute([$worker_id]);
        $wallet_id = $stmt_create_wallet->fetchColumn();
        if (!$wallet_id) {
            throw new Exception("Failed to create wallet for the worker.");
        }
    }

    // --- Update Worker's Wallet Balance ---
    $stmt_update_wallet = $conn->prepare("UPDATE public.wallets SET balance = balance + ? WHERE id = ?");
    $stmt_update_wallet->execute([$amount_paid, $wallet_id]); // Credit the actual amount paid

    // --- Add Transaction Record ---
    $description = 'Payment received for Booking #' . $booking_id;
    if ($discount_amount > 0) {
        // Include original and discount in description for clarity
        $description .= " (Original: ₹".number_format($original_cost, 2).", Discount: ₹".number_format($discount_amount, 2).")";
    }
    $stmt_transaction = $conn->prepare("
        INSERT INTO public.transactions (wallet_id, booking_id, type, amount, description) 
        VALUES (?, ?, 'credit', ?, ?)
    ");
    $stmt_transaction->execute([$wallet_id, $booking_id, $amount_paid, $description]); // Log the actual amount paid

    // --- Offer Usage Count Increment REMOVED ---
    // The uses_count is now incremented in validate_apply_offer.php when the offer is first applied.
    // No action needed here regarding offer count.

    // --- Commit Transaction ---
    $conn->commit();

    // --- Success Response ---
    echo json_encode(['status' => 'success', 'message' => 'Payment successful!']);

} catch (Exception $e) {
    // --- Error Handling ---
    // Rollback if transaction is still active
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Static Payment Error for Booking ID $booking_id: " . $e->getMessage());
    http_response_code(500); // Internal Server Error is appropriate

    // Provide a user-friendly message for specific, known errors
    $user_message = 'Payment processing failed due to a system error. Please try again later or contact support.';
    if (strpos($e->getMessage(), 'invalid') !== false ||
        strpos($e->getMessage(), 'Cannot process') !== false ||
        strpos($e->getMessage(), 'already been paid') !== false ||
        strpos($e->getMessage(), 'marks the job as complete') !== false ||
        strpos($e->getMessage(), 'Booking not found') !== false)
    {
        $user_message = $e->getMessage(); // Show specific validation/state errors directly
    }

    echo json_encode(['status' => 'error', 'message' => $user_message]);
}
?>