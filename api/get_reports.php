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

// 2. Input Validation
$time_filter = $_GET['filter'] ?? '1month';
$current_role = $role; // Get role from user_session.php

// Sanitize and define date range based on filter
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
    default: 
        // Default to 1 month if filter is invalid
        $time_filter = '1month';
        $start_date->modify('-1 month');
        break;
}

$start_date_db = $start_date->format('Y-m-d H:i:s');
$end_date_db = $end_date->format('Y-m-d H:i:s');

// --- 3. Dynamic SQL Query Generation ---
$report_data = [];
$bindings = [];

try {
    // Base WHERE clause for time range
    $time_where = "WHERE b.booking_time BETWEEN ? AND ?";
    $bindings = [$start_date_db, $end_date_db];

    if ($current_role === 'customer') {
        $time_where .= " AND b.customer_id = ?";
        $bindings[] = $userId;
    } elseif ($current_role === 'worker') {
        $time_where .= " AND b.worker_id = ?";
        $bindings[] = $userId;
    }

    $sql = "
        SELECT 
            b.status,
            COUNT(b.id) AS count,
            COALESCE(SUM(b.final_cost), 0) AS total_cost
        FROM public.bookings b
        " . $time_where . "
        GROUP BY b.status
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($bindings);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results into a structured array
    $report_data['summary'] = [
        'total_bookings' => 0,
        'total_revenue' => 0.00,
        'completed' => 0,
        'cancelled' => 0
    ];
    
    foreach ($results as $row) {
        $status = $row['status'];
        $count = (int)$row['count'];
        $cost = (float)$row['total_cost'];
        
        $report_data['summary']['total_bookings'] += $count;
        
        // Revenue calculation changes based on role
        if ($current_role === 'customer') {
             // Customer view: Total spent is sum of costs for completed/paid bookings
            if (in_array($status, ['completed'])) { 
                $report_data['summary']['total_revenue'] += $cost;
            }
        } elseif (in_array($current_role, ['worker', 'admin'])) {
             // Worker/Admin view: Total earned/revenue is sum of all costs for completed bookings
             if (in_array($status, ['completed'])) { 
                 $report_data['summary']['total_revenue'] += $cost;
             }
        }
        
        if (in_array($status, ['completed'])) {
            $report_data['summary']['completed'] += $count;
        } elseif (in_array($status, ['cancelled'])) {
            $report_data['summary']['cancelled'] += $count;
        }
        $report_data[$status] = $count;
    }

    $report_data['filter_dates'] = [
        'start' => $start_date->format('Y-m-d'),
        'end' => $end_date->format('Y-m-d'),
        'filter_label' => ucfirst($time_filter)
    ];

    echo json_encode(['status' => 'success', 'data' => $report_data]);
    
} catch (PDOException $e) {
    error_log("Report fetch failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred while fetching reports.']);
}
?>