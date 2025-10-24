<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";
// $userId and $role must be set by the included header/session logic

if (!isset($userId) || $role !== 'customer') {
    header("Location: /dailyfix/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Reports - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
</head>
<style>
    /* ------------------- LIGHT MODE STYLES (Base) ------------------- */
    .report-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;}
    .report-card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .report-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
    .report-card h4 { font-size: 1.2em; color: #555; margin-bottom: 10px; }
    .report-card p { font-size: 2em; font-weight: 700; color: var(--main-color, #007bff); }
    .report-card.revenue p { color: #28a745; }
    .report-card.cancelled p { color: #dc3545; }
    .detailed-report-container { background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; padding: 20px; border-radius: 8px;}
    .detailed-report-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    .detailed-report-table th, .detailed-report-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9em; }
    .detailed-report-table th { background-color: #f8f8f8; color: #333; }
    .detailed-report-table tbody tr:hover { background-color: #f5f5f5; }
    .export-button {
        padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; transition: background-color 0.2s;
        background-color: #007bff; color: white; margin-left: 5px;
    }
    .export-button:hover { background-color: #0056b3; }
    .filter-select { padding: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: white; color: #333; }
    .status-completed { color: #28a745; font-weight: 500; }
    .status-cancelled { color: #dc3545; font-weight: 500; }
    .review-yes { color: #28a745; }
    .review-no { color: #6c757d; }
    .pdf-button { background-color: #dc3545 !important; }

    /* ------------------- DARK MODE STYLES (NEW) ------------------- */
    .dark-mode .page-content { background-color: #121212; color: #e0e0e0; }
    .dark-mode .page-title, .dark-mode h3 { color: #f0f0f0; }

    .dark-mode .report-card { 
        background: #1e1e1e; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.4); 
    }
    .dark-mode .report-card h4 { color: #aaaaaa; }
    
    .dark-mode .detailed-report-container { 
        background: #1e1e1e;
        box-shadow: 0 4px 6px rgba(0,0,0,0.4); 
    }
    .dark-mode .detailed-report-table th { 
        background-color: #333; 
        color: #f0f0f0; 
    }
    .dark-mode .detailed-report-table td { 
        color: #e0e0e0; 
    }
    .dark-mode .detailed-report-table tbody tr:hover { 
        background-color: #2a2a2a; 
    }

    .dark-mode .status-pending { color: #ffb74d; }
    .dark-mode .status-confirmed { color: #64b5f6; } 
    .dark-mode .status-completed { color: #81c784; }
    .dark-mode .status-cancelled { color: #e57373; }
    .dark-mode .review-yes { color: #81c784; }

    .dark-mode .export-button { background-color: #3b82f6; color: white; }
    .dark-mode .pdf-button { background-color: #c94040 !important; }
    .dark-mode .filter-select { background-color: #1e1e1e; color: #e0e0e0; border: 1px solid #444; }
</style>
<body>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">My Reports</h1>
            
            <div class="report-controls">
                <div class="filter-group">
                    <select id="timeFilter" class="filter-select">
                        <option value="daily">Last 24 Hours</option>
                        <option value="7days">Last 7 Days</option>
                        <option value="1month" selected>Last 1 Month</option>
                        <option value="3months">Last 3 Months</option>
                        <option value="6months">Last 6 Months</option>
                        <option value="1year">Last 1 Year</option>
                        <option value="2years">Last 2 Years</option>
                    </select>
                </div>
                <div class="export-options">
                    <a href="#" id="exportCsvBtn" class="export-button">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="#" id="exportPdfBtn" class="export-button pdf-button">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <div id="reportLoader" style="text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i> Loading Report...
            </div>
            
            <div id="reportContent" style="display:none;">
                <h3>Summary Overview (Bookings from <span id="startDate"></span> to <span id="endDate"></span>)</h3>
                
                <div class="report-card-grid">
                    <div class="report-card">
                        <h4>Total Bookings</h4>
                        <p id="totalBookings">0</p>
                    </div>
                    <div class="report-card revenue">
                        <h4>Total Spent</h4>
                        <p id="totalSpent">₹0.00</p>
                    </div>
                    <div class="report-card">
                        <h4>Completed Jobs</h4>
                        <p id="completedJobs">0</p>
                    </div>
                    <div class="report-card cancelled">
                        <h4>Cancelled Jobs</h4>
                        <p id="cancelledJobs">0</p>
                    </div>
                </div>

                <h3 style="margin-top: 40px;">Detailed Job & Review History</h3>
                <div class="detailed-report-container">
                    <table class="detailed-report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date/Time</th>
                                <th>Worker</th>
                                <th>Details</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Review</th>
                            </tr>
                        </thead>
                        <tbody id="detailedReportBody">
                            </tbody>
                    </table>
                </div>
                <div id="noDataMessage" style="text-align: center; padding: 20px; display: none;">
                    No detailed data found for this period.
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelect = document.getElementById('timeFilter');
            const loader = document.getElementById('reportLoader');
            const content = document.getElementById('reportContent');
            const exportCsvBtn = document.getElementById('exportCsvBtn'); 
            const exportPdfBtn = document.getElementById('exportPdfBtn'); 
            const totalBookings = document.getElementById('totalBookings');
            const totalSpent = document.getElementById('totalSpent');
            const completedJobs = document.getElementById('completedJobs');
            const cancelledJobs = document.getElementById('cancelledJobs');
            const startDateSpan = document.getElementById('startDate');
            const endDateSpan = document.getElementById('endDate');
            const detailedReportBody = document.getElementById('detailedReportBody');
            const noDataMessage = document.getElementById('noDataMessage');

            // API endpoints
            const summaryApi = '/dailyfix/api/get_reports.php'; 
            const detailedApi = '/dailyfix/api/get_detailed_reports.php';
            const exportCsvApi = '/dailyfix/api/export_reports.php';
            const exportPdfApi = '/dailyfix/api/export_pdf.php';
            const currentRole = 'customer'; 

            function formatCurrency(amount) {
                return '₹' + parseFloat(amount).toFixed(2);
            }
            
            function getStatusClass(status) {
                return 'status-' + status.toLowerCase().replace(/[^a-z0-9]/g, '');
            }

            function fetchReports(filter) {
                loader.style.display = 'block';
                content.style.display = 'none';
                detailedReportBody.innerHTML = '';
                noDataMessage.style.display = 'none';
                
                // 1. Update Export Links
                const currentFilter = filter;
                exportCsvBtn.href = `${exportCsvApi}?filter=${currentFilter}&role=${currentRole}`;
                exportPdfBtn.href = `${exportPdfApi}?filter=${currentFilter}&role=${currentRole}`;
                
                // 2. Fetch Summary Report 
                fetch(`${summaryApi}?filter=${filter}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const summary = data.data.summary;
                            totalBookings.textContent = summary.total_bookings;
                            totalSpent.textContent = formatCurrency(summary.total_revenue); 
                            completedJobs.textContent = summary.completed;
                            cancelledJobs.textContent = summary.cancelled;
                            
                            startDateSpan.textContent = data.data.filter_dates.start;
                            endDateSpan.textContent = data.data.filter_dates.end;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch summary error:', error);
                    });

                // 3. Fetch Detailed Report 
                fetch(`${detailedApi}?filter=${filter}&role=${currentRole}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            populateDetailedTable(data.data);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch detailed error:', error);
                    })
                    .finally(() => {
                        loader.style.display = 'none';
                        content.style.display = 'block';
                    });
            }

            function populateDetailedTable(data) {
                if (data.length === 0) {
                    noDataMessage.style.display = 'block';
                    exportCsvBtn.style.display = 'none';
                    exportPdfBtn.style.display = 'none';
                    return;
                }
                exportCsvBtn.style.display = 'inline-flex';
                exportPdfBtn.style.display = 'inline-flex';

                data.forEach(job => {
                    const row = document.createElement('tr');

                    // Date formatting
                    const dateObj = new Date(job.booking_time + 'Z'); 
                    const formattedDate = dateObj.toLocaleString('en-IN', {
                        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                    });

                    // Review Cell Content
                    let reviewContent;
                    if (job.rating) {
                        reviewContent = `<span class="review-yes"><i class="fas fa-star"></i> ${job.rating}</span><br><small title="${job.review_comment}">${job.review_comment.substring(0, 30)}${job.review_comment.length > 30 ? '...' : ''}</small>`;
                    } else if (job.status === 'completed') {
                        reviewContent = `<span class="review-no">Pending</span>`;
                    } else {
                        reviewContent = `N/A`;
                    }
                    
                    // Service Details Cleanup
                    const serviceDetails = job.service_details ? job.service_details.replace(/Service: (.*)\nItem: (.*)\nAddress: (.*)/s, (match, service, item, address) => `Service: <b>${service}</b><br>Item: ${item}<br>Address: ${address.substring(0, 30)}...`) : 'N/A';
                    
                    // Status with Cancellation Reason Tooltip
                    let statusText = job.status;
                    if (job.status === 'cancelled' && job.cancellation_reason) {
                        statusText = `<span title="Reason: ${job.cancellation_reason}">${job.status} <i class="fas fa-info-circle"></i></span>`;
                    } else {
                        statusText = job.status;
                    }

                    row.innerHTML = `
                        <td>${job.booking_id}</td>
                        <td>${formattedDate}</td>
                        <td>${job.worker_name}</td>
                        <td>${serviceDetails}</td>
                        <td>${formatCurrency(job.final_cost)}</td>
                        <td class="${getStatusClass(job.status)}">${statusText}</td>
                        <td>${reviewContent}</td>
                    `;
                    detailedReportBody.appendChild(row);
                });
            }

            filterSelect.addEventListener('change', (e) => {
                fetchReports(e.target.value);
            });

            fetchReports(filterSelect.value);
        });
    </script>

    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>