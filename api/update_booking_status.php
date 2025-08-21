<?php

// Set headers for JSON response
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/header.php"; // Provides $userId and $role

$booking_id_from_url = $_GET['id'];

error_log('Debug: GET parameters -> ' . json_encode($_GET));
error_log('Debug: Session User ID -> ' . $userId);

// 1. Ensure user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// 2. Check for required parameters
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing booking ID or status.']);
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$new_status = $_GET['status'];

// 3. Validate the status value
$allowed_statuses = ['confirmed', 'cancelled'];
if (!$booking_id || !in_array($new_status, $allowed_statuses)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid input provided.']);
    exit;
}

// --- Database Operation ---

try {
    $stmt = $conn->prepare(
        "UPDATE public.bookings 
         SET status = ? 
         WHERE id = ? AND worker_id = ?" // Extra check to ensure worker owns this job
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
?>