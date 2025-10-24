<?php
// You should save this file as: dailyfix/api/customer_cancel_booking.php

// Set headers for JSON response
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php";

// 1. Ensure user is a logged-in customer
if (!isset($userId) || $role !== 'customer') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// 2. Check for required parameters
// Expect JSON data from the fetch request
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['booking_id']) || empty($data['cancellation_reason'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or mandatory cancellation reason.']);
    exit;
}

$booking_id = filter_var($data['booking_id'], FILTER_VALIDATE_INT);
$cancellation_reason = trim($data['cancellation_reason']);

if (!$booking_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID.']);
    exit;
}

// --- Database Operation ---
try {
    $stmt_current = $conn->prepare("
        SELECT status, confirmed_at 
        FROM public.bookings
        WHERE id = ? AND customer_id = ?
    ");
    $stmt_current->execute([$booking_id, $userId]);
    $current_booking = $stmt_current->fetch(PDO::FETCH_ASSOC);

    if (!$current_booking) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or you do not have permission to modify it.']);
        exit;
    }

    // Check if cancellation is allowed based on status
    if (!in_array($current_booking['status'], ['pending', 'confirmed'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Booking cannot be cancelled in status: ' . $current_booking['status'] . '.']);
        exit;
    }

    // Apply 1-hour rule if it was confirmed
    if ($current_booking['status'] === 'confirmed') {
        $confirmed_timestamp = strtotime($current_booking['confirmed_at']);
        $current_timestamp = time();
        // Check if more than 1 hour (3600 seconds) has passed since confirmation
        if (($current_timestamp - $confirmed_timestamp) > 3600) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Cancellation failed. More than 1 hour has passed since the booking was accepted by the worker.']);
            exit;
        }
    }
    
    // Update the booking status and add the reason
    $stmt = $conn->prepare(
        "UPDATE public.bookings 
         SET status = 'cancelled', cancellation_reason = ? 
         WHERE id = ? AND customer_id = ?"
    );
    
    $stmt->execute([$cancellation_reason, $booking_id, $userId]);

    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Booking cancelled successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Job not found or failed to update.']);
    }

} catch (PDOException $e) {
    error_log("Customer booking cancellation failed: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>