<?php
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

// Ensure the admin's ID is stored in the session when they log in.
// We assume it's stored in $_SESSION['admin_id']. Adjust if your variable is different.
$current_admin_id = $_SESSION['admin_id'] ?? 0;

if (!isset($_GET['action']) || !isset($_GET['admin_id'])) {
    header("Location: ../manage_admins.php?status=error");
    exit();
}

$action = $_GET['action'];
$admin_id_to_delete = (int)$_GET['admin_id'];
$status = 'error';

// *** CRITICAL SAFETY CHECK: Prevent self-deletion ***
if ($admin_id_to_delete === $current_admin_id) {
    header("Location: ../manage_admins.php?status=self_delete_error");
    exit();
}

try {
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM public.users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$admin_id_to_delete]);
        $status = 'deleted';
    }
} catch (PDOException $e) {
    error_log("Admin Action Error: " . $e->getMessage());
    $status = 'error';
}

header("Location: ../manage_admins.php?status=" . $status);
exit();
?>