<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

$response = ['status' => 'error', 'message' => 'Invalid request.'];

// 1. Security Check: Ensure a logged-in worker is making a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $role !== 'worker') {
    http_response_code(403);
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

$workerId = $userId;

try {
    // Start a transaction to ensure database consistency
    $conn->beginTransaction();

    // 2. Fetch the worker's current wallet balance, locking the row for update (FOR UPDATE).
    $stmt_wallet = $conn->prepare("SELECT id, balance FROM public.wallets WHERE worker_id = ? FOR UPDATE");
    $stmt_wallet->execute([$workerId]);
    $wallet = $stmt_wallet->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $conn->rollBack();
        $response['message'] = 'Wallet not found.';
        echo json_encode($response);
        exit;
    }

    $wallet_id = $wallet['id'];
    $payout_amount = (float) $wallet['balance'];

    // 3. Validate balance for payout
    if ($payout_amount <= 0.00) {
        $conn->rollBack();
        $response['message'] = 'Your available balance is zero.';
        echo json_encode($response);
        exit;
    }

    // 4. Create a new Payout request record (status 'pending')
    // This assumes the payouts table has an auto-increment ID to reference in the transaction.
    $stmt_payout = $conn->prepare("INSERT INTO public.payouts (wallet_id, amount, status) VALUES (?, ?, 'pending')");
    $stmt_payout->execute([$wallet_id, $payout_amount]);
    $payout_id = $conn->lastInsertId('public.payouts_id_seq');

    // 5. Deduct the full amount from the wallet balance
    $stmt_update_balance = $conn->prepare("UPDATE public.wallets SET balance = 0.00, updated_at = NOW() WHERE id = ?");
    $stmt_update_balance->execute([$wallet_id]);

    // 6. Create a 'debit' transaction record for the payout request
    $stmt_transaction = $conn->prepare("INSERT INTO public.transactions (wallet_id, type, amount, description) VALUES (?, 'debit', ?, ?)");
    $stmt_transaction->execute([$wallet_id, $payout_amount, 'Payout Request #' . $payout_id]);

    // Commit all changes
    $conn->commit();
    $response = ['status' => 'success', 'message' => 'Payout request of ₹' . number_format($payout_amount, 2) . ' submitted successfully.'];

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Payout request failed for worker " . $workerId . ": " . $e->getMessage());
    $response['message'] = 'A system error occurred during the payout request.';
}

echo json_encode($response);
?>