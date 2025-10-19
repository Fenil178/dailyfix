<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId, $role

// Security: Allow logged-in customers or workers (e.g., if worker needs to see their own active offers)
if (!isset($userId) || !in_array($role, ['customer', 'worker'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];
$worker_id = filter_input(INPUT_GET, 'worker_id', FILTER_VALIDATE_INT);

if (!$worker_id) {
    http_response_code(400);
    $response['message'] = 'Worker ID is required.';
    echo json_encode($response);
    exit;
}

try {
    $now_utc_str = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

    // Fetch active, valid offers for the specified worker
    $stmt = $conn->prepare("
        SELECT id, coupon_code, discount_type, discount_value, min_booking_amount
        FROM public.worker_offers
        WHERE worker_id = ?
          AND is_active = true
          AND (valid_from IS NULL OR valid_from <= ?)
          AND (valid_until IS NULL OR valid_until >= ?)
          AND (max_uses IS NULL OR uses_count < max_uses)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$worker_id, $now_utc_str, $now_utc_str]);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = ['status' => 'success', 'data' => $offers];

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get Worker Offers Error: " . $e->getMessage());
    $response['message'] = 'A database error occurred.';
}

echo json_encode($response);
?>