<?php
header('Content-Type: application/json');
include_once __DIR__ . '/connect.php';

$response = ['success' => false, 'data' => null];
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT address_line1, address_line2, city, state, pincode, latitude, longitude FROM public.users WHERE id = ?");
        $stmt->execute([$user_id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($location) {
            $response['success'] = true;
            $response['data'] = $location;
        }
    } catch (PDOException $e) {
        error_log("Get User Location Error: " . $e->getMessage());
    }
}

echo json_encode($response);
exit();