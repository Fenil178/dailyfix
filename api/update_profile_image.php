<?php
ob_start();
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php"; // This gets the $userId
include_once __DIR__ . "/encryption.php"; // ======================= ADD THIS LINE =======================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /dailyfix/profile.php");
    exit;
}

if (!isset($_FILES['profile_avatar']) || $_FILES['profile_avatar']['error'] !== UPLOAD_ERR_OK) {
    header("Location: /dailyfix/profile.php?error=avatar_upload#details");
    exit;
}

// --- Configuration ---
$uploadDir = __DIR__ . "/../uploads/profile_images/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
$maxSize = 5 * 1024 * 1024; // 5 MB
$webPathPrefix = "uploads/profile_images/";

// --- File Validation ---
$file = $_FILES['profile_avatar'];
$fileType = mime_content_type($file['tmp_name']);
$fileSize = $file['size'];

if (!in_array($fileType, $allowedTypes)) {
    header("Location: /dailyfix/profile.php?error=file_type#details");
    exit;
}

if ($fileSize > $maxSize) {
    header("Location: /dailyfix/profile.php?error=file_size#details");
    exit;
}

// --- Create Upload Directory ---
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        error_log("Failed to create upload directory: " . $uploadDir);
        header("Location: /dailyfix/profile.php?error=avatar_upload#details");
        exit;
    }
}

// --- Generate Unique Filename ---
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeExtension = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']) ? strtolower($extension) : 'jpg';
$newFileName = "user_{$userId}_" . time() . "." . $safeExtension;
$fullPath = $uploadDir . $newFileName;
$dbPath = $webPathPrefix . $newFileName; // e.g., uploads/profile_images/user_1_1678886400.jpg

try {
    // --- Delete Old Avatar (if exists) ---
    $stmt = $conn->prepare("SELECT profile_image FROM public.users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldImagePath = $stmt->fetchColumn();

    if ($oldImagePath && strpos($oldImagePath, 'default-avatar.png') === false) {
        $oldFileFullPath = __DIR__ . "/../" . $oldImagePath; // Go up one level from /api/
        if (file_exists($oldFileFullPath)) {
            @unlink($oldFileFullPath);
        }
    }

    // --- Move New File & Update DB ---
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        $stmt = $conn->prepare("UPDATE public.users SET profile_image = ? WHERE id = ?");
        $stmt->execute([$dbPath, $userId]);
        
        // ======================= MODIFICATION START =======================
        // THE COOKIE FIX: Update the cookie with the new image path
        $encrypted_image_path = encrypt_id($dbPath);
        // Set cookie for 30 days, httponly, and accessible site-wide
        setcookie('encrypted_profile_image', $encrypted_image_path, time() + (86400 * 30), "/", "", false, true);
        // ======================= MODIFICATION END =========================

        header("Location: /dailyfix/profile.php?success=avatar_updated#details");
        exit;
    } else {
        throw new Exception("Could not move uploaded file.");
    }

} catch (Exception $e) {
    error_log("Avatar Upload Error: " . $e->getMessage());
    header("Location: /dailyfix/profile.php?error=avatar_upload#details");
    exit;
}

ob_end_flush();
?>