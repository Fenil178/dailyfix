<?php
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM public.sub_service_items WHERE id = ?");
        $stmt->execute([$item_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../manage_sub_service_items.php?status=deleted");
        } else {
            header("Location: ../manage_sub_service_items.php?status=error");
        }
    } catch (PDOException $e) {
        error_log("Service Item Delete Error: " . $e->getMessage());
        header("Location: ../manage_sub_service_items.php?status=error");
    }
} else {
    header("Location: ../manage_sub_service_items.php");
}
exit();
?>