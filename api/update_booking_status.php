<?php
// api/update_booking_status.php
session_start();
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/encryption.php"; // Include encryption for user ID validation

header('Content-Type: application/json');

// Get worker ID from cookie (security check)
if (!isset($_COOKIE['encrypted_user_id']) || !isset($_COOKIE['encrypted_user_role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$workerId = decrypt_id($_COOKIE['encrypted_user_id']);
$role = decrypt_id($_COOKIE['encrypted_user_role']);

if (!$workerId || $role !== 'worker') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}


if (!isset($_GET['id']) || !isset($_GET['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or status.']);
    exit;
}

$bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$newStatus = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
$validStatuses = ['confirmed', 'cancelled', 'in_progress', 'completed']; // Added 'completed' just in case

// Validate status
if (!$bookingId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID or status provided.']);
    exit;
}

// Get the reason if the status is 'cancelled'
$reason = null;
if ($newStatus === 'cancelled' && isset($_GET['reason'])) {
    $reason = trim(filter_input(INPUT_GET, 'reason', FILTER_SANITIZE_STRING));
    if (empty($reason)) {
        // While the JS validates, add a server-side check
        echo json_encode(['status' => 'error', 'message' => 'Cancellation reason cannot be empty.']);
        exit;
    }
}


try {
    $conn->beginTransaction();

    // Check current status and ownership before updating
    $stmtCheck = $conn->prepare("SELECT status FROM public.bookings WHERE id = ? AND worker_id = ?");
    $stmtCheck->execute([$bookingId, $workerId]);
    $currentBooking = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentBooking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or you are not assigned to this job.']);
        $conn->rollBack();
        exit;
    }

    $currentStatus = $currentBooking['status'];

    // Define allowed transitions
    $allowedTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['in_progress', 'cancelled'], // Worker might cancel confirmed job (though maybe needs reason too?)
        'in_progress' => ['completed'] // Or potentially 'cancelled' with consequences? For now, only complete.
    ];

    // Check if the transition is allowed
    if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
         echo json_encode(['status' => 'error', 'message' => "Cannot change status from '$currentStatus' to '$newStatus'."]);
         $conn->rollBack();
         exit;
    }

    // --- Prepare SQL Update ---
    $sql = "UPDATE public.bookings SET status = :newStatus";
    $params = [':newStatus' => $newStatus, ':bookingId' => $bookingId, ':workerId' => $workerId];

    // Add confirmed_at timestamp if accepting
    if ($currentStatus === 'pending' && $newStatus === 'confirmed') {
        $sql .= ", confirmed_at = NOW()";
    }

    // Add rejection_reason if declining a PENDING job
    if ($currentStatus === 'pending' && $newStatus === 'cancelled' && $reason !== null) {
        $sql .= ", rejection_reason = :reason";
        $params[':reason'] = $reason;
    }
    // Add cancellation_reason if cancelling a CONFIRMED job (Optional: decide if workers can cancel confirmed jobs and if reason is mandatory)
    /* else if ($currentStatus === 'confirmed' && $newStatus === 'cancelled' && $reason !== null) {
        $sql .= ", cancellation_reason = :reason"; // Use cancellation_reason column if applicable
        $params[':reason'] = $reason;
    } */


    $sql .= " WHERE id = :bookingId AND worker_id = :workerId"; // Double check worker ID

    $stmt = $conn->prepare($sql);
    $success = $stmt->execute($params);

    if ($success && $stmt->rowCount() > 0) {
        // TODO: Add notification logic here if needed (e.g., notify customer of acceptance/rejection)
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Booking status updated successfully.']);

    } else if ($success && $stmt->rowCount() === 0) {
         // This case might happen if the WHERE clause didn't match (e.g., wrong worker ID somehow)
         echo json_encode(['status' => 'error', 'message' => 'Booking not found or update failed (no rows affected).']);
         $conn->rollBack();
    }
    else {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to update booking status.']);
    }

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Update booking status error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}

?>