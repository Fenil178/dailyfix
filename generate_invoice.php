<?php
// Start output buffering immediately
ob_start();

// --- 1. SETUP & AUTH ---
 include_once __DIR__ . "/api/connect.php"; // Use REAL connection
 include_once __DIR__ . "/api/header.php";  // Use REAL auth (ensure $userId is set)

// Composer autoload required for Dompdf
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    ob_end_clean(); // Clean buffer before redirecting
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$bookingId = (int)$_GET['id']; // Cast to integer
$invoice = null;

// --- 2. LOAD LOGO ---
$logoSrc = '';
$logoPath = __DIR__ . '/assets/images/DailyFix.png'; // Corrected path assumption
if (file_exists($logoPath)) {
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoSrc = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
} else {
    error_log("Logo file not found at: " . $logoPath); // Log if logo is missing
}


// --- 3. DATA FETCHING ---
try {
    // --- Fetch booking, discount, user details and ensure user has access ---
    $stmt = $conn->prepare("
        SELECT
            b.id, b.created_at, b.booking_time, b.final_cost, b.discount_amount, b.status, b.payment_status, b.service_details,
            c.full_name AS customer_name, c.address_line1 AS customer_addr1, c.address_line2 AS customer_addr2, c.city AS customer_city, c.state AS customer_state, c.pincode AS customer_pincode,
            w.full_name AS worker_name, w.address_line1 AS worker_addr1, w.address_line2 AS worker_addr2, w.city AS worker_city, w.state AS worker_state, w.pincode AS worker_pincode
        FROM public.bookings b
        JOIN public.users c ON b.customer_id = c.id
        JOIN public.users w ON b.worker_id = w.id
        WHERE b.id = ? AND (b.customer_id = ? OR b.worker_id = ?) -- Check ownership
    ");
    $stmt->execute([$bookingId, $userId, $userId]); // Use the actual logged-in user ID
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) { // Changed to PDOException
    error_log("Invoice Data PDO Error: " . $e->getMessage());
    ob_end_clean();
    die("Database error. Cannot generate invoice.");
} catch (Exception $e) { // Catch general exceptions
    error_log("Invoice Data Error: " . $e->getMessage());
    ob_end_clean();
    die("An error occurred. Cannot generate invoice.");
}


if (!$invoice || $invoice['payment_status'] !== 'paid') { // Ensure invoice exists AND is paid
    ob_end_clean();
    die("Invoice not found, not paid, or you do not have permission to view it.");
}

// --- 4. DATE & TIME FORMATTING ---
$bookingTime = new DateTime($invoice['booking_time'], new DateTimeZone('UTC'));
$bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
$dateIssued = new DateTime($invoice['created_at']); // Assuming creation date is issue date
$dateIssued->setTimezone(new DateTimeZone('Asia/Kolkata'));

// --- 5. PARSE SERVICE DETAILS ---
$serviceDetails = explode("\n", $invoice['service_details'] ?? '');
$serviceData = ['Service' => 'N/A', 'Item' => 'N/A', 'Address' => 'N/A']; // Initialize
foreach ($serviceDetails as $line) {
    if (strpos($line, 'Service:') !== false) $serviceData['Service'] = trim(str_replace('Service:', '', $line));
    else if (strpos($line, 'Item:') !== false) $serviceData['Item'] = trim(str_replace('Item:', '', $line));
    else if (strpos($line, 'Address:') !== false) $serviceData['Address'] = trim(str_replace('Address:', '', $line));
}


// --- 6. CALCULATE & FORMAT CURRENCY ---
$originalCost = (float)($invoice['final_cost'] ?? 0.00);
$discountAmount = (float)($invoice['discount_amount'] ?? 0.00);
$totalPaid = max(0, $originalCost - $discountAmount); // Calculate actual paid amount

$originalCostFormatted = number_format($originalCost, 2);
$discountAmountFormatted = number_format($discountAmount, 2);
$totalPaidFormatted = number_format($totalPaid, 2);

// --- 7. GENERATE DYNAMIC HTML INVOICE ---
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #' . htmlspecialchars($invoice['id']) . '</title>
    <style>
        @page { 
        margin: 0;
        }

        body {
        font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif; 
        font-size: 13px; color: #374151; margin: 0; 
        }

        .invoice-wrapper { 
        padding: 40px; 
        padding-bottom: 100px; 
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

        .header-table td {
        vertical-align: middle; 
        }

        .logo-container img {
            max-height: 150px; 
            width: auto;
            display: block; 
        }

        .invoice-details { 
        text-align: right; 
        } 

        .invoice-details h1 { 
        font-size: 42px; font-weight: bold; color: #111827; margin: 0 0 10px 0;
         }

        .invoice-details p { 
        font-size: 12px; line-height: 1.6; margin: 2px 0 0 0; color: #6B7280; 
        }

        .status-paid { 
        color: #16A34A;
        font-weight: bold; 
        }

        .party-box { 
        width: 50%; 
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
        margin: 0 0 2px 0;
        font-size: 13px; 
         }

        .items-table th { 
        background-color: #F9FAFB;
        padding: 12px 15px;
        border-bottom: 1px solid #E5E7EB;
        font-size: 11px;
        text-transform: uppercase;
        text-align: left;
        color: #6B7280;
        letter-spacing: 0.5px;
        }

        .items-table td { 
        padding: 15px; border-bottom: 1px solid #E5E7EB;
         }

        .summary-table td { 
        padding: 6px 0; font-size: 13px;
         }

        .summary-table .label {
        color: #6B7280;
        text-align: right;
        padding-right: 15px;
        width: 70%;
        }

        .summary-table .value {
         text-align: right;
        font-weight: 500;
        color: #111827;
        width: 30%; 
        }

        .total-row td {
        border-top: 2px solid #E5E7EB;
        padding-top: 15px !important;
        }

        .total-paid {
         font-size: 18px !important;
         font-weight: bold; color: #16A34A;
        }

        .footer { 
        position: fixed; 
        bottom: 0; left: 0;
        right: 0; 
        padding: 30px 40px; 
        border-top: 1px solid #E5E7EB; 
        text-align: center; 
        font-size: 11px; 
        color: #9CA3AF; 
        }

    </style>
</head>
<body>
    <div class="footer">
        This is a computer-generated invoice from DailyFix. Thank you for using our services. | Generated on: ' . date('Y-m-d H:i:s T') . '
    </div>
    <main class="invoice-wrapper">
        <table class="header-table" style="margin-bottom: 40px;">
            <tr>
                <td style="width: 50%;" class="logo-container">
                    ' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="DailyFix Logo">' : '<h2>DailyFix</h2>') . '
                </td>
                <td style="width: 50%;" class="invoice-details">
                    <h1>INVOICE</h1>
                    <p><strong>Invoice ID:</strong> #' . htmlspecialchars($invoice['id']) . '</p>
                    <p><strong>Date Issued:</strong> ' . $dateIssued->format('M d, Y') . '</p>
                    <p><strong>Service Date:</strong> ' . $bookingTime->format('M d, Y') . '</p>
                    <p><strong>Status:</strong> <span class="status-paid">PAID</span></p>
                </td>
            </tr>
        </table>

        <table style="margin-bottom: 50px;">
            <tr>
                <td class="party-box">
                    <h3>Billed To</h3>
                    <p><strong>' . htmlspecialchars($invoice['customer_name']) . '</strong></p>
                    <p>' . htmlspecialchars($invoice['customer_addr1'] ?? '') . '</p>
                    ' . (!empty($invoice['customer_addr2']) ? '<p>' . htmlspecialchars($invoice['customer_addr2']) . '</p>' : '') . '
                    <p>' . htmlspecialchars($invoice['customer_city'] ?? '') . ', ' . htmlspecialchars($invoice['customer_state'] ?? '') . ' - ' . htmlspecialchars($invoice['customer_pincode'] ?? '') . '</p>
                </td>
                <td class="party-box" style="padding-left: 30px;">
                    <h3>Service Provider</h3>
                    <p><strong>' . htmlspecialchars($invoice['worker_name']) . '</strong></p>
                     <p>' . htmlspecialchars($invoice['worker_addr1'] ?? '') . '</p>
                    ' . (!empty($invoice['worker_addr2']) ? '<p>' . htmlspecialchars($invoice['worker_addr2']) . '</p>' : '') . '
                    <p>' . htmlspecialchars($invoice['worker_city'] ?? '') . ', ' . htmlspecialchars($invoice['worker_state'] ?? '') . ' - ' . htmlspecialchars($invoice['worker_pincode'] ?? '') . '</p>
               </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 55%;">Service Description</th>
                    <th style="width: 25%; text-align: center;">Service Date & Time</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <p style="font-weight: bold; color: #111827; margin-top: 4px;">' . htmlspecialchars($serviceData['Service']) . ' - ' . htmlspecialchars($serviceData['Item']) . '</p>
                    </td>
                    <td style="text-align: center; white-space: nowrap;">' . $bookingTime->format('M d, Y, g:i A') . '</td>
                    <td style="text-align: right; font-weight: 500; color: #111827;">
                        ₹' . $originalCostFormatted . '
                    </td>
                </tr>
            </tbody>
        </table>

        <table style="margin-top: 30px; width: 40%; margin-left: auto;">
             <tbody class="summary-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">₹' . $originalCostFormatted . '</td>
                </tr>';
if ($discountAmount > 0) {
    $html .= '<tr>
                    <td class="label">Discount Applied:</td>
                    <td class="value" style="color: #16A34A;">-₹' . $discountAmountFormatted . '</td>
                </tr>';
}
$html .= '  <tr>
                    <td class="label">Taxes/Fees:</td>
                    <td class="value">₹' . number_format(0, 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td class="label total-paid">TOTAL PAID:</td>
                    <td class="value total-paid">₹' . $totalPaidFormatted . '</td>
                </tr>
            </tbody>
        </table>

    </main>
</body>
</html>';

// --- 8. PDF GENERATION ---
ob_end_clean(); // Clean buffer BEFORE generating PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Font supporting ₹ symbol
$options->set('isRemoteEnabled', true);
 
try {
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = 'DailyFix_Invoice_' . $bookingId . '.pdf';
    $dompdf->stream($fileName, ['Attachment' => false]); // Display inline
    exit;

} catch (Exception $e) {
    error_log("Dompdf Error: " . $e->getMessage());
    die("Error generating PDF invoice. Please check logs.");
}

?>