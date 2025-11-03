<?php
include_once __DIR__ . '/includes/header.php'; // Use the admin header
include_once __DIR__ . '/../api/connect.php';

// Security check (header.php includes auth_check.php which defines $isAdmin)
if (!$isAdmin) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$totalEarnings = 0;
$processedPayouts = 0; // Changed from pending
$availableForPayout = 0;
$completedJobs = 0;
$payoutHistory = [];
$feesEarned = [];
$error_message = '';

try {
    // 1. Get Total Platform Fee (All Time)
    $stmt_total = $conn->prepare("
        SELECT SUM(COALESCE(platform_fee, 0)) FROM public.bookings
        WHERE status = 'completed' AND payment_status = 'paid' AND platform_fee > 0
    ");
    $stmt_total->execute();
    $totalEarnings = (float)($stmt_total->fetchColumn() ?: 0);

    // 2. Get Total PROCESSED Payouts
    $stmt_processed = $conn->prepare("
        SELECT SUM(COALESCE(amount, 0)) FROM public.platform_payouts WHERE status = 'processed'
    ");
    $stmt_processed->execute();
    $processedPayouts = (float)($stmt_processed->fetchColumn() ?: 0);

    // 3. Calculate Available Balance
    $availableForPayout = $totalEarnings - $processedPayouts;

    // 4. Get Total Completed Jobs (Platform-wide)
    $stmt_jobs = $conn->prepare("
        SELECT COUNT(*) FROM public.bookings
        WHERE status = 'completed' AND payment_status = 'paid'
    ");
    $stmt_jobs->execute();
    $completedJobs = $stmt_jobs->fetchColumn() ?: 0;
    
    // 5. Get Detailed List of All Fees Earned (Last 50 for performance)
    $stmt_details = $conn->prepare("
        SELECT
            b.id, b.booking_time,
            u_cust.full_name as customer_name,
            u_work.full_name as worker_name,
            b.platform_fee
        FROM public.bookings b
        JOIN public.users u_cust ON b.customer_id = u_cust.id
        JOIN public.users u_work ON b.worker_id = u_work.id
        WHERE b.status = 'completed' AND b.payment_status = 'paid' AND b.platform_fee > 0
        ORDER BY b.booking_time DESC
        LIMIT 50
    ");
    $stmt_details->execute();
    $feesEarned = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

    // 6. Get Payout History
    $stmt_payouts = $conn->prepare("
        SELECT p.*, u.full_name as admin_name
        FROM public.platform_payouts p
        JOIN public.users u ON p.requested_by_admin_id = u.id
        ORDER BY p.requested_at DESC
    ");
    $stmt_payouts->execute();
    $payoutHistory = $stmt_payouts->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin earnings fetch error: " . $e->getMessage());
    $error_message = "Could not load earnings data.";
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
<link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">

<style>
    /* Use admin panel's dark mode compatibility */
    body.dark-mode .summary-card,
    body.dark-mode .dashboard-card,
    body.dark-mode .data-table th,
    body.dark-mode .list-item {
        background: var(--background-card);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    body.dark-mode .data-table tr:hover,
    body.dark-mode .list-item:hover {
        background: var(--background-secondary);
    }
    body.dark-mode .summary-card h4 {
        color: var(--text-secondary);
    }
    body.dark-mode .summary-card p {
        color: var(--primary-color);
    }
    /* Payout-specific styles */
    .summary-card.payout-card p {
        font-size: 1.1rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }
    .payout-card .btn {
        width: 100%;
        font-size: 1rem;
        padding: 0.85rem;
    }
    .payout-card input[type="number"] {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        background-color: var(--background-secondary);
        color: var(--text-primary);
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;
    }
    .item-value.debit { color: var(--danger-color); }
    .item-value.credit { color: var(--success-color); }
    
    /* Status color for 'processed' */
    .item-status.processed {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success-color, #10b981);
    }
    body.dark-mode .item-status.processed {
        color: #82CD84; /* Lighter green for dark mode */
    }
    .item-status.pending {
        background-color: rgba(245, 158, 11, 0.1);
        color: var(--warning-color, #f59e0b);
    }
    body.dark-mode .item-status.pending {
        color: #f59e0b;
    }

    
    /* === START: COMPLETE SKELETON LOADER STYLES === */
    .skeleton-loader {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: var(--background-color-body, #f9f9f9);
        z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
    }
    .skeleton-loader.hidden { 
        opacity: 0; 
        pointer-events: none; 
    }
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

    /* Page-specific skeleton layout for this page */
    .skeleton-title { height: 38px; width: 40%; margin: 2rem 0; }
    .skeleton-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .skeleton-stat-card { 
        height: 120px; 
        background-color: var(--background-card); 
        border: 1px solid var(--border-color);
        border-radius: 8px;
    }
    body.dark-mode .skeleton-stat-card {
        background-color: var(--background-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-panel {
        padding: 2rem;
        background-color: var(--background-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    body.dark-mode .skeleton-panel {
        background-color: var(--background-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-card-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
    .skeleton-table { height: 300px; width: 100%; }

    @media (max-width: 768px) {
        .skeleton-stats-grid { grid-template-columns: 1fr; }
    }
    /* === END: COMPLETE SKELETON LOADER STYLES === */

    /* === START: CUSTOM POP-UP STYLES === */
    .confirmation-modal.modal-success .modal-icon { 
        color: var(--success-color); 
    }
    .confirmation-modal.modal-success .btn-confirm { 
        background-color: var(--success-color); 
    }
    .confirmation-modal.modal-success .btn-confirm:hover { 
        background-color: #059669; /* Darker green */
    }
    body.dark-mode .confirmation-modal.modal-success .btn-confirm:hover { 
        background-color: #059669; 
    }
    /* === END: CUSTOM POP-UP STYLES === */
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton-stats-grid">
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
            <div class="skeleton skeleton-stat-card"></div>
        </div>
        <div class="skeleton skeleton-panel">
            <div class="skeleton skeleton-card-title"></div>
            <div class="skeleton skeleton-table"></div>
        </div>
    </div>
</div>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-wallet"></i> Platform Wallet</h1>
    <p>View all revenue and manage platform payouts.</p>
</div>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="summary-grid section-fly-in">
    <div class="summary-card">
        <h4>Available for Payout</h4>
        <p><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($availableForPayout, 2); ?></p>
    </div>
    <div class="summary-card">
        <h4>Total Paid Out</h4>
        <p><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($processedPayouts, 2); ?></p>
    </div>
    <div class="summary-card payout-card">
        <h4>Process a Payout</h4>
        <p>Click to process a payout of your entire available balance of ₹<?php echo number_format($availableForPayout, 2); ?>.</p>
        <button id="request-payout-btn" class="btn btn-primary" <?php echo ($availableForPayout <= 0) ? 'disabled' : ''; ?>>
            Process Payout
        </button>
    </div>
</div>

<div class="management-grid section-fly-in">
    
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Payout History</h2>
        </div>
        <div class="card-content">
            <div class="item-list">
                <?php if (empty($payoutHistory)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No Payouts Processed</h3>
                        <p>Your processed payouts will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($payoutHistory as $payout): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <p><strong>Payout #<?php echo htmlspecialchars($payout['id']); ?> (<?php echo htmlspecialchars($payout['admin_name']); ?>)</strong></p>
                                <small>Requested on: <?php echo date("M d, Y, h:i A", strtotime($payout['requested_at'])); ?></small>
                            </div>
                            <div class="item-value debit">
                                -<i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($payout['amount'], 2); ?>
                            </div>
                            <span class="item-status <?php echo strtolower(htmlspecialchars($payout['status'])); ?>">
                                <?php echo htmlspecialchars(ucfirst($payout['status'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Platform Fee History (Last 50)</h2>
        </div>
        <div class="card-content">
            <div class="item-list">
                <?php if (empty($feesEarned)): ?>
                    <div class="empty-state">
                        <i class="fas fa-dollar-sign"></i>
                        <h3>No Fees Earned</h3>
                        <p>Fees from completed bookings will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($feesEarned as $fee): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <p><strong>Fee from Booking #<?php echo htmlspecialchars($fee['id']); ?></strong></p>
                                <small>Customer: <?php echo htmlspecialchars($fee['customer_name']); ?> | Worker: <?php echo htmlspecialchars($fee['worker_name']); ?></small>
                            </div>
                            <div class="item-value credit">
                                +<i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($fee['platform_fee'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="confirmation-modal" class="modal confirmation-modal" role="dialog" aria-hidden="true">
    <div class="modal-content">
        <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
        <div class="modal-icon"><i class=""></i></div>
        <h2 id="modal-title">Confirmation</h2>
        <p id="modal-description">Are you sure?</p>
        <div class="modal-buttons">
            <button id="confirm-action-btn" class="btn btn-confirm" type="button">Confirm</button>
            <button id="cancel-action-btn" class="btn btn-secondary" type="button">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payoutBtn = document.getElementById('request-payout-btn');
    const payoutAmount = <?php echo (float)$availableForPayout; ?>;

    // --- Helper function to show the status modal ---
    function showPayoutStatusModal(title, message, type = 'error') {
        const modal = document.getElementById('confirmation-modal');
        if (!modal) {
            alert(message); // Fallback if modal is missing
            if (type === 'success') window.location.reload();
            return;
        }

        const modalTitle = modal.querySelector('#modal-title');
        const modalDescription = modal.querySelector('#modal-description');
        const modalIcon = modal.querySelector('.modal-icon i');
        const confirmBtn = modal.querySelector('#confirm-action-btn');
        const cancelBtn = modal.querySelector('#cancel-action-btn');

        modalTitle.textContent = title;
        modalDescription.textContent = message;
        
        // Set theme based on status
        modal.classList.remove('modal-danger', 'modal-warning', 'modal-success');
        if (type === 'success') {
            modalIcon.className = 'fas fa-check-circle';
            modal.classList.add('modal-success');
            confirmBtn.textContent = 'OK';
        } else {
            modalIcon.className = 'fas fa-exclamation-triangle';
            modal.classList.add('modal-danger');
            confirmBtn.textContent = 'OK';
        }

        // Hide the cancel button, show the confirm (OK) button
        cancelBtn.style.display = 'none';
        confirmBtn.style.display = 'inline-block';

        // Show modal
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');

        // --- Handle OK click ---
        // We need to clone to remove old listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        const closeModal = () => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            if (type === 'success') {
                window.location.reload();
            }
            // Restore original modal state for next use
            cancelBtn.style.display = 'inline-block';
            modal.classList.remove('modal-success', 'modal-danger');
        };

        newConfirmBtn.onclick = closeModal;
        modal.querySelector('.close-button').onclick = closeModal;
        modal.querySelector('#cancel-action-btn').onclick = closeModal;
    }

    // --- Payout Button Click Logic ---
    if (payoutBtn) {
        payoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (payoutAmount <= 0) {
                showPayoutStatusModal('Error', 'There is no available balance to process a payout.', 'error');
                return;
            }

            // Use the admin confirmation modal for the *first* check
            const modal = document.getElementById('confirmation-modal');
            const modalTitle = modal.querySelector('#modal-title');
            const modalDescription = modal.querySelector('#modal-description');
            const confirmBtn = modal.querySelector('#confirm-action-btn');
            const cancelBtn = modal.querySelector('#cancel-action-btn');
            
            modalTitle.textContent = 'Confirm Payout';
            modalDescription.textContent = `Are you sure you want to process a payout of ₹${payoutAmount.toFixed(2)}? This will be logged as 'processed'.`;
            confirmBtn.textContent = 'Yes, Process Payout';
            
            // Set modal theme
            modal.classList.remove('modal-danger', 'modal-success');
            modal.classList.add('modal-warning');
Example:
            modal.querySelector('.modal-icon i').className = 'fas fa-exclamation-triangle';

            // Show confirmation modal
            cancelBtn.style.display = 'inline-block'; // Ensure cancel button is visible
            confirmBtn.style.display = 'inline-block'; // Ensure confirm button is visible
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');

            // --- Handle confirmation ---
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            newConfirmBtn.onclick = function() {
                // This function runs when "Yes, Process Payout" is clicked
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                
                payoutBtn.disabled = true;
                payoutBtn.textContent = 'Processing...';

                fetch('/dailyfix/api/admin_request_payout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({}) // Send an empty body, API calculates amount
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show the custom success modal
                        showPayoutStatusModal('Payout Processed', data.message, 'success');
                    } else {
                        // Show the custom error modal
                        showPayoutStatusModal('Payout Failed', data.message, 'error');
                        payoutBtn.disabled = false;
                        payoutBtn.textContent = 'Process Payout';
                    }
                })
                .catch(() => {
                    // Show the custom error modal
                    showPayoutStatusModal('Network Error', 'An unexpected network error occurred.', 'error');
                    payoutBtn.disabled = false;
                    payoutBtn.textContent = 'Process Payout';
                });
            };
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>