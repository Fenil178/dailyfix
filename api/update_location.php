<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $address_line1 = $_POST['address_line1'] ?? null;
    $address_line2 = $_POST['address_line2'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $pincode = $_POST['pincode'] ?? null;

    try {
        $stmt = $conn->prepare(
            "UPDATE public.users SET 
                latitude = ?, 
                longitude = ?, 
                address_line1 = ?, 
                address_line2 = ?, 
                city = ?, 
                state = ?, 
                pincode = ? 
            WHERE id = ?"
        );
        $stmt->execute([
            $latitude,
            $longitude,
            $address_line1,
            $address_line2,
            $city,
            $state,
            $pincode,
            $userId
        ]);

        header("Location: /dailyfix/profile.php?success=location_updated");
    } catch (PDOException $e) {
        error_log("Location update failed: " . $e->getMessage());
        header("Location: /dailyfix/profile.php?error=update_failed");
    }
} else {
    header("Location: /dailyfix/profile.php");
}
exit;