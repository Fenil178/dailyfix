<?php
// This file checks if the user is an admin and has a valid session.
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $sub_service_id = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM public.sub_services WHERE id = ?");
        $stmt->execute([$sub_service_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../manage_sub_services.php?status=deleted");
        } else {
            header("Location: ../manage_sub_services.php?status=error");
        }
    } catch (PDOException $e) {
        error_log("Sub-Service Delete Error: " . $e->getMessage());
        header("Location: ../manage_sub_services.php?status=error");
    }
} else {
    header("Location: ../manage_sub_services.php");
}
exit();
?>