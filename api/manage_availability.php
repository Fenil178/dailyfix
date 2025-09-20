<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php"; // Changed from header.php to user_session.php

// Check if the user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Check for required parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['date']) || !isset($_POST['time_slots'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing date or time slots.']);
    exit;
}

$date = $_POST['date'];
$timeSlots = $_POST['time_slots'];

try {
    $conn->beginTransaction();

    // 1. Delete all existing availability for the specified date and worker
    $stmt = $conn->prepare("DELETE FROM public.worker_availability WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);

    // 2. Insert the new time slots if the array is not empty
    if (!empty($timeSlots)) {
        $stmt_insert = $conn->prepare("INSERT INTO public.worker_availability (user_id, date, time_slot) VALUES (?, ?, ?)");
        foreach ($timeSlots as $slot) {
            $stmt_insert->execute([$userId, $date, $slot]);
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