<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/encryption.php"; // Include encryption for user ID validation
include_once __DIR__ . "/user_session.php"; // <-- FIX: Added to get $userName for notifications

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
    // <-- FIX: Modified query to also fetch customer_id for notification
    $stmtCheck = $conn->prepare("SELECT status, customer_id FROM public.bookings WHERE id = ? AND worker_id = ?");
    $stmtCheck->execute([$bookingId, $workerId]);
    $currentBooking = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$currentBooking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or you are not assigned to this job.']);
        $conn->rollBack();
        exit;
    }

    $currentStatus = $currentBooking['status'];
    $customer_id_to_notify = $currentBooking['customer_id']; // <-- FIX: Store customer_id for later

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
        
        // --- NOTIFICATION LOGIC (FIXED) ---
        include_once __DIR__ . "/notification_handler.php";
        // $userName is the worker's name (from user_session.php)
        $link = "booking-details.php?id=$bookingId"; // <-- FIX: Use $bookingId
        $message_for_customer = '';
        $message_for_admin = '';

        // <-- FIX: Check $newStatus, not $status
        if ($newStatus === 'confirmed') { 
            $message_for_customer = "$userName has confirmed your booking (#$bookingId).";
            $message_for_admin = "Worker $userName confirmed booking #$bookingId.";
        
        // <-- FIX: Check $newStatus, not 'rejected'
        } elseif ($newStatus === 'cancelled') { 
            // <-- FIX: Use $reason variable from earlier
            $reason_text = !empty($reason) ? " Reason: $reason" : ""; 
            $message_for_customer = "$userName has rejected your booking (#$bookingId).$reason_text";
            $message_for_admin = "Worker $userName rejected booking #$bookingId.$reason_text";
        
        } elseif ($newStatus === 'in_progress') {
            $message_for_customer = "$userName has started the job for booking #$bookingId.";
            $message_for_admin = "Worker $userName started job #$bookingId.";
        }
        
        // Send notifications if a message was generated
        if (!empty($message_for_customer)) {
            // <-- FIX: Use $customer_id_to_notify and $workerId (which is $userId)
            create_notification($conn, $customer_id_to_notify, $workerId, $message_for_customer, $link);
            create_notification($conn, 'admin', $workerId, $message_for_admin, $link);
        }
        // --- END NOTIFICATION ---

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