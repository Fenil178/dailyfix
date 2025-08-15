<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php"; // Provides $userId and $role

// --- Security Checks ---

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /dailyfix/dashboard.php?error=invalid_request");
    exit;
}

// Ensure the user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    header("Location: /dailyfix/login.php?error=unauthorized");
    exit;
}

// --- Database Operation ---

// Get the array of selected service IDs from the form.
// If no checkboxes were checked, it will be an empty array.
$selected_services = $_POST['services'] ?? [];

try {
    // Use a transaction to ensure data integrity.
    // This means all database changes will succeed, or none will.
    $conn->beginTransaction();

    // 1. Delete all existing services for this worker.
    // This is the simplest way to sync their choices.
    $stmt_delete = $conn->prepare("DELETE FROM public.worker_services WHERE user_id = ?");
    $stmt_delete->execute([$userId]);

    // 2. Insert the new set of selected services.
    if (!empty($selected_services)) {
        $stmt_insert = $conn->prepare("INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)");
        
        foreach ($selected_services as $service_id) {
            // Sanitize to ensure it's an integer before inserting
            $sanitized_service_id = filter_var($service_id, FILTER_VALIDATE_INT);
            if ($sanitized_service_id) {
                $stmt_insert->execute([$userId, $sanitized_service_id]);
            }
        }
    }

    // If everything was successful, commit the changes to the database.
    $conn->commit();

    // Redirect back to the profile page with a success message.
    header("Location: /dailyfix/worker/profile.php?success=services_updated");
    exit;

} catch (PDOException $e) {
    // If any part of the transaction fails, roll back all changes.
    $conn->rollBack();

    // Log the detailed error for the administrator.
    error_log("Worker services update failed: " . $e->getMessage());
    
    // Redirect back with a user-friendly error message.
    header("Location: /dailyfix/worker/profile.php?error=update_failed");
    exit;
}