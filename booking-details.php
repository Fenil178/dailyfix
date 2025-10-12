<?php
include_once __DIR__ . "/api/connect.php";
include_once __DIR__ . "/api/header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$bookingId = $_GET['id'];
$booking = null;

try {
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            c.full_name AS customer_name,
            c.profile_image AS customer_avatar,
            w.full_name AS worker_name,
            w.profile_image AS worker_avatar,
            w.phone AS worker_phone,
            c.phone AS customer_phone
        FROM 
            public.bookings b
        JOIN 
            public.users c ON b.customer_id = c.id
        JOIN 
            public.users w ON b.worker_id = w.id
        WHERE 
            b.id = ? AND (b.customer_id = ? OR b.worker_id = ?)
    ");
    $stmt->execute([$bookingId, $userId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking Details Error: " . $e->getMessage());
}

if (!$booking) {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

// --- Dynamic Variables Setup ---
$statusClass = strtolower(str_replace(' ', '_', $booking['status']));
$bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
$bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
$backLink = ($role === 'worker') ? '/dailyfix/worker/jobs.php' : '/dailyfix/customer/bookings.php';

// Parse service details
$serviceDetails = explode("\n", $booking['service_details']);
$serviceData = [];
$addressLines = [];
foreach ($serviceDetails as $line) {
    if (strpos($line, 'Service:') !== false) $serviceData['service'] = trim(str_replace('Service:', '', $line));
    else if (strpos($line, 'Item:') !== false) $serviceData['item'] = trim(str_replace('Item:', '', $line));
    else if (strpos($line, 'Address:') !== false) $addressLines['full'] = trim(str_replace('Address:', '', $line));
}
// Fallback for avatar paths
$customerAvatar = strpos($booking['customer_avatar'], '/') === 0 ? $booking['customer_avatar'] : '/dailyfix/' . $booking['customer_avatar'];
$workerAvatar = strpos($booking['worker_avatar'], '/') === 0 ? $booking['worker_avatar'] : '/dailyfix/' . $booking['worker_avatar'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking #<?php echo htmlspecialchars($booking['id']); ?> - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/booking-details.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <style>
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }
        .custom-modal.show {
            display: flex;
        }
        .custom-modal-content {
            background-color: var(--background-color-card);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.25);
            animation: modal-fade-in 0.3s ease-out;
            position: relative;
        }
        @keyframes modal-fade-in {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-icon-custom {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .modal-icon-custom.success { color: #10b981; }
        .modal-icon-custom.error { color: #ef4444; }
        .modal-icon-custom.warning { color: #f59e0b; }
        .custom-modal-content h3 {
            color: var(--text-color-dark);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .custom-modal-content p {
            color: var(--text-color-light);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .custom-modal-content .ok-btn {
            background-color: var(--primary-color);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100px;
        }
        body.dark-mode .custom-modal-content .ok-btn {
            color: #111;
        }
        .modal-close-icon {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-color-light);
            padding: 5px;
        }
        .modal-buttons-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .modal-buttons-container .btn {
            padding: 10px 25px;
            font-weight: 600;
            min-width: 120px;
            border-radius: 8px;
        }
        .modal-buttons-container .btn-secondary {
            background-color: var(--hover-color);
            border: 1px solid var(--border-color);
            color: var(--text-color-light);
        }
        .modal-buttons-container .btn-secondary:hover {
            background-color: var(--border-color);
        }
        .modal-buttons-container .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }
        body.dark-mode .modal-buttons-container .btn-primary { color: #111; }

    </style>
</head>
<body>
    <?php include_once __DIR__ . "/api/header.php"; ?>

    <main class="page-content">
        <div class="management-container">
            <a href="<?php echo $backLink; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to <?php echo ($role === 'worker') ? 'Jobs' : 'Bookings'; ?></a>
            
            <div class="details-header">
                <div class="header-top">
                    <h1>Booking #<?php echo htmlspecialchars($booking['id']); ?></h1>
                    <span class="item-status <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $booking['status'])); ?>
                    </span>
                </div>
                <div class="booking-time">
                    <i class="fas fa-calendar-alt"></i> Scheduled for: <?php echo $bookingTime->format('D, M j, Y, g:i A'); ?>
                </div>
            </div>

            <div class="details-grid-main">
                <div class="details-column">
                    <div class="detail-card">
                        <h3><i class="fas fa-clipboard-list"></i> Job Breakdown</h3>
                        
                        <div class="card-section">
                            <div class="detail-item">
                                <strong>Service Category</strong>
                                <span><?php echo htmlspecialchars($serviceData['service'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Service Item</strong>
                                <span><?php echo htmlspecialchars($serviceData['item'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <div class="card-section">
                             <div class="detail-item">
                                <strong>Full Address</strong>
                                <span><?php echo htmlspecialchars($addressLines['full'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="card-section">
                            <div class="detail-item">
                                <strong>Total Cost</strong>
                                <span>₹<?php echo number_format($booking['final_cost'] ?? 0.00, 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Payment Status</strong>
                                <span class="item-status <?php echo $booking['payment_status']; ?>"><?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?></span>
                            </div>
                        </div>   
                    </div>
                </div>

                <div class="sidebar-column">
                    <div class="detail-card">
                        <h3><i class="fas fa-users"></i> Participants</h3>
                        <div class="participant-list">
                            <div class="participant-profile">
                                <img src="<?php echo htmlspecialchars($customerAvatar); ?>" alt="Customer">
                                <div>
                                    <div class="role">Customer</div>
                                    <div class="name"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                                </div>
                            </div>
                            <div class="participant-profile">
                                <img src="<?php echo htmlspecialchars($workerAvatar); ?>" alt="Worker">
                                <div>
                                    <div class="role">Worker</div>
                                    <div class="name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['worker_phone']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'worker'): ?>
                        <?php if ($booking['status'] === 'confirmed'): ?>
                        <div class="action-panel">
                            <h2>Arrived at Job Site?</h2>
                            <p>Once you are physically ready to start the work, update the status.</p>
                            <button onclick="handleJobAction(<?php echo $booking['id']; ?>, 'in_progress', null, this)" class="btn-main btn start-job">Start Job</button>
                        </div>
                        <?php elseif ($booking['status'] === 'in_progress' && !$booking['work_completed_by_worker']): ?>
                        <div class="action-panel">
                            <h2>Work is Finished?</h2>
                            <p>Confirm completion to notify the customer and trigger the payment step.</p>
                            <button id="mark-complete-btn" class="btn-main btn mark-complete" data-booking-id="<?php echo $booking['id']; ?>">Mark Complete</button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($role === 'customer' && $booking['work_completed_by_worker'] && $booking['payment_status'] === 'unpaid'): ?>
                        <div class="action-panel">
                            <h2>Payment Required</h2>
                            <p>The worker confirmed job completion. Please finalize payment of **₹<?php echo number_format($booking['final_cost'] ?? 0.00, 2); ?>**.</p>
                            <button id="pay-now-btn" class="btn-main" data-booking-id="<?php echo $booking['id']; ?>">Pay Now</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($booking['status'] === 'completed' && $booking['payment_status'] === 'paid'): ?>
                        <div class="action-panel" style="border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);">
                            <h2><i class="fas fa-check-circle" style="color: #10b981;"></i> Job Completed & Paid</h2>
                            <p style="color: #10b981;">This transaction is complete. Thank you for using DailyFix.</p>
                            <a href="/dailyfix/generate_invoice.php?id=<?php echo $booking['id']; ?>" target="_blank" class="btn-main" style="background-color: #10b981; color: white; margin-top: 1rem;">
                                <i class="fas fa-file-invoice"></i> Download Invoice
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="status-modal" class="custom-modal">
        <div class="custom-modal-content">
            <i id="status-modal-icon" class="modal-icon-custom fas"></i>
            <h3 id="status-modal-title"></h3>
            <p id="status-modal-message"></p>
            <button id="status-modal-close-btn" class="ok-btn">OK</button>
        </div>
    </div>
    
    <div id="confirmation-modal" class="custom-modal">
        <div class="custom-modal-content">
            <button class="modal-close-icon">&times;</button>
            <i id="confirm-modal-icon" class="modal-icon-custom fas fa-question-circle warning"></i>
            <h3 id="confirm-modal-title"></h3>
            <p id="confirm-modal-message"></p>
            <div class="modal-buttons-container">
                <button id="modal-cancel-btn" class="btn btn-secondary">Cancel</button>
                <button id="modal-confirm-btn" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // --- Status Modal (for success/error messages) ---
    function showStatusModal(status, title, message) {
        const modal = document.getElementById('status-modal');
        const icon = document.getElementById('status-modal-icon');
        const titleEl = document.getElementById('status-modal-title');
        const messageEl = document.getElementById('status-modal-message');
        
        icon.className = 'modal-icon-custom fas';
        if (status === 'success') icon.classList.add('fa-check-circle', 'success');
        else if (status === 'error') icon.classList.add('fa-exclamation-triangle', 'error');
        else icon.classList.add('fa-info-circle', 'warning');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.classList.add('show');

        document.getElementById('status-modal-close-btn').onclick = function() {
            modal.classList.remove('show');
            if (status === 'success') window.location.reload();
        };
    }

    // --- Confirmation Modal (replaces default confirm()) ---
    function showConfirmationModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmation-modal');
        const titleEl = document.getElementById('confirm-modal-title');
        const messageEl = document.getElementById('confirm-modal-message');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const cancelBtn = document.getElementById('modal-cancel-btn');
        const closeBtn = modal.querySelector('.modal-close-icon');

        titleEl.textContent = title;
        messageEl.innerHTML = message; // Use innerHTML to parse <strong> tags
        modal.classList.add('show');

        const confirmHandler = () => {
            onConfirm();
            modal.classList.remove('show');
        };

        const cancelHandler = () => {
            modal.classList.remove('show');
        };

        confirmBtn.onclick = confirmHandler;
        cancelBtn.onclick = cancelHandler;
        closeBtn.onclick = cancelHandler;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        
        const markCompleteBtn = document.getElementById('mark-complete-btn');
        if (markCompleteBtn) {
            markCompleteBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.textContent;
                
                const processAction = () => {
                    button.disabled = true;
                    button.textContent = 'Processing...';

                    const bookingId = button.dataset.bookingId;
                    const formData = new FormData();
                    formData.append('booking_id', bookingId);

                    fetch('/dailyfix/api/mark-work-done.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showStatusModal('success', 'Work Completed!', 'The customer has been notified for payment.');
                            } else {
                                showStatusModal('error', 'Update Failed', data.message);
                                button.disabled = false;
                                button.textContent = originalText;
                            }
                        })
                        .catch(() => {
                            showStatusModal('error', 'Network Error', 'A network error occurred.');
                            button.disabled = false;
                            button.textContent = originalText;
                        });
                };

                showConfirmationModal(
                    'Confirm Completion',
                    'Are you sure you want to mark this job as complete? The customer will be prompted for payment.',
                    processAction
                );
            });
        }
        
        const payNowBtn = document.getElementById('pay-now-btn');
        if (payNowBtn) {
            payNowBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.textContent;
                
                const processPayment = () => {
                    button.disabled = true;
                    button.textContent = 'Processing...';

                    const bookingId = button.dataset.bookingId;
                    const formData = new FormData();
                    formData.append('booking_id', bookingId);

                    fetch('/dailyfix/api/process-static-payment.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showStatusModal('success', 'Payment Successful!', data.message);
                            } else {
                                showStatusModal('error', 'Payment Failed', data.message);
                                button.disabled = false;
                                button.textContent = originalText;
                            }
                        })
                        .catch(() => {
                            showStatusModal('error', 'Network Error', 'An unexpected network error occurred.');
                            button.disabled = false;
                            button.textContent = originalText;
                        });
                };
                
                showConfirmationModal(
                    'Confirm Payment',
                    'You are about to authorize a payment of <strong>₹<?php echo number_format($booking['final_cost'] ?? 0.00, 2); ?></strong>. Proceed?',
                    processPayment
                );
            });
        }

        window.handleJobAction = function(bookingId, status, bookingTime, buttonElement) {
            const originalText = buttonElement.textContent;
            buttonElement.disabled = true;
            buttonElement.textContent = '...';
            
            let url = `/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload(); 
                    } else {
                        showStatusModal('error', 'Update Failed', data.message);
                        buttonElement.disabled = false;
                        buttonElement.textContent = originalText;
                    }
                })
                .catch(() => {
                    showStatusModal('error', 'Network Error', 'A network error occurred.');
                    buttonElement.disabled = false;
                    buttonElement.textContent = originalText;
                });
        };
    });
    </script>

    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>