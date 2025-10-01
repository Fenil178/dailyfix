<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files for database connection and session
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php";

$response = ['status' => 'error', 'message' => 'Invalid request.', 'slots' => [], 'booked' => []];

// Check for required parameters
if (!isset($_GET['date'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing date parameter.']);
    exit;
}

// Determine target worker ID based on role
$target_worker_id = null;

if ($role === 'admin' && isset($_GET['worker_id'])) {
    // Admin can view any worker's availability
    $target_worker_id = filter_var($_GET['worker_id'], FILTER_VALIDATE_INT);
} else if ($role === 'customer' && isset($_GET['worker_id'])) {
    // Customer can only request a worker's availability
    $target_worker_id = filter_var($_GET['worker_id'], FILTER_VALIDATE_INT);
} else if ($role === 'worker') {
    // Worker can request their own availability
    $target_worker_id = $userId;
}

if (!$target_worker_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access or missing worker ID.']);
    exit;
}

$date = $_GET['date'];

try {
    // Fetch available time slots for the specific worker and date
    $stmt = $conn->prepare("SELECT time_slot FROM public.worker_availability WHERE user_id = ? AND date = ? ORDER BY time_slot ASC");
    $stmt->execute([$target_worker_id, $date]);
    $slots = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Fetch booked time slots for the specific worker and date
    $stmt_booked = $conn->prepare("SELECT booking_time FROM public.bookings WHERE worker_id = ? AND booking_time::date = ? AND status IN ('confirmed', 'in_progress')");
    $stmt_booked->execute([$target_worker_id, $date]);
    $booked_slots_raw = $stmt_booked->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Convert UTC booked times to the local timezone (Asia/Kolkata)
    $booked_slots = array_map(function($time) {
        $utc_time = new DateTime($time, new DateTimeZone('UTC'));
        $utc_time->setTimezone(new DateTimeZone('Asia/Kolkata'));
        return $utc_time->format('H:i:s');
    }, $booked_slots_raw);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'slots' => $slots, 'booked' => $booked_slots]);

} catch (PDOException $e) {
    error_log("Failed to fetch availability: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
}
?>