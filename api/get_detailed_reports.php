<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php";

// 1. Authorization Check
if (!isset($userId)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// 2. Input Validation (Use GET parameters for filtering)
$time_filter = $_GET['filter'] ?? '1month';
$current_role = $_GET['role'] ?? $role; // Allows admin to filter, defaults to logged-in role

// Define date range based on filter
$end_date = new DateTime('now', new DateTimeZone('UTC'));
$start_date = clone $end_date;

switch ($time_filter) {
    case 'daily': $start_date->modify('-1 day'); break;
    case '7days': $start_date->modify('-7 days'); break;
    case '1month': $start_date->modify('-1 month'); break;
    case '3months': $start_date->modify('-3 months'); break;
    case '6months': $start_date->modify('-6 months'); break;
    case '1year': $start_date->modify('-1 year'); break;
    case '2years': $start_date->modify('-2 years'); break;
    default: $start_date->modify('-1 month'); break;
}

$start_date_db = $start_date->format('Y-m-d H:i:s');
$end_date_db = $end_date->format('Y-m-d H:i:s');

// --- 3. Dynamic SQL Query Generation ---
$bindings = [$start_date_db, $end_date_db];
$where_clauses = ["b.booking_time BETWEEN ? AND ?"];

// Define role-based filtering
if ($current_role === 'customer' || ($current_role !== 'admin' && $role === 'customer')) {
    $where_clauses[] = "b.customer_id = ?";
    $bindings[] = $userId;
} elseif ($current_role === 'worker' || ($current_role !== 'admin' && $role === 'worker')) {
    $where_clauses[] = "b.worker_id = ?";
    $bindings[] = $userId;
}
// Note: If $current_role is 'admin' and no user filter is applied, it fetches all data.

// Construct the main query
$sql = "
    SELECT 
        b.id AS booking_id,
        b.booking_time,
        b.status,
        b.final_cost,
        b.payment_status,
        b.service_details,
        b.cancellation_reason,
        cust.full_name AS customer_name,
        work.full_name AS worker_name,
        r.rating,
        r.comment AS review_comment
    FROM public.bookings b
    JOIN public.users cust ON b.customer_id = cust.id
    JOIN public.users work ON b.worker_id = work.id
    LEFT JOIN public.reviews r ON b.id = r.booking_id
    WHERE " . implode(' AND ', $where_clauses) . "
    ORDER BY b.booking_time DESC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($bindings);
    $detailed_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $detailed_report]);
    
} catch (PDOException $e) {
    error_log("Detailed report fetch failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred while fetching detailed reports.']);
}
?>