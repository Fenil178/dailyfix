<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php"; // Provides $userId and $role

// --- Security Checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /dailyfix/dashboard.php?error=invalid_request");
    exit;
}

if (!isset($userId) || $role !== 'worker') {
    header("Location: /dailyfix/login.php?error=unauthorized");
    exit;
}

// --- Data Sanitization ---
$experience_years = filter_input(INPUT_POST, 'experience_years', FILTER_VALIDATE_INT);
$hourly_rate = filter_input(INPUT_POST, 'hourly_rate', FILTER_VALIDATE_FLOAT);
$bio = trim($_POST['bio'] ?? '');
$selected_services = $_POST['services'] ?? []; // This will be an array of IDs

// --- Database Operation ---
try {
    $conn->beginTransaction();

    // 1. Update the worker_profiles table
    $stmt_update = $conn->prepare(
        "UPDATE public.worker_profiles 
         SET experience_years = ?, hourly_rate = ?, bio = ?
         WHERE user_id = ?"
    );
    $stmt_update->execute([$experience_years, $hourly_rate, $bio, $userId]);

    // 2. Delete all existing services for this worker to prevent duplicates
    $stmt_delete = $conn->prepare("DELETE FROM public.worker_services WHERE user_id = ?");
    $stmt_delete->execute([$userId]);

    // 3. Insert the new set of selected services
    if (!empty($selected_services)) {
        $stmt_insert = $conn->prepare("INSERT INTO public.worker_services (user_id, sub_service_id) VALUES (?, ?)");
        foreach ($selected_services as $service_id) {
            if (filter_var($service_id, FILTER_VALIDATE_INT)) {
                $stmt_insert->execute([$userId, $service_id]);
            }
        }
    }

    $conn->commit();

    // Redirect to the main dashboard with a success message
    header("Location: /dailyfix/dashboard.php?success=profile_setup_complete");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Worker profile setup failed: " . $e->getMessage());
    header("Location: /dailyfix/worker/setup.php?error=database_error");
    exit;
}