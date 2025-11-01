<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php"; 

if (!isset($userId) || $role !== 'worker') {
    header("Location: /dailyfix/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Worker Earnings Report - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/reports.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style>
        /* Common skeleton styles (loader, shimmer, dark-mode) */
        .skeleton-loader {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: var(--background-color-body, #f9f9f9);
            z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
        }
        .skeleton-loader.hidden { opacity: 0; pointer-events: none; }
        .skeleton-container {
            max-width: 1100px; width: 100%;
            padding: 0 1rem;
            margin: 1rem auto;
            margin-top: 80px; /* Adjust to match your header's height */
        }
        @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        .skeleton {
            animation: shimmer 1.5s infinite linear;
            background: linear-gradient(to right, 
            var(--hover-color, #f0f0f0) 8%, 
            var(--border-color, #e2e8f0) 18%, 
            var(--hover-color, #f0f0f0) 33%);
            background-size: 800px 104px; border-radius: 6px;
        }

        /* Page-specific skeleton layout for reports.php */
        .skeleton-title { height: 38px; width: 300px; margin: 2rem 0; }
        .skeleton-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .skeleton-stat-card { height: 100px; }
        .skeleton-main-card {
            height: 400px;
            width: 100%;
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .skeleton-card-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
        .skeleton-card-content { height: 300px; width: 100%; }
        
        @media (max-width: 768px) {
            .skeleton-stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>  
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-title"></div>
            
            <div class="skeleton-stats-grid">
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            </div>
            
            <div class="skeleton-main-card">
            <div class="skeleton skeleton-card-title"></div>
            <div class="skeleton skeleton-card-content"></div>
            </div>

            <div class="skeleton-main-card" style="height: 300px;">
            <div class="skeleton skeleton-card-title" style="width: 200px;"></div>
            <div class="skeleton skeleton-card-content" style="height: 200px;"></div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">Worker Job & Earnings Report</h1>
            
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
                <h3>My Performance Summary (Jobs from <span id="startDate"></span> to <span id="endDate"></span>)</h3>
                
                <div class="report-card-grid">
                    <div class="report-card">
                        <h4>Total Jobs Accepted</h4>
                        <p id="totalBookings">0</p>
                    </div>
                    <div class="report-card revenue">
                        <h4>Total Earned (Gross)</h4>
                        <p id="totalEarned">₹0.00</p>
                    </div>
                    <div class="report-card">
                        <h4>Completed Jobs</h4>
                        <p id="completedJobs">0</p>
                    </div>
                    <div class="report-card cancelled">
                        <h4>Cancelled By Me</h4>
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
                                <th>Customer</th>
                                <th>Service Details</th>
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
            const totalEarned = document.getElementById('totalEarned');
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
            const currentRole = 'worker'; // *** ROLE CONTEXT ***

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
                            totalEarned.textContent = formatCurrency(summary.total_revenue); 
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
                    let statusBadge;
                    const statusClass = getStatusClass(job.status); // Get the class name, e.g., "status-completed"

                    if (job.status === 'cancelled' && job.cancellation_reason) {
                        // Create the badge with the status class and the title
                        statusBadge = `<span class="${statusClass}" title="Reason: ${job.cancellation_reason}">${job.status} <i class="fas fa-info-circle"></i></span>`;
                    } else {
                        // Create a simple badge with the status class
                        statusBadge = `<span class="${statusClass}">${job.status}</span>`;
                    }

                    row.innerHTML = `
                        <td>${job.booking_id}</td>
                        <td>${formattedDate}</td>
                        <td>${job.customer_name}</td>
                        <td>${serviceDetails}</td>
                        <td>${formatCurrency(job.final_cost)}</td>
                        <td class="status-cell">${statusBadge}</td> 
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