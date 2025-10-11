<?php
// Start output buffering immediately to catch any stray output
ob_start();

// --- 1. SETUP & AUTH ---
// These files should handle database connection and user authentication.
// include_once __DIR__ . "/api/connect.php";
// include_once __DIR__ . "/api/header.php";

// --- MOCK DATA FOR DEMONSTRATION ---
// This section is for standalone testing. Replace with your actual database logic.
class MockPDO { public function prepare($query) { return new MockPDOStatement(); } }
class MockPDOStatement { public function execute($params) {} public function fetch($mode) {
    return [
        'id' => 30, 'created_at' => '2025-10-11 10:30:00', 'booking_time' => '2025-10-12 04:30:00',
        'final_cost' => 800.00, 'status' => 'completed', 'payment_status' => 'paid',
        'customer_name' => 'Fenil Pastagia', 'customer_addr1' => 'C-1/501, Sai Milan Residency',
        'customer_addr2' => 'Opposite jalaram international school, Palanpore',
        'customer_city' => 'Surat', 'customer_state' => 'Gujarat', 'customer_pincode' => '395009',
        'worker_name' => 'Veer Naik', 'worker_addr1' => 'G-90, Shital Residency',
        'worker_addr2' => 'Yogi chowk', 'worker_city' => 'Surat', 'worker_state' => 'Gujarat',
        'worker_pincode' => '395006',
        'service_details' => "Category: Vehicle Maintenance\nService: Bike\nItem: General Repair\nAddress: C-1/501, Sai Milan Residency, Palanpore"
    ];
} }
$conn = new MockPDO();
$userId = 1; // Mock user ID
// --- END MOCK DATA ---


// Composer autoload required for Dompdf
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    ob_end_clean();
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$bookingId = $_GET['id'];
$invoice = null;

// --- 2. LOAD LOGO FROM IMAGE FILE ---
$logoSrc = '';
$logoPath = __DIR__ . '/assets/images/dailyfix.png'; // Assumes script is in /dailyfix/ and image is in /dailyfix/assets/images/

if (file_exists($logoPath)) {
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
}

// --- 3. DATA FETCHING ---
try {
    // This is a representative query. Adjust to your actual schema.
    $stmt = $conn->prepare("
        SELECT * FROM bookings WHERE id = ?
    ");
    $stmt->execute([$bookingId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Invoice Data Error: " . $e->getMessage());
    ob_end_clean();
    die("Database error. Cannot generate invoice.");
}

if (!$invoice) {
    ob_end_clean();
    die("Invoice not found or you do not have permission to view it.");
}

// --- 4. DATE & TIME FORMATTING ---
$bookingTime = new DateTime($invoice['booking_time'], new DateTimeZone('UTC'));
$bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
$dateIssued = new DateTime($invoice['created_at']);
$dateIssued->setTimezone(new DateTimeZone('Asia/Kolkata'));

// --- 5. PARSE SERVICE DETAILS ---
$serviceDetails = explode("\n", $invoice['service_details']);
$serviceData = ['Category' => '', 'Service' => '', 'Item' => '', 'Address' => ''];
foreach ($serviceDetails as $line) {
    if (strpos($line, 'Category:') !== false) $serviceData['Category'] = trim(str_replace('Category:', '', $line));
    else if (strpos($line, 'Service:') !== false) $serviceData['Service'] = trim(str_replace('Service:', '', $line));
    else if (strpos($line, 'Item:') !== false) $serviceData['Item'] = trim(str_replace('Item:', '', $line));
    else if (strpos($line, 'Address:') !== false) $serviceData['Address'] = trim(str_replace('Address:', '', $line));
}

// --- 6. FORMAT CURRENCY ---
$finalCost = floatval($invoice['final_cost']);
$finalCostFormatted = number_format($finalCost, 2);

// --- 7. GENERATE DYNAMIC HTML INVOICE ---
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #' . htmlspecialchars($invoice['id']) . '</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            color: #374151; /* Gray-700 */
            margin: 0;
        }
        .invoice-wrapper {
            padding: 40px;
            padding-bottom: 100px; /* Space for the footer */
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            padding: 0;
            margin: 0;
            vertical-align: top;
        }
        .invoice-details h1 {
            font-size: 42px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }
        .invoice-details p {
            font-size: 12px;
            line-height: 1.6;
            margin: 2px 0 0 0;
            color: #6B7280; /* Gray-500 */
        }
        .status-paid {
            color: #16A34A; /* Green-600 */
            font-weight: bold;
        }
        .party-box h3 {
            font-size: 11px;
            font-weight: bold;
            color: #6B7280;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .party-box p {
            line-height: 1.6;
            margin: 0;
        }
        .items-table th {
            background-color: #F9FAFB; /* Gray-50 */
            padding: 12px 15px;
            border-bottom: 1px solid #E5E7EB; /* Gray-200 */
            font-size: 11px;
            text-transform: uppercase;
            text-align: left;
            color: #6B7280;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #E5E7EB;
        }
        .summary-table td {
            padding: 4px 0;
        }
        .total-paid {
            font-size: 22px !important;
            font-weight: bold;
            color: #16A34A;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px 40px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 11px;
            color: #9CA3AF; /* Gray-400 */
        }
    </style>
</head>
<body>
    <div class="footer">
        This is a computer-generated invoice from DailyFix. Thank you for using our services.
    </div>
    <main class="invoice-wrapper">
        <table style="margin-bottom: 40px;">
            <tr>
                <td style="vertical-align: middle;">
                    ' . ($logoSrc ? '<img src="' . $logoSrc . '" height="180" alt="DailyFix Logo" style="vertical-align: middle;">' : '') . '
                </td>
                <td class="invoice-details" style="text-align: right; vertical-align: middle;">
                    <h1>INVOICE</h1>
                    <p><strong>Invoice ID:</strong> #' . htmlspecialchars($invoice['id']) . '</p>
                    <p><strong>Date Issued:</strong> ' . $dateIssued->format('F d, Y') . '</p>
                    <p><strong>Status:</strong> <span class="status-paid">PAID</span></p>
                </td>
            </tr>
        </table>
        
        <table style="margin-bottom: 50px;">
            <tr>
                <td style="width: 50%;" class="party-box">
                    <h3>Billed To</h3>
                    <p><strong>' . htmlspecialchars($invoice['customer_name']) . '</strong></p>
                    <p>' . htmlspecialchars($invoice['customer_addr1']) . '</p>
                    <p>' . htmlspecialchars($invoice['customer_addr2']) . '</p>
                    <p>' . htmlspecialchars($invoice['customer_city']) . ', ' . htmlspecialchars($invoice['customer_state']) . ' - ' . htmlspecialchars($invoice['customer_pincode']) . '</p>
                </td>
                <td style="width: 50%; padding-left: 20px;" class="party-box">
                    <h3>Service Provider</h3>
                    <p><strong>' . htmlspecialchars($invoice['worker_name']) . '</strong></p>
                    <p>' . htmlspecialchars($invoice['worker_addr1']) . '</p>
                    <p>' . htmlspecialchars($invoice['worker_addr2']) . '</p>
                    <p>' . htmlspecialchars($invoice['worker_city']) . ', ' . htmlspecialchars($invoice['worker_state']) . ' - ' . htmlspecialchars($invoice['worker_pincode']) . '</p>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Service Description</th>
                    <th style="width: 25%;">Date & Time</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <p style="font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars($serviceData['Category']) . '</p>
                        <p style="font-weight: bold; color: #111827; margin-top: 4px;">' . htmlspecialchars($serviceData['Item']) . '</p>
                        <p style="font-size: 12px; color: #6B7280;">At: ' . htmlspecialchars($serviceData['Address']) . '</p>
                    </td>
                    <td>' . $bookingTime->format('D, M d, Y') . '</td>
                    <td style="text-align: right; font-weight: bold; color: #111827;">
                        ₹' . $finalCostFormatted . '
                    </td>
                </tr>
            </tbody>
        </table>
        
        <table class="summary-table">
            <tr>
                <td style="color: #6B7280;">Subtotal:</td>
                <td style="text-align: right;">' . $finalCostFormatted . '</td>
            </tr>
            <tr>
                <td style="color: #6B7280;">Taxes/Fees:</td>
                <td style="text-align: right;">' . number_format(0, 2) . '</td>
            </tr>
            <tr class="total-paid">
                <td style="padding: 20px 15px; background-color: #F9FAFB; border-top: 1px solid #E5E7EB;">
                    TOTAL PAID:
                    <p style="font-size: 11px; color: #6B7280; font-weight: normal; margin: 4px 0 0 0; padding: 0;">
                        (Payment Status: Completed / Paid)
                    </p>
                </td>
                <td style="padding: 20px 15px; background-color: #F9FAFB; border-top: 1px solid #E5E7EB; text-align: right; vertical-align: middle;">
                    ₹' . $finalCostFormatted . '
                </td>
            </tr>
        </table>
    </main>
</body>
</html>';

// --- 8. PDF GENERATION WITH DOMPDF ---
ob_end_clean();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
// Enabling remote images is required if you use a direct URL, but base64 encoding (used here) is more reliable.
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileName = 'DailyFix_Invoice_' . $invoice['id'] . '.pdf';
$dompdf->stream($fileName, ['Attachment' => false]);
exit;