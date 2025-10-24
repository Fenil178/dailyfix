<?php

// Set headers for JSON response
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php";

// 1. Ensure user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// FIX: Release the session lock immediately if sessions are auto-started on the server.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// 2. Check for required parameters
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or status.']);
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$new_status = $_GET['status'];
$cancellation_reason = $_GET['cancellation_reason'] ?? null; // NEW: Capture cancellation reason

// Get the booking time if a status update to 'confirmed' is requested
$booking_time = null;
$confirmed_at = null;
if ($new_status === 'confirmed') {
    if (!isset($_GET['booking_time'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing booking time for confirmation.']);
        exit;
    }
    $booking_time = $_GET['booking_time'];
    $confirmed_at = date('Y-m-d H:i:s', time()); // NEW: Capture confirmation time (in server time, assumed UTC)
}

// 3. Validate the status value: ADDED 'in_progress' and now enforces reason for 'cancelled'
$allowed_statuses = ['confirmed', 'cancelled', 'in_progress'];
if (!$booking_id || !in_array($new_status, $allowed_statuses)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input provided.']);
    exit;
}

// NEW: Enforce cancellation reason if status is 'cancelled'
if ($new_status === 'cancelled' && empty($cancellation_reason)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Cancellation reason is mandatory.']);
    exit;
}

// --- Database Operation ---
try {
    $current_booking = null;
    $one_hour_limit_exceeded = false;
    
    // Check current status and apply 1-hour limit for worker cancellation
    if ($new_status === 'cancelled') {
        $stmt_current = $conn->prepare("
            SELECT status, confirmed_at 
            FROM public.bookings
            WHERE id = ? AND worker_id = ?
        ");
        $stmt_current->execute([$booking_id, $userId]);
        $current_booking = $stmt_current->fetch(PDO::FETCH_ASSOC);

        if (!$current_booking) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Job not found or you do not have permission to modify it.']);
            exit;
        }

        // Apply 1-hour rule if it was confirmed
        if ($current_booking['status'] === 'confirmed') {
            $confirmed_timestamp = strtotime($current_booking['confirmed_at']);
            $current_timestamp = time();
            // Check if more than 1 hour (3600 seconds) has passed since confirmation
            if (($current_timestamp - $confirmed_timestamp) > 3600) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cancellation failed. More than 1 hour has passed since you accepted the booking.']);
                exit;
            }
        }
        // If status was 'pending', worker is rejecting, which is always allowed with a reason.
    }
    
    // START CONFLICT CHECK LOGIC (only for 'confirmed')
    if ($new_status === 'confirmed') {
        $stmt_check = $conn->prepare("
            SELECT COUNT(*) FROM public.bookings
            WHERE worker_id = ?
            AND booking_time = ?
            AND status IN ('confirmed', 'in_progress')
        ");
        $stmt_check->execute([$userId, $booking_time]);
        $conflict_count = $stmt_check->fetchColumn();

        if ($conflict_count > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'conflict', 'message' => 'A confirmed job already exists for this time slot.']);
            exit;
        }
    }
    // END CONFLICT CHECK LOGIC

    // Prepare dynamic update query
    $update_fields = ["status = ?"];
    $params = [$new_status];
    
    if ($new_status === 'confirmed') {
        $update_fields[] = "confirmed_at = ?";
        $params[] = $confirmed_at;
    }
    if ($new_status === 'cancelled') {
        $update_fields[] = "cancellation_reason = ?";
        $params[] = $cancellation_reason;
    }

    $sql = "UPDATE public.bookings 
            SET " . implode(', ', $update_fields) . " 
            WHERE id = ? AND worker_id = ?";
    
    $params[] = $booking_id;
    $params[] = $userId;
    
    $stmt = $conn->prepare($sql);
    
    $stmt->execute($params);

    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Booking status updated to ' . $new_status . '.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Job not found or you do not have permission to modify it.']);
    }

} catch (PDOException $e) {
    error_log("Booking status update failed: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>