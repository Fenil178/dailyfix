<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Gets $userId

header('Content-Type: application/json');

// 1. Check for valid request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 2. Ensure user is logged in
if (!isset($userId)) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// 3. Perform the delete operation
try {
    $stmt = $conn->prepare("DELETE FROM public.notifications WHERE user_id = ?");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'All notifications cleared.']);
    } else {
        // This is not an error, it just means they had no notifications to clear.
        echo json_encode(['status' => 'success', 'message' => 'No notifications to clear.']);
    }

} catch (PDOException $e) {
    error_log("Failed to clear notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>