<?php
include_once __DIR__ . '/../includes/auth_check.php';
include_once __DIR__ . '/../../api/connect.php';

if (!isset($_GET['action']) || !isset($_GET['booking_id'])) {
    header("Location: ../view_bookings.php?status=error");
    exit();
}

$action = $_GET['action'];
$booking_id = (int)$_GET['booking_id'];
$status = 'error';

try {
    if ($action === 'cancel') {
        // You can add more logic here, e.g., only cancel if status is 'pending' or 'confirmed'
        $stmt = $conn->prepare("UPDATE public.bookings SET status = 'cancelled' WHERE id = ? AND status NOT IN ('completed', 'cancelled')");
        $stmt->execute([$booking_id]);
        
        if ($stmt->rowCount() > 0) {
            $status = 'cancelled';
        } else {
            $status = 'already_processed';
        }
    }
} catch (PDOException $e) {
    error_log("Booking Action Error: " . $e->getMessage());
    $status = 'error';
}

header("Location: ../view_bookings.php?status=" . $status);
exit();
?>