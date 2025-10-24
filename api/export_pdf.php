<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include_once __DIR__ . "/connect.php"; 
include_once __DIR__ . "/user_session.php";

// 1. Authorization Check
if (!isset($userId)) {
    die('Unauthorized access.');
}

// 2. Input Validation (Use GET parameters for filtering)
$time_filter = $_GET['filter'] ?? '1month';
$current_role = $_GET['role'] ?? $role;

// Define date range based on filter (This logic needs to be copied from get_detailed_reports.php)
$end_date = new DateTime('now', new DateTimeZone('UTC'));
$start_date = clone $end_date;

switch ($time_filter) {
    case 'daily': $range_name = 'Last 24 Hours'; $start_date->modify('-1 day'); break;
    case '7days': $range_name = 'Last 7 Days'; $start_date->modify('-7 days'); break;
    case '1month': $range_name = 'Last 1 Month'; $start_date->modify('-1 month'); break;
    case '3months': $range_name = 'Last 3 Months'; $start_date->modify('-3 months'); break;
    case '6months': $range_name = 'Last 6 Months'; $start_date->modify('-6 months'); break;
    case '1year': $range_name = 'Last 1 Year'; $start_date->modify('-1 year'); break;
    case '2years': $range_name = 'Last 2 Years'; $start_date->modify('-2 years'); break;
    default: $range_name = 'Last 1 Month'; $start_date->modify('-1 month'); break;
}

$start_date_db = $start_date->format('Y-m-d H:i:s');
$end_date_db = $end_date->format('Y-m-d H:i:s');

// === Fetch Data (Reusing logic from get_detailed_reports.php) ===
$bindings = [$start_date_db, $end_date_db];
$where_clauses = ["b.booking_time BETWEEN ? AND ?"];

if ($current_role === 'customer' || ($current_role !== 'admin' && $role === 'customer')) {
    $where_clauses[] = "b.customer_id = ?";
    $bindings[] = $userId;
    $report_title = "Customer Spending and Job History";
    $filename_prefix = 'Customer_Report';
} elseif ($current_role === 'worker' || ($current_role !== 'admin' && $role === 'worker')) {
    $where_clauses[] = "b.worker_id = ?";
    $bindings[] = $userId;
    $report_title = "Worker Earnings and Job History";
    $filename_prefix = 'Worker_Report';
} elseif ($current_role === 'admin') {
    $report_title = "Platform Global Report";
    $filename_prefix = 'Admin_Global_Report';
} else {
    die("Invalid role context for export.");
}

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

} catch (PDOException $e) {
    error_log("PDF Report fetch failed: " . $e->getMessage());
    die("Database Error during PDF export.");
}

// === 3. PDF Generation with dompdf ===

// HTML Content Start
$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1, h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .status-completed { color: green; }
        .status-cancelled { color: red; }
        .status-pending { color: orange; }
        .status-confirmed { color: blue; }
        .review-rating { font-weight: bold; }
    </style>
</head>
<body>
    <h1>DailyFix Detailed Report</h1>
    <h2>' . htmlspecialchars($report_title) . '</h2>
    <p><strong>Time Range:</strong> ' . htmlspecialchars($range_name) . ' (' . $start_date->format('Y-m-d') . ' to ' . $end_date->format('Y-m-d') . ')</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date/Time</th>
                <th>Customer</th>
                <th>Worker</th>
                <th>Cost (₹)</th>
                <th>Status</th>
                <th>Review Rating</th>
            </tr>
        </thead>
        <tbody>';

// Populate Table Rows
if (count($results) > 0) {
    foreach ($results as $row) {
        // Clean up data for PDF display
        $status_class = 'status-' . strtolower($row['status']);
        $cost = number_format($row['final_cost'], 2);
        $review_rating = $row['rating'] ? $row['rating'] . ' stars' : 'N/A';
        $worker_name = ($current_role === 'customer') ? htmlspecialchars($row['worker_name']) : 'N/A';
        $customer_name = ($current_role === 'worker') ? htmlspecialchars($row['customer_name']) : 'N/A';
        $customer_name = ($current_role === 'admin') ? htmlspecialchars($row['customer_name']) : $customer_name;
        $worker_name = ($current_role === 'admin') ? htmlspecialchars($row['worker_name']) : $worker_name;
        
        // Ensure only relevant names are displayed based on role
        if ($current_role === 'customer') {
             $customer_name = 'You';
        } elseif ($current_role === 'worker') {
             $worker_name = 'You';
        }
        
        $html .= '
            <tr>
                <td>' . $row['booking_id'] . '</td>
                <td>' . (new DateTime($row['booking_time']))->setTimezone(new DateTimeZone('Asia/Kolkata'))->format('Y-m-d H:i A') . '</td>
                <td>' . $customer_name . '</td>
                <td>' . $worker_name . '</td>
                <td>' . $cost . '</td>
                <td class="' . $status_class . '">' . ucfirst($row['status']) . '</td>
                <td class="review-rating">' . $review_rating . '</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="7" style="text-align: center;">No detailed data found for this period.</td></tr>';
}


$html .= '
        </tbody>
    </table>
</body>
</html>';

// Initialize Dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Landscape is often better for detailed tables
$dompdf->render();

// Output the generated PDF
$filename = $filename_prefix . "_" . $time_filter . "_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]); // true forces download
exit;