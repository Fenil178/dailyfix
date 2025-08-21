<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['worker_key'])) {
    // Standardize the key format: uppercase, no hyphens.
    $worker_key = trim(strtoupper(str_replace('-', '', $_POST['worker_key'])));

    if (strlen($worker_key) !== 8) {
        $response['message'] = 'Key must be 8 characters long.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id FROM public.worker_keys WHERE access_key = ? AND is_used = false");
            $stmt->execute([$worker_key]);

            if ($stmt->fetch()) {
                $response = ['status' => 'success', 'message' => 'Key is valid!'];
            } else {
                $response = ['status' => 'error', 'message' => 'This key is invalid or has already been used.'];
            }
        } catch (PDOException $e) {
            error_log("Key verification failed: " . $e->getMessage());
            $response['message'] = 'A database error occurred.';
        }
    }
}

echo json_encode($response);
?>
