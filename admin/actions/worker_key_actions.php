<?php
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (!isset($_GET['action']) || !isset($_GET['key_id'])) {
    header("Location: ../manage_worker_keys.php?status=error");
    exit();
}

$action = $_GET['action'];
$key_id = (int)$_GET['key_id'];
$status = 'error';

try {
    if ($action === 'delete') {
        // Soft delete by setting the deleted_at timestamp
        $stmt = $conn->prepare("UPDATE public.worker_keys SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$key_id]);
        if ($stmt->rowCount() > 0) {
            $status = 'deleted';
        }
    } elseif ($action === 'toggle_status') {
        $stmt = $conn->prepare("SELECT status FROM public.worker_keys WHERE id = ?");
        $stmt->execute([$key_id]);
        $current_status = $stmt->fetchColumn();

        if ($current_status) {
            $new_status = ($current_status === 'active') ? 'suspended' : 'active';
            $stmt = $conn->prepare("UPDATE public.worker_keys SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $key_id]);
            $status = 'updated';
        }
    }
} catch (PDOException $e) {
    error_log("Worker Key Action Error: " . $e->getMessage());
    $status = 'error';
}

header("Location: ../manage_worker_keys.php?status=" . $status);
exit();
?>