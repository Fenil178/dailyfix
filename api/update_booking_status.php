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

// Get the booking time if a status update to 'confirmed' is requested
$booking_time = null;
if ($new_status === 'confirmed') {
    if (!isset($_GET['booking_time'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing booking time for confirmation.']);
        exit;
    }
    $booking_time = $_GET['booking_time'];
}

// 3. Validate the status value: ADDED 'in_progress'
$allowed_statuses = ['confirmed', 'cancelled', 'in_progress'];
if (!$booking_id || !in_array($new_status, $allowed_statuses)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input provided.']);
    exit;
}

// --- Database Operation ---
try {
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

    $stmt = $conn->prepare(
        "UPDATE public.bookings 
         SET status = ? 
         WHERE id = ? AND worker_id = ?"
    );
    
    $stmt->execute([$new_status, $booking_id, $userId]);

    // Check if the update was successful
    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'message' => 'Booking status updated.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Job not found or you do not have permission to modify it.']);
    }

} catch (PDOException $e) {
    error_log("Booking status update failed: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}