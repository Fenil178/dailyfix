<?php
// Set the content type to JSON for API-like behavior
header('Content-Type: application/json');
include_once __DIR__ . '/connect.php';

$response = ['success' => false, 'data' => null];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                b.id, 
                b.service_details, 
                b.booking_time, 
                b.status, 
                b.created_at,
                b.final_cost,
                c.full_name as customer_name, 
                c.email as customer_email,
                c.phone as customer_phone,
                w.full_name as worker_name,
                w.email as worker_email,
                w.phone as worker_phone
            FROM public.bookings b
            JOIN public.users c ON b.customer_id = c.id
            JOIN public.users w ON b.worker_id = w.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            // FIX: Convert the UTC booking_time to the local timezone (Asia/Kolkata)
            $utc_time = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
            $utc_time->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $booking['booking_time_local'] = $utc_time->format('Y-m-d H:i:s');
            
            $response['success'] = true;
            $response['data'] = $booking;
        }
    } catch (PDOException $e) {
        error_log("Get Booking Details Error: " . $e->getMessage());
    }
}

echo json_encode($response);
exit();
?>