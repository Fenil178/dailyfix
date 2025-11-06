<?php
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

if ($role !== 'worker') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];

try {
    // Set work_completed_by_worker to true AND ensure status is 'in_progress' or 'confirmed' before proceeding.
    // We also forcefully set the status to 'in_progress' if it was merely 'confirmed'.
    $stmt = $conn->prepare(
        "UPDATE public.bookings 
        SET work_completed_by_worker = true, status = 'in_progress' 
        WHERE id = ? AND worker_id = ?
        AND status IN ('confirmed', 'in_progress')"
    );
    $stmt->execute([$booking_id, $userId]);

    if ($stmt->rowCount() > 0) {
        
        // --- NOTIFICATION LOGIC (FIXED) ---
        // <-- FIX: We MUST fetch the customer_id to notify them.
        $stmt_customer = $conn->prepare("SELECT customer_id FROM public.bookings WHERE id = ?");
        $stmt_customer->execute([$booking_id]);
        $booking_data = $stmt_customer->fetch(PDO::FETCH_ASSOC);

        if ($booking_data) {
            $customer_id_to_notify = $booking_data['customer_id'];
            
            include_once __DIR__ . "/notification_handler.php";
            // $userName is the worker's name (from user_session.php)
            $link = "booking-details.php?id=$booking_id";

            // 1. Notify Customer
            $message_for_customer = "$userName has marked booking as complete. Please proceed with payment.";
            // <-- FIX: Use fetched $customer_id_to_notify
            create_notification($conn, $customer_id_to_notify, $userId, $message_for_customer, $link);

            // 2. Notify Admin
            $message_for_admin = "Worker $userName completed job.";
            create_notification($conn, 'admin', $userId, $message_for_admin, $link);
        }
        // --- END NOTIFICATION ---
        
        echo json_encode(['status' => 'success', 'message' => 'Work marked as complete.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Job status prevents marking work as complete, or the job was not found.']);
    }
} catch (PDOException $e) {
    error_log("Mark work done error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>