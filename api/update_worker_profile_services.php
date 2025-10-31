<?php
// Set up error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId and $role

// --- Security Checks ---

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /dailyfix/profile.php?error=invalid_request#services");
    exit;
}

// Ensure the user is a logged-in worker
if (!isset($userId) || $role !== 'worker') {
    header("Location: /dailyfix/login.php?error=unauthorized");
    exit;
}

// --- Database Operation ---

// Get the arrays of selected IDs from the form.
$selected_sub_services = $_POST['services'] ?? [];
$prices = $_POST['prices'] ?? [];
$selected_sub_service_items = $_POST['sub_service_items'] ?? [];

try {
    // Use a transaction to ensure data integrity.
    $conn->beginTransaction();

    // 1. Delete all existing sub-services for this worker.
    $stmt_delete_services = $conn->prepare("DELETE FROM public.worker_services WHERE user_id = ?");
    $stmt_delete_services->execute([$userId]);

    // 2. Insert the new set of selected sub-services.
    if (!empty($selected_sub_services)) {
        $stmt_insert_services = $conn->prepare("INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)");
        foreach ($selected_sub_services as $service_id) {
            $sanitized_service_id = filter_var($service_id, FILTER_VALIDATE_INT);
            if ($sanitized_service_id) {
                $stmt_insert_services->execute([$userId, $sanitized_service_id]);
            }
        }
    }

    // 3. Delete all existing sub-service items for this worker.
    $stmt_delete_items = $conn->prepare("DELETE FROM public.worker_sub_service_items WHERE user_id = ?");
    $stmt_delete_items->execute([$userId]);

    // 4. Insert the new set of selected sub-service items with their prices.
    if (!empty($selected_sub_service_items)) {
        $stmt_insert_items = $conn->prepare("INSERT INTO public.worker_sub_service_items (user_id, sub_service_item_id, price) VALUES (?, ?, ?)");
        
        foreach ($selected_sub_service_items as $item_id) {
            $sanitized_item_id = filter_var($item_id, FILTER_VALIDATE_INT);
            if ($sanitized_item_id) {
                $price = isset($prices[$sanitized_item_id]) ? filter_var($prices[$sanitized_item_id], FILTER_VALIDATE_FLOAT) : 0.00;
                $stmt_insert_items->execute([$userId, $sanitized_item_id, $price]);
            }
        }
    }

    // If everything was successful, commit the changes to the database.
    $conn->commit();

    // Redirect back to the profile page with a success message.
    header("Location: /dailyfix/profile.php?success=services_updated#services");
    exit;

} catch (PDOException $e) {
    // If any part of the transaction fails, roll back all changes.
    $conn->rollBack();

    // Log the detailed error for the administrator.
    error_log("Worker profile services update failed: " . $e->getMessage());
    
    // Redirect back with a user-friendly error message.
    header("Location: /dailyfix/profile.php?error=update_failed#services");
    exit;
}
?>