<?php
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (!isset($_GET['action']) || !isset($_GET['user_id'])) {
    header("Location: ../manage_users.php?status=error");
    exit();
}

$action = $_GET['action'];
$user_id = (int)$_GET['user_id'];
$status = 'error';

try {
    // FIXED: The query now also selects 'account_status'
    $stmt = $conn->prepare("SELECT role, account_status FROM public.users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] !== 'admin') {
        switch ($action) {
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM public.users WHERE id = ?");
                $stmt->execute([$user_id]);
                $status = 'deleted';
                break;

            case 'toggle_status':
                // This logic will now work correctly
                $new_status = ($user['account_status'] === 'active') ? 'suspended' : 'active';
                $stmt = $conn->prepare("UPDATE public.users SET account_status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                $status = 'updated';
                break;
        }
    } else {
        $status = 'unauthorized';
    }
} catch (PDOException $e) {
    error_log("User Action Error: " . $e->getMessage());
    $status = 'error';
}

header("Location: ../manage_users.php?status=" . $status);
exit();
?>