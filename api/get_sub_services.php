<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";

$response = ['status' => 'error', 'message' => 'Invalid request.', 'data' => []];

if (isset($_GET['service_id']) && is_numeric($_GET['service_id'])) {
    $service_id = (int)$_GET['service_id'];
    try {
        $stmt = $conn->prepare("SELECT id, name FROM public.sub_services WHERE service_id = ? ORDER BY name");
        $stmt->execute([$service_id]);
        $sub_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['status' => 'success', 'data' => $sub_services];
    } catch (PDOException $e) {
        error_log("API Error: " . $e->getMessage());
        $response['message'] = 'A database error occurred.';
    }
}

echo json_encode($response);
?>