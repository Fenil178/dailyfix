<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

if ($role !== 'worker') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];

try {
    $stmt = $conn->prepare(
        "UPDATE public.bookings 
         SET work_completed_by_worker = true 
         WHERE id = ? AND worker_id = ?"
    );
    $stmt->execute([$booking_id, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Work marked as complete.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or not owned by you.']);
    }
} catch (PDOException $e) {
    error_log("Mark work done error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>