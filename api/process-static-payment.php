<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

if ($role !== 'customer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];

try {
    $conn->beginTransaction();

    // 1. Fetch booking details to get amount and worker_id
    $stmt = $conn->prepare("SELECT worker_id, final_cost FROM public.bookings WHERE id = ? AND customer_id = ?");
    $stmt->execute([$booking_id, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or you are not authorized to pay for it.");
    }

    $worker_id = $booking['worker_id'];
    $amount = $booking['final_cost'];

    // 2. Update booking status to 'completed' and payment_status to 'paid'
    $stmt = $conn->prepare("UPDATE public.bookings SET payment_status = 'paid', status = 'completed' WHERE id = ?");
    $stmt->execute([$booking_id]);

    // 3. Get the worker's wallet
    $stmt = $conn->prepare("SELECT id FROM public.wallets WHERE worker_id = ?");
    $stmt->execute([$worker_id]);
    $wallet_id = $stmt->fetchColumn();

    if (!$wallet_id) {
        // If for some reason the worker doesn't have a wallet, create one.
        $stmt_create_wallet = $conn->prepare("INSERT INTO public.wallets (worker_id) VALUES (?) RETURNING id");
        $stmt_create_wallet->execute([$worker_id]);
        $wallet_id = $stmt_create_wallet->fetchColumn();
    }

    // 4. Update worker's wallet balance
    $stmt = $conn->prepare("UPDATE public.wallets SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $wallet_id]);

    // 5. Add a transaction record
    $stmt = $conn->prepare("INSERT INTO public.transactions (wallet_id, booking_id, type, amount, description) VALUES (?, ?, 'credit', ?, ?)");
    $stmt->execute([$wallet_id, $booking_id, $amount, 'Payment for Booking #' . $booking_id]);

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Payment successful!']);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Static Payment Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred during payment processing.']);
}
?>