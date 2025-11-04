<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Gets $userId

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!$userId) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE public.notifications SET is_read = true WHERE user_id = ? AND is_read = false");
    $stmt->execute([$userId]);
    
    echo json_encode(['status' => 'success', 'message' => 'Notifications marked as read']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Failed to mark notifications read: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>