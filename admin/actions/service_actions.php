<?php
// This file checks if the user is an admin and has a valid session.
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];

    try {
        // First, check if any sub-services are linked to this service
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM public.sub_services WHERE service_id = ?");
        $checkStmt->execute([$service_id]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            // Cannot delete because it has children
            header("Location: ../manage_services.php?status=delete_failed");
            exit();
        }

        // No sub-services, safe to delete
        $deleteStmt = $conn->prepare("DELETE FROM public.services WHERE id = ?");
        $deleteStmt->execute([$service_id]);

        if ($deleteStmt->rowCount() > 0) {
            header("Location: ../manage_services.php?status=deleted");
        } else {
            header("Location: ../manage_services.php?status=error");
        }
    } catch (PDOException $e) {
        error_log("Service Delete Error: " . $e->getMessage());
        header("Location: ../manage_services.php?status=error");
    }
} else {
    header("Location: ../manage_services.php");
}
exit();
?>