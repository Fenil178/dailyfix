<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId and $role

$response = ['status' => 'error', 'message' => 'Invalid request.'];

// 1. Security Check: Ensure a logged-in ADMIN is making a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $role !== 'admin') {
    http_response_code(403);
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

// 2. Calculate the available balance server-side
try {
    // Start a transaction
    $conn->beginTransaction();

    // 2a. Get current total earnings
    $stmt_total = $conn->prepare("
        SELECT SUM(COALESCE(platform_fee, 0)) FROM public.bookings
        WHERE status = 'completed' AND payment_status = 'paid' AND platform_fee > 0
    ");
    $stmt_total->execute();
    $totalEarnings = (float)($stmt_total->fetchColumn() ?: 0);

    // 2b. Get current PROCESSED payouts
    $stmt_processed = $conn->prepare("
        SELECT SUM(COALESCE(amount, 0)) FROM public.platform_payouts WHERE status = 'processed'
    ");
    $stmt_processed->execute();
    $processedPayouts = (float)($stmt_processed->fetchColumn() ?: 0);

    // 2c. Calculate available balance
    $available_balance = $totalEarnings - $processedPayouts;
    
    // 2d. This is the new payout amount
    $payout_amount = $available_balance;

    // 3. Validation
    if ($payout_amount <= 0) {
        throw new Exception('No available balance to process a payout.');
    }

    // 4. Create the new Payout request record with 'processed' status
    $stmt_payout = $conn->prepare(
        "INSERT INTO public.platform_payouts (amount, requested_by_admin_id, status) VALUES (?, ?, 'processed')"
    );
    $stmt_payout->execute([$payout_amount, $userId]); // $userId is the admin's ID from user_session.php

    // Commit all changes
    $conn->commit();
    $response = ['status' => 'success', 'message' => 'Payout of ₹' . number_format($payout_amount, 2) . ' processed successfully.'];

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log("Admin payout request failed: " . $e->getMessage());
    $response['message'] = 'A system error occurred during the payout request.';
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>