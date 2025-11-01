<?php
ob_start();
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php"; // This gets the $userId

if (!isset($_POST['update_password'])) {
    header("Location: /dailyfix/profile.php#security");
    exit;
}

$currentPassword = $_POST['current_password'];
$newPassword = $_POST['new_password'];
$confirmPassword = $_POST['confirm_password'];

// --- Server-side Validation ---
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    header("Location: /dailyfix/profile.php?error=missing_fields#security");
    exit;
}

if ($newPassword !== $confirmPassword) {
    header("Location: /dailyfix/profile.php?error=password_mismatch#security");
    exit;
}

if (strlen($newPassword) < 8) {
    header("Location: /dailyfix/profile.php?error=password_weak#security");
    exit;
}

try {
    // --- Check Current Password ---
    $stmt = $conn->prepare("SELECT password_hash FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        header("Location: /dailyfix/profile.php?error=current_password_invalid#security");
        exit;
    }

    // --- Hash and Update New Password ---
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE public.users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $userId]);

    header("Location: /dailyfix/profile.php?success=password_updated#security");
    exit;

} catch (PDOException $e) {
    error_log("Password Update Error: " . $e->getMessage());
    header("Location: /dailyfix/profile.php?error=db_error#security");
    exit;
}

ob_end_flush();
?>