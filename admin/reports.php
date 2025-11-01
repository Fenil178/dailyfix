<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/includes/auth_check.php"; 
include_once __DIR__ . "/includes/header.php"; // Includes Admin Header (Menu/Nav)

if ($role !== 'admin') {
    header("Location: /dailyfix/index.php");
    exit;
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<style>
    /* ------------------- LIGHT MODE STYLES (Base) ------------------- */
    .report-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;}
    .report-card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .report-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
    .report-card h4 { font-size: 1.1em; color: #555; margin-bottom: 10px; }
    .report-card p { font-size: 1.8em; font-weight: 700; color: var(--main-color, #007bff); }
    .report-card.revenue p { color: #28a745; }
    .report-card.cancelled p { color: #dc3545; }
    /* Detailed containers use the same background as cards */
    .detailed-report-container { background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; padding: 20px; border-radius: 8px;}
    .detailed-report-table { width: 100%; border-collapse: collapse; min-width: 1200px; }
    .detailed-report-table th, .detailed-report-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9em; }
    .detailed-report-table th { background-color: #f8f8f8; color: #333; }
    .detailed-report-table tbody tr:hover { background-color: #f5f5f5; }
    .export-button {
        padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; transition: background-color 0.2s;
        background-color: #007bff; color: white; margin-left: 5px;
    }
    .filter-group { display: flex; gap: 10px; align-items: center; }
    .filter-select { padding: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: white; color: #333; }
    .pdf-button { background-color: #dc3545 !important; }

    /* ------------------- DARK MODE STYLES (FINAL CORRECTION) ------------------- */
    
    /* Corrected: Use a unified dark background color for admin cards/containers */
    .dark-mode .page-header {
        color: #f0f0f0;
    }

    .dark-mode .dashboard-card,
    .dark-mode .report-card,
    .dark-mode .detailed-report-container { 
        background-color: #1e293b !important; /* Matches common dark admin theme base */
        color: #f0f0f0;
        border: 1px solid #334155;
        box-shadow: 0 4px 6px rgba(0,0,0,0.5);
    }
    
    .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode #reportTitle { 
        color: #f1f5f9; 
    }
    .dark-mode p {
        color: #f1f5f9;
    }
    .dark-mode .report-card h4 { 
        color: #f1f5f9; 
    }
    
    /* Table headers should be slightly darker or different */
    .dark-mode .detailed-report-table th { 
        background-color: #1e293b; /* Slightly darker than card body */
        color: #f0f0f0; 
        border-color: #334155;
    }
    .dark-mode .detailed-report-table td { 
        color: #e0e0e0; 
        border-color: #334155;
    }
    .dark-mode .detailed-report-table tbody tr:hover { 
        background-color: #0f172a; /* Subtle dark hover effect */
    }

    /* Filters and Buttons */
    .dark-mode .filter-select { 
        background-color: #1e293b; 
        color: #e0e0e0; 
        border: 1px solid #334155; 
    }
    .dark-mode .export-button { 
        background-color: #007bff; /* Primary blue */
        color: white; 
    }
    .dark-mode .pdf-button { 
        background-color: #e74c3c !important; 
    }
    
    /* Status Colors */
    .dark-mode .status-pending { color: #FFD700; }
    .dark-mode .status-confirmed { color: #78B9FF; }
    .dark-mode .status-completed { color: #82CD84; }
    .dark-mode .status-cancelled { color: #E77373; }
</style>

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
    body.dark-mode .skeleton-loader { background-color: var(--background-color-body, #121212); }
    body.dark-mode .skeleton {
        background: linear-gradient(to right, 
        var(--hover-color, #2c2c2c) 8%, 
        var(--border-color, #334155) 18%, 
        var(--hover-color, #2c2c2c) 33%);
        background-size: 800px 104px;
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
    body.dark-mode .skeleton-main-card {
        background-color: var(--background-color-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-card-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
    .skeleton-card-content { height: 300px; width: 100%; }
    
    @media (max-width: 768px) {
        .skeleton-stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

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

<div class="page-header section-fly-in">
    <h1><i class="fas fa-chart-line"></i> Platform Management Reports</h1>
    <p>Detailed performance analytics and user data.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-header">
        <h2 id="reportTitle">Global Metrics</h2>
    </div>
    
    <div class="card-content">
        <div class="report-controls">
            <div class="filter-group">
                <select id="roleFilter" class="filter-select">
                    <option value="admin">Global/Platform</option>
                    <option value="customer">Customer Metrics</option>
                    <option value="worker">Worker Metrics</option>
                </select>
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
            <i class="fas fa-spinner fa-spin fa-2x"></i> Loading Platform Data...
        </div>
        
        <div id="reportContent" style="display:none;">
            <h3>Summary Overview (Data from <span id="startDate"></span> to <span id="endDate"></span>)</h3>
            
            <div class="report-card-grid">
                <div class="report-card">
                    <h4>Total Bookings</h4>
                    <p id="totalBookings">0</p>
                </div>
                <div class="report-card revenue">
                    <h4>Total Platform Revenue</h4>
                    <p id="totalRevenue">₹0.00</p>
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
                            <th>Customer</th>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeFilter = document.getElementById('timeFilter');
        const roleFilter = document.getElementById('roleFilter');
        const loader = document.getElementById('reportLoader');
        const content = document.getElementById('reportContent');
        const reportTitle = document.getElementById('reportTitle');
        const exportCsvBtn = document.getElementById('exportCsvBtn'); 
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        
        const totalBookings = document.getElementById('totalBookings');
        const totalRevenue = document.getElementById('totalRevenue');
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

        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toFixed(2);
        }
        
        function getStatusClass(status) {
            return 'status-' + status.toLowerCase().replace(/[^a-z0-9]/g, '');
        }
        
        function updateTitle(role) {
             if (role === 'customer') {
                reportTitle.textContent = 'Customer Metrics';
            } else if (role === 'worker') {
                reportTitle.textContent = 'Worker Metrics';
            } else {
                reportTitle.textContent = 'Global Metrics';
            }
        }

        function fetchReports() {
            const currentFilter = timeFilter.value;
            const currentRole = roleFilter.value;
            
            updateTitle(currentRole);
            loader.style.display = 'block';
            content.style.display = 'none';
            detailedReportBody.innerHTML = '';
            noDataMessage.style.display = 'none';

            // 1. Update Export Links
            exportCsvBtn.href = `${exportCsvApi}?filter=${currentFilter}&role=${currentRole}`;
            exportPdfBtn.href = `${exportPdfApi}?filter=${currentFilter}&role=${currentRole}`;
            
            // 2. Fetch Summary Report
            fetch(`${summaryApi}?filter=${currentFilter}&role=${currentRole}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const summary = data.data.summary;
                        totalBookings.textContent = summary.total_bookings;
                        totalRevenue.textContent = formatCurrency(summary.total_revenue);
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
            fetch(`${detailedApi}?filter=${currentFilter}&role=${currentRole}`)
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
                    <td>${job.customer_name}</td>
                    <td>${job.worker_name}</td>
                    <td>${serviceDetails}</td>
                    <td>${formatCurrency(job.final_cost)}</td>
                    <td class="${getStatusClass(job.status)}">${statusText}</td>
                    <td>${reviewContent}</td>
                `;
                detailedReportBody.appendChild(row);
            });
        }

        // Event Listeners for filter change
        timeFilter.addEventListener('change', fetchReports);
        roleFilter.addEventListener('change', fetchReports);

        // Initial load
        fetchReports();
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>