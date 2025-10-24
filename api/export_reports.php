<?php
include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php";

// 1. Authorization Check
if (!isset($userId)) {
    die('Unauthorized access.');
}

// 2. Input Validation (Same as get_detailed_reports.php)
$time_filter = $_GET['filter'] ?? '1month';
$current_role = $_GET['role'] ?? $role;

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
    $filename_prefix = 'Customer_Report';
} elseif ($current_role === 'worker' || ($current_role !== 'admin' && $role === 'worker')) {
    $where_clauses[] = "b.worker_id = ?";
    $bindings[] = $userId;
    $filename_prefix = 'Worker_Report';
} elseif ($current_role === 'admin') {
    $filename_prefix = 'Admin_Global_Report';
} else {
    die("Invalid role context for export.");
}

// Construct the main query (Same as get_detailed_reports.php)
$sql = "
    SELECT 
        b.id AS booking_id,
        b.booking_time,
        cust.full_name AS customer_name,
        work.full_name AS worker_name,
        b.service_details,
        b.final_cost,
        b.status,
        b.payment_status,
        b.cancellation_reason,
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
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- CSV Output Headers ---
    $filename = $filename_prefix . "_" . $time_filter . "_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');

    if (!empty($results)) {
        // Output header row
        $headers = array_keys($results[0]);
        // Clean up column names for readability
        $display_headers = array_map(function($header) {
            return ucwords(str_replace('_', ' ', $header));
        }, $headers);
        fputcsv($output, $display_headers);

        // Output data rows
        foreach ($results as $row) {
            $data_row = [];
            foreach ($headers as $header) {
                $value = $row[$header];
                // Clean up service details for CSV
                if ($header === 'service_details') {
                    $value = str_replace(["\n", "\r"], ", ", $value);
                }
                $data_row[] = $value;
            }
            fputcsv($output, $data_row);
        }
    } else {
        fputcsv($output, ["No data available for the selected filter."]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("Report export failed: " . $e->getMessage());
    http_response_code(500);
    echo "Database Error during export.";
}
?>