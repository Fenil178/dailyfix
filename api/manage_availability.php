<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php"; // Gets user ID and role from session

// Check if the user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// --- THIS IS THE FIX ---
// Release the session lock immediately after verifying the user.
// This allows all the parallel requests from the browser to be processed concurrently,
// preventing timeouts and the "stuck" button.
session_write_close();

// Check for required parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['date'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing date parameter.']);
    exit;
}

$date = $_POST['date'];
$timeSlots = $_POST['time_slots'] ?? [];

try {
    $conn->beginTransaction();

    // 1. Delete all existing availability for the specified date and worker
    $stmt_delete = $conn->prepare("DELETE FROM public.worker_availability WHERE user_id = ? AND date = ?");
    $stmt_delete->execute([$userId, $date]);

    // 2. Insert the new time slots if the array is not empty
    if (!empty($timeSlots)) {
        $stmt_insert = $conn->prepare("INSERT INTO public.worker_availability (user_id, date, time_slot) VALUES (?, ?, ?)");
        foreach ($timeSlots as $slot) {
            // Basic validation to ensure the slot format is correct
            if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $slot)) {
                $stmt_insert->execute([$userId, $date, $slot]);
            }
        }
    }

    $conn->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Availability updated successfully.']);

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Worker availability update failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>