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
            c.phone AS customer_phone,
            w.full_name AS worker_name,
            w.profile_image AS worker_avatar,
            w.phone AS worker_phone,
            r.id as review_id
        FROM
            public.bookings b
        JOIN
            public.users c ON b.customer_id = c.id
        JOIN
            public.users w ON b.worker_id = w.id
        LEFT JOIN
            public.reviews r ON b.id = r.booking_id
        WHERE
            b.id = ? AND (b.customer_id = ? OR b.worker_id = ?)
    ");
    $stmt->execute([$bookingId, $userId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking Details Error: " . $e->getMessage());
    // Optionally display an error message or redirect
}

if (!$booking) {
     // Redirect if booking not found or user doesn't have permission
    header("Location: /dailyfix/dashboard.php?error=not_found");
    exit;
}

$appliedCouponCode = null;
if ($booking['applied_offer_id']) {
    try {
        $stmt_coupon = $conn->prepare("SELECT coupon_code FROM public.worker_offers WHERE id = ?");
        $stmt_coupon->execute([$booking['applied_offer_id']]);
        $appliedCouponCode = $stmt_coupon->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching applied coupon code: " . $e->getMessage());
    }
}


// Calculate final cost after discount for display
$originalCost = (float)($booking['final_cost'] ?? 0.00); // This is the base price set during booking
$discountAmount = (float)($booking['discount_amount'] ?? 0.00);
$finalCostAfterDiscount = max(0, $originalCost - $discountAmount);

// --- Dynamic Variables Setup ---
$statusClass = strtolower(str_replace(' ', '_', $booking['status']));
$bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
$bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); // Use local timezone
$backLink = ($role === 'worker') ? '/dailyfix/worker/jobs.php' : '/dailyfix/customer/bookings.php';

// Parse service details
$serviceDetails = explode("\n", $booking['service_details'] ?? '');
$serviceData = ['service' => 'N/A', 'item' => 'N/A']; // Initialize with defaults
$addressLines = ['full' => 'N/A'];
foreach ($serviceDetails as $line) {
    if (strpos($line, 'Service:') !== false) $serviceData['service'] = trim(str_replace('Service:', '', $line));
    else if (strpos($line, 'Item:') !== false) $serviceData['item'] = trim(str_replace('Item:', '', $line));
    else if (strpos($line, 'Address:') !== false) $addressLines['full'] = trim(str_replace('Address:', '', $line));
}

// Calculate final cost after discount for display
$originalCost = (float)($booking['final_cost'] ?? 0.00);
$discountAmount = (float)($booking['discount_amount'] ?? 0.00);
$finalCostAfterDiscount = max(0, $originalCost - $discountAmount); // Ensure cost doesn't go below zero

// Fallback for avatar paths
$customerAvatar = $booking['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png';
if ($booking['customer_avatar'] && strpos($booking['customer_avatar'], '/') !== 0) {
    $customerAvatar = '/dailyfix/' . $booking['customer_avatar'];
}
$workerAvatar = $booking['worker_avatar'] ?: '/dailyfix/assets/images/default-avatar.png';
if ($booking['worker_avatar'] && strpos($booking['worker_avatar'], '/') !== 0) {
    $workerAvatar = '/dailyfix/' . $booking['worker_avatar'];
}

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
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style> /* Combined styles */
        .custom-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .custom-modal.show { display: flex; }
        .custom-modal-content { background-color: var(--background-color-card); padding: 30px; border-radius: 12px; width: 90%; max-width: 420px; text-align: center; box-shadow: 0 8px 30px rgba(0,0,0,0.25); animation: modal-fade-in 0.3s ease-out; position: relative; }
        @keyframes modal-fade-in { from { opacity: 0; transform: translateY(-30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-icon-custom { font-size: 3rem; margin-bottom: 1rem; }
        .modal-icon-custom.success { color: #10b981; } .modal-icon-custom.error { color: #ef4444; } .modal-icon-custom.warning { color: #f59e0b; }
        .custom-modal-content h3 { color: var(--text-color-dark); font-size: 1.5rem; margin-bottom: 0.5rem; }
        .custom-modal-content p { color: var(--text-color-light); margin-bottom: 1.5rem; line-height: 1.6; }
        .custom-modal-content .ok-btn { 
            color: var(--primary-color);
            background-color: transparent;
            border: 2px solid var(--primary-color);
            padding: 10px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100px; 
        }
        .custom-modal-content .ok-btn:hover { background-color: var(--primary-color); color: #fff; }
        body.dark-mode .custom-modal-content .ok-btn { color: #111; }
        .modal-close-icon { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; background: none; border: none; cursor: pointer; color: var(--text-color-light); padding: 5px; }
        .modal-buttons-container { display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
        .modal-buttons-container .btn { padding: 10px 25px; font-weight: 600; min-width: 120px; border-radius: 8px; }
        .modal-buttons-container .btn-secondary { background-color: var(--hover-color); border: 1px solid var(--border-color); color: var(--text-color-light); }
        .modal-buttons-container .btn-secondary:hover { background-color: var(--border-color); }
        .modal-buttons-container .btn-primary { background-color: var(--primary-color); color: #fff; }
        body.dark-mode .modal-buttons-container .btn-primary { color: #111; }
         /* Coupon Specific Styles */
        #coupon-section label { font-weight: 500; display: block; margin-bottom: 0.5rem; color: var(--text-color-dark); }
        #coupon-section input[type="text"] { flex-grow: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--hover-color); color: var(--text-color-dark);}
        #coupon-section button#apply-coupon-btn { padding: 10px 15px; flex-shrink: 0; background-color: var(--secondary-color); color: var(--text-color-dark); border:none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s, color 0.2s, opacity 0.2s; }
        #coupon-section button#apply-coupon-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        #coupon-section button#apply-coupon-btn:hover:not(:disabled) { background-color: #d97706; }
        #coupon-message { font-size: 0.85rem; margin-top: 0.5rem; min-height: 1.2em; font-weight: 500;}
        #price-summary { margin-top: 1rem; font-size: 0.9rem; color: var(--text-color-light); border-top: 1px dashed var(--border-color); padding-top: 1rem; display: none; }
        #price-summary p { margin: 0.3rem 0; }
        #price-summary hr { border: none; border-top: 1px solid var(--border-color); margin: 0.5rem 0; }
        #price-summary #discount-applied { color: var(--success-color); font-weight: 500; }
        #price-summary #final-cost-display { font-weight: bold; font-size: 1.1em; color: var(--text-color-dark); }
         body.dark-mode #coupon-section input[type="text"] { background-color: #333; border-color: #555; }
         body.dark-mode #coupon-section button#apply-coupon-btn { color: #111; }
         /* Styles for Available Offer Buttons */
         .available-offer-btn {
            background-color: var(--hover-color); border: 1px dashed var(--primary-color); color: var(--primary-color);
            padding: 5px 10px; border-radius: 6px; font-size: 0.8em; cursor: pointer; transition: background-color 0.2s, color 0.2s;
         }

         /* Add style for disabled input */
         #coupon-section input[type="text"]:disabled {
             background-color: var(--border-color); /* Lighter grey */
             cursor: not-allowed;
             opacity: 0.7;
         }
         body.dark-mode #coupon-section input[type="text"]:disabled {
              background-color: #444; /* Darker grey */
         }
         .available-offer-btn code { background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 3px; font-weight: bold;}
         .available-offer-btn:hover { background-color: var(--primary-color); color: white; }
         body.dark-mode .available-offer-btn { background-color: rgba(251, 191, 36, 0.1); border-color: var(--primary-color); color: var(--primary-color); }
         body.dark-mode .available-offer-btn:hover { background-color: var(--primary-color); color: #111; }
         body.dark-mode .available-offer-btn code { background: rgba(255,255,255,0.1); }

         /* --- NEW STYLE FOR REVIEW BUTTON CONTAINER --- */
         .review-button-container {
             text-align: center;
             margin-top: 1.5rem; /* Space between green box and button */
         }
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
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['status']))); ?>
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
                                <span><?php echo htmlspecialchars($serviceData['service']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Service Item</strong>
                                <span><?php echo htmlspecialchars($serviceData['item']); ?></span>
                            </div>
                        </div>
                        <div class="card-section">
                             <div class="detail-item">
                                <strong>Full Address</strong>
                                <span><?php echo htmlspecialchars($addressLines['full']); ?></span>
                            </div>
                        </div>
                        <div class="card-section">
                            <div class="detail-item">
                                <strong>Original Cost</strong>
                                <span>₹<?php echo number_format($originalCost, 2); ?></span>
                            </div>
                            <?php if ($discountAmount > 0): ?>
                            <div class="detail-item">
                                <strong>Discount Applied <?php if ($appliedCouponCode) echo '(' . htmlspecialchars($appliedCouponCode) . ')'; ?></strong>
                                <span style="color: var(--success-color);">-₹<?php echo number_format($discountAmount, 2); ?></span>
                            </div>
                            <hr style="border: none; border-top: 1px dashed var(--border-color); margin: 5px 0;">
                            <div class="detail-item" style="margin-top: 5px;">
                                <strong>Final Cost</strong>
                                <span style="font-weight: bold; font-size: 1.1em;">₹<?php echo number_format($finalCostAfterDiscount, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item" style="margin-top: <?php echo ($discountAmount > 0) ? '1rem' : '0.5rem'; ?>;">
                                <strong>Payment Status</strong>
                                <span class="item-status <?php echo htmlspecialchars($booking['payment_status']); ?>"><?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?></span>
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
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                            <div class="participant-profile">
                                <img src="<?php echo htmlspecialchars($workerAvatar); ?>" alt="Worker">
                                <div>
                                    <div class="role">Worker</div>
                                    <div class="name"><?php echo htmlspecialchars($booking['worker_name']); ?></div>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['worker_phone'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                        </div>
                        </div>

                    <?php if ($role === 'worker'): ?>
                        <?php if ($booking['status'] === 'confirmed'): ?>
                        <div class="action-panel">
                            <h2>Arrived at Job Site?</h2>
                            <p>Once you are physically ready to start the work, update the status.</p>
                            <button onclick="handleJobAction(<?php echo $booking['id']; ?>, 'in_progress', null, this)" class="btn btn-main start-job">Start Job</button>
                        </div>
                        <?php elseif ($booking['status'] === 'in_progress' && !$booking['work_completed_by_worker']): ?>
                        <div class="action-panel">
                            <h2>Work is Finished?</h2>
                            <p>Confirm completion to notify the customer and trigger the payment step.</p>
                            <button id="mark-complete-btn" class="btn btn-main mark-complete" data-booking-id="<?php echo $booking['id']; ?>">Mark Complete</button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($role === 'customer' && $booking['work_completed_by_worker'] && $booking['payment_status'] === 'unpaid'): ?>
                        <div class="action-panel">
                            <h2>Payment Required</h2>
                            <p>The worker confirmed job completion. Please finalize payment.</p>

<div id="coupon-section" style="margin-bottom: 1.5rem; text-align: left;">
    <label for="coupon-code" style="font-weight: 500; display: block; margin-bottom: 0.5rem;">
        <?php echo ($booking['applied_offer_id'] && $appliedCouponCode) ? '' : 'Have a Coupon Code?'; ?>
    </label>
    <div style="display: flex; gap: 0.5rem;">
        <input type="text" id="coupon-code" placeholder="Enter code" style="text-transform: uppercase;"
               value="<?php echo htmlspecialchars($appliedCouponCode ?? ''); ?>"
               <?php if ($booking['applied_offer_id'] && $appliedCouponCode) echo 'disabled'; // Disable input if pre-applied ?>
               >
        <?php // Conditionally hide the Apply button if a coupon was pre-applied
        if (!($booking['applied_offer_id'] && $appliedCouponCode)): ?>
            <button id="apply-coupon-btn">Apply</button>
        <?php endif; ?>
    </div>
    <div id="coupon-message">
         <?php if ($booking['applied_offer_id'] && $discountAmount > 0): ?>
             <span style="color: var(--success-color); font-weight: 500;">Discount of ₹<?php echo number_format($discountAmount, 2); ?> applied.</span>
         <?php endif; ?>
    </div>

    <?php // Conditionally hide the Remove link if a coupon was pre-applied
    if (!($booking['applied_offer_id'] && $appliedCouponCode)): ?>
        <a href="#" id="remove-coupon-btn" style="display: none; font-size: 0.85rem; color: var(--danger-color); margin-top: 0.5rem; text-decoration: none;">Remove Coupon</a>
    <?php endif; ?>

    <div id="price-summary" style="<?php echo $discountAmount > 0 ? 'display: block;' : 'display: none;'; ?>">
         <p>Original Cost: <span id="original-cost">₹<?php echo number_format($originalCost, 2); ?></span></p>
         <p>Discount: <span id="discount-applied">-₹<?php echo number_format($discountAmount, 2); ?></span></p>
         <hr>
         <p>New Total: <span id="final-cost-display">₹<?php echo number_format($finalCostAfterDiscount, 2); ?></span></p>
    </div>
     <?php // This condition already correctly hides this section
     if (!($booking['applied_offer_id'] && $appliedCouponCode)): ?>
         <div id="available-offers-container-details" style="margin-top: 1rem;"></div>
     <?php endif; ?>
</div>

                            <button id="pay-now-btn" class="btn btn-main" data-booking-id="<?php echo $booking['id']; ?>" data-final-amount="<?php echo $finalCostAfterDiscount; ?>">
                                Pay Now ₹<?php echo number_format($finalCostAfterDiscount, 2); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($booking['status'] === 'completed' && $booking['payment_status'] === 'paid'): ?>
                       <div class="action-panel" style="border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);">
                            <h2><i class="fas fa-check-circle" style="color: #10b981;"></i> Job Completed & Paid</h2>
                            <p style="color: #10b981;">This transaction is complete. Thank you for using DailyFix.</p>
                            <a href="/dailyfix/generate_invoice.php?id=<?php echo $booking['id']; ?>" target="_blank" class="btn btn-invoice">
                                <i class="fas fa-file-invoice"></i> Download Invoice
                            </a>
                      </div> <?php // End of the green action-panel div ?>

                       <?php // MOVED Review Button outside the green box ?>
                       <?php if ($role === 'customer' && !$booking['review_id']): ?>
                           <div class="review-button-container"> <?php // Existing container for centering/margin ?>
                               <button class="btn btn-main" onclick="openReviewModal(<?php echo $booking['id']; ?>)">
                                   <i class="fas fa-star"></i> Leave a Review
                               </button>
                           </div>
                       <?php endif; ?>
                    <?php endif; // End check for completed & paid ?>

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

        <div id="reviewModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeReviewModal()">&times;</span>
                <h2>Leave a Review</h2>
                <form id="reviewForm">
                    <input type="hidden" id="bookingId" name="booking_id">
                    <div class="rating">
                        <i class="fas fa-star" data-rating="1"></i>
                        <i class="fas fa-star" data-rating="2"></i>
                        <i class="fas fa-star" data-rating="3"></i>
                        <i class="fas fa-star" data-rating="4"></i>
                        <i class="fas fa-star" data-rating="5"></i>
                    </div>
                    <input type="hidden" id="ratingValue" name="rating">
                    <textarea name="comment" placeholder="Share your experience..." rows="4"></textarea>
                    <button type="submit">Submit Review</button>
                </form>
            </div>
        </div>

    <script>
    // --- Utility Functions (Modals) ---
    function showStatusModal(status, title, message) {
        const modal = document.getElementById('status-modal');
        if (!modal) return;
        const icon = document.getElementById('status-modal-icon');
        const titleEl = document.getElementById('status-modal-title');
        const messageEl = document.getElementById('status-modal-message');
        const closeBtn = document.getElementById('status-modal-close-btn');

        icon.className = 'modal-icon-custom fas'; // Reset classes
        if (status === 'success') icon.classList.add('fa-check-circle', 'success');
        else if (status === 'error') icon.classList.add('fa-exclamation-triangle', 'error');
        else icon.classList.add('fa-info-circle', 'warning');

        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.classList.add('show');

        // Clean up previous listener before adding new one
        const newCloseBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);

        newCloseBtn.onclick = function() {
            modal.classList.remove('show');
            if (status === 'success') {
                // Reload on success for status updates, payment, coupon removal, review submission
                window.location.reload();
            }
        };
         modal.onclick = function(event) {
             if (event.target == modal) {
                 newCloseBtn.onclick();
             }
         };
    }

    function showConfirmationModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmation-modal');
         if (!modal) return;
        const titleEl = document.getElementById('confirm-modal-title');
        const messageEl = document.getElementById('confirm-modal-message');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const cancelBtn = document.getElementById('modal-cancel-btn');
        const closeBtn = modal.querySelector('.modal-close-icon');

        titleEl.textContent = title;
        messageEl.innerHTML = message; // Use innerHTML for potential bold tags
        modal.classList.add('show');

        // Remove previous listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        const newCancelBtn = cancelBtn.cloneNode(true);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        const newCloseBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);

        const confirmHandler = () => {
            onConfirm();
            modal.classList.remove('show');
        };
        const cancelHandler = () => {
            modal.classList.remove('show');
        };

        newConfirmBtn.addEventListener('click', confirmHandler);
        newCancelBtn.addEventListener('click', cancelHandler);
        newCloseBtn.addEventListener('click', cancelHandler);

        modal.onclick = function(event) {
          if (event.target == modal) {
            cancelHandler();
          }
        }
    }

    // --- handleJobAction (for Worker status updates) ---
    function handleJobAction(bookingId, status, bookingTime, buttonElement) {
        const actionContainer = buttonElement.closest('.job-card-actions') || buttonElement.closest('.action-panel');
        const buttonsInContainer = actionContainer ? actionContainer.querySelectorAll('.btn, button') : [buttonElement];
        const originalTexts = {};

        buttonsInContainer.forEach(btn => {
            originalTexts[btn.innerHTML] = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        });

        let url = `/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`;
        if (status === 'confirmed' && bookingTime) {
            url += `&booking_time=${encodeURIComponent(bookingTime)}`;
        }

        fetch(url)
            .then(res => {
                if (!res.ok) {
                   return res.json().then(errData => { throw new Error(errData.message || `HTTP error ${res.status}`); });
                }
                return res.json();
             })
            .then(data => {
                if (data.status === 'success') {
                     // Reload will show updated status
                     showStatusModal('success', 'Status Updated', 'Booking status changed successfully.');
                } else {
                     throw new Error(data.message || 'Could not update status.');
                }
            })
            .catch((error) => {
                 console.error("Job Action Error:", error);
                 showStatusModal('error', 'Update Failed', error.message || 'A network error occurred.');
                 // Restore buttons on failure
                 buttonsInContainer.forEach(btn => {
                    btn.disabled = false;
                    // Find the original HTML safely
                    let originalHTML = 'Action'; // Default fallback
                     for (const html in originalTexts) {
                        if (originalTexts.hasOwnProperty(html) && !html.includes('fa-spinner')) {
                             originalHTML = html;
                             break;
                        }
                     }
                    btn.innerHTML = originalHTML;
                });
            });
    }

     // --- displayAvailableOffers (slightly adapted for details page) ---
    function displayAvailableOffersDetails(offers) {
        const offersContainer = document.getElementById('available-offers-container-details');
        if (!offersContainer) return;

        if (offers.length === 0) {
            offersContainer.innerHTML = ''; return;
        }

        let offersHtml = '<p style="font-size: 0.9em; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light);">Available Offers:</p>';
        offersHtml += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';

        const currentCost = parseFloat(<?php echo json_encode($originalCost); ?>); // Use original booking cost here

        offers.forEach(offer => {
            let offerText = '';
            if (offer.discount_type === 'percentage') {
                offerText = `${parseFloat(offer.discount_value)}% off`;
            } else {
                offerText = `₹${parseFloat(offer.discount_value).toFixed(2)} off`;
            }
             if (parseFloat(offer.min_booking_amount) > 0) {
                 offerText += ` (min ₹${parseFloat(offer.min_booking_amount).toFixed(2)})`;
             }

             // Check if applicable based on original booking cost
             let canApply = currentCost >= parseFloat(offer.min_booking_amount);
             let titleText = canApply ? `Click to apply ${offer.coupon_code}` : `Requires min ₹${parseFloat(offer.min_booking_amount).toFixed(2)} booking value`;

            offersHtml += `<button type="button" class="available-offer-btn" data-code="${offer.coupon_code}" title="${titleText}" ${!canApply ? 'disabled style="opacity:0.5; cursor: not-allowed; border-style: dotted;"' : ''}>
                              <code>${offer.coupon_code}</code>: ${offerText}
                           </button>`;
        });
        offersHtml += '</div>';
        offersContainer.innerHTML = offersHtml;

        offersContainer.querySelectorAll('.available-offer-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.dataset.code;
                const couponInput = document.getElementById('coupon-code');
                const applyBtn = document.getElementById('apply-coupon-btn');
                // Ensure elements exist and apply button is not disabled (meaning not already applied/hidden)
                if (couponInput && applyBtn && !applyBtn.disabled && (!applyBtn.style.display || applyBtn.style.display !== 'none')) { // Added display check
                    couponInput.value = code;
                    applyBtn.click();
                }
            });
        });
    }

    // --- Review Modal Functions ---
    function openReviewModal(bookingId) {
        document.getElementById('bookingId').value = bookingId;
        document.getElementById('reviewModal').style.display = 'flex';
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
        // Reset form for next time
        const reviewForm = document.getElementById('reviewForm');
        if(reviewForm) reviewForm.reset();
        const stars = document.querySelectorAll('#reviewModal .rating .fa-star');
        stars.forEach(s => s.classList.remove('selected'));
        const ratingValueInput = document.getElementById('ratingValue');
        if(ratingValueInput) ratingValueInput.value = '';
    }
    // --- End Review Modal Functions ---


    // --- *** SINGLE DOMContentLoaded Listener *** ---
    document.addEventListener('DOMContentLoaded', function() {

        // --- Variable Declarations ---
        const markCompleteBtn = document.getElementById('mark-complete-btn');
        const payNowBtnCustomer = document.getElementById('pay-now-btn');
        const applyCouponBtn = document.getElementById('apply-coupon-btn');
        const couponCodeInput = document.getElementById('coupon-code');
        const couponMessageDiv = document.getElementById('coupon-message');
        const priceSummaryDiv = document.getElementById('price-summary');
        const originalCostSpan = document.getElementById('original-cost');
        const discountAppliedSpan = document.getElementById('discount-applied');
        const finalCostDisplaySpan = document.getElementById('final-cost-display');
        const removeCouponBtn = document.getElementById('remove-coupon-btn');
        const availableOffersContainerDetails = document.getElementById('available-offers-container-details');

        // Review Modal Variables
        const reviewForm = document.getElementById('reviewForm');
        const reviewStars = document.querySelectorAll('#reviewModal .rating .fa-star');
        const ratingValueInput = document.getElementById('ratingValue');


        // Get initial costs and state from PHP
        let currentBookingCost = parseFloat(<?php echo json_encode($originalCost); ?>);
        let finalCostAfterDiscount = parseFloat(<?php echo json_encode($finalCostAfterDiscount); ?>);
        const isCouponPreApplied = <?php echo json_encode($booking['applied_offer_id'] && $appliedCouponCode); ?>;


        // --- Fetch and display available offers (if payment panel exists AND coupon not pre-applied) ---
         if (payNowBtnCustomer && !isCouponPreApplied && availableOffersContainerDetails) {
              const workerIdForOffers = <?php echo json_encode($booking['worker_id']); ?>;
              if (workerIdForOffers) {
                  fetch(`/dailyfix/api/get_worker_offers.php?worker_id=${workerIdForOffers}`)
                     .then(res => res.json())
                     .then(result => {
                         if (result.status === 'success' && result.data) {
                             displayAvailableOffersDetails(result.data); // Use the details version
                         } else {
                              availableOffersContainerDetails.innerHTML = ''; // Clear if no offers
                         }
                     })
                     .catch(err => {
                        console.error("Error fetching available offers:", err);
                        availableOffersContainerDetails.innerHTML = '<p style="font-size: 0.8em; color: var(--danger-color);">Could not load offers.</p>';
                     });
              }
         } else if (availableOffersContainerDetails) {
              availableOffersContainerDetails.innerHTML = ''; // Ensure it's empty if coupon pre-applied
         }

        // --- Event Listeners ---

        // Worker: Mark Job Complete
        if (markCompleteBtn) {
            markCompleteBtn.addEventListener('click', function() {
                const button = this;
                const originalText = button.textContent;

                const processAction = () => {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    const bookingId = button.dataset.bookingId;
                    const formData = new FormData();
                    formData.append('booking_id', bookingId);

                    fetch('/dailyfix/api/mark-work-done.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showStatusModal('success', 'Work Completed!', 'The customer has been notified for payment.');
                                // Reload is handled by showStatusModal on success
                            } else {
                                showStatusModal('error', 'Update Failed', data.message || 'Could not mark work as complete.');
                                button.disabled = false;
                                button.innerHTML = originalText;
                            }
                        })
                        .catch(() => {
                            showStatusModal('error', 'Network Error', 'A network error occurred.');
                            button.disabled = false;
                            button.innerHTML = originalText;
                        });
                };

                showConfirmationModal(
                    'Confirm Completion',
                    'Are you sure you want to mark this job as complete? The customer will be prompted for payment.',
                    processAction
                );
            });
        }

        // Customer: Apply Coupon
        if (applyCouponBtn && couponCodeInput && payNowBtnCustomer && removeCouponBtn) { // Ensure removeCouponBtn exists
            applyCouponBtn.addEventListener('click', function() {
                const code = couponCodeInput.value.trim().toUpperCase();
                const bookingId = payNowBtnCustomer.dataset.bookingId;
                if (!code) {
                    couponMessageDiv.textContent = 'Please enter a coupon code.';
                    couponMessageDiv.style.color = 'var(--danger-color)';
                    return;
                }

                const button = this;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
                couponMessageDiv.textContent = '';

                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('coupon_code', code);

                fetch('/dailyfix/api/validate_apply_offer.php', { method: 'POST', body: formData })
                     .then(res => res.json().then(body => ({ ok: res.ok, body })))
                     .then(({ ok, body }) => {
                          button.disabled = false; // Re-enable first

                         if (ok && body.status === 'success') {
                             couponMessageDiv.textContent = body.message;
                             couponMessageDiv.style.color = 'var(--success-color)';
                             button.textContent = 'Applied';
                             button.style.backgroundColor = 'var(--success-color)'; // Optional visual cue
                             button.style.color = 'white'; // Optional visual cue
                             button.disabled = true; // Disable after applying
                             couponCodeInput.disabled = true; // Disable input
                             if(availableOffersContainerDetails) availableOffersContainerDetails.style.display = 'none'; // Hide available offers


                             originalCostSpan.textContent = `₹${body.original_cost}`;
                             discountAppliedSpan.textContent = `-₹${body.discount_amount}`;
                             finalCostDisplaySpan.textContent = `₹${body.final_cost_after_discount}`;
                             priceSummaryDiv.style.display = 'block';

                             const finalAmount = parseFloat(body.final_cost_after_discount.replace(/,/g, ''));
                             payNowBtnCustomer.textContent = `Pay Now ₹${finalAmount.toFixed(2)}`;
                             payNowBtnCustomer.dataset.finalAmount = finalAmount;
                             finalCostAfterDiscount = finalAmount; // Update JS state

                             removeCouponBtn.style.display = 'inline'; // Show remove button
                             removeCouponBtn.removeAttribute('data-pre-applied'); // Ensure it's not marked as pre-applied

                         } else {
                             couponMessageDiv.textContent = body.message || `Error applying coupon.`;
                             couponMessageDiv.style.color = 'var(--danger-color)';
                             button.innerHTML = 'Apply'; // Reset button text
                             button.style.backgroundColor = ''; // Reset styles if they were changed
                             button.style.color = '';
                             priceSummaryDiv.style.display = 'none'; // Hide summary
                             payNowBtnCustomer.textContent = `Pay Now ₹${currentBookingCost.toFixed(2)}`;
                             payNowBtnCustomer.dataset.finalAmount = currentBookingCost;
                             finalCostAfterDiscount = currentBookingCost; // Update JS state
                             removeCouponBtn.style.display = 'none';
                         }
                    })
                    .catch((error) => {
                        console.error("Coupon Apply Error:", error);
                        couponMessageDiv.textContent = 'A network error occurred.';
                        couponMessageDiv.style.color = 'var(--danger-color)';
                        button.disabled = false;
                        button.innerHTML = 'Apply';
                        button.style.backgroundColor = '';
                        button.style.color = '';
                        priceSummaryDiv.style.display = 'none';
                         payNowBtnCustomer.textContent = `Pay Now ₹${currentBookingCost.toFixed(2)}`;
                         payNowBtnCustomer.dataset.finalAmount = currentBookingCost;
                         finalCostAfterDiscount = currentBookingCost;
                         removeCouponBtn.style.display = 'none';
                    });
            });
        }

        // Customer: Remove Coupon (Add listener only if the button exists)
        if (removeCouponBtn && payNowBtnCustomer) {
            removeCouponBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Check if the coupon was pre-applied
                if (removeCouponBtn.hasAttribute('data-pre-applied')) {
                     // Optionally show a message that pre-applied coupons can't be removed, or just do nothing.
                     // For now, let's just prevent the action.
                     return;
                }


                const bookingId = payNowBtnCustomer.dataset.bookingId;
                const buttonLink = this;
                const originalLinkText = 'Remove Coupon';

                buttonLink.style.pointerEvents = 'none'; // Prevent double clicks
                buttonLink.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                couponMessageDiv.textContent = ''; // Clear message area

                const formData = new FormData();
                formData.append('booking_id', bookingId);

                fetch('/dailyfix/api/remove_worker_offers.php', { method: 'POST', body: formData })
                    .then(res => res.json().then(body => ({ ok: res.ok, body })))
                    .then(({ ok, body }) => {
                        if (ok && body.status === 'success') {
                             // --- UI Reset on SUCCESSFUL Removal ---
                             couponCodeInput.value = '';
                             couponCodeInput.disabled = false;
                             if(applyCouponBtn) { // Check if apply button exists before manipulating
                                 applyCouponBtn.disabled = false;
                                 applyCouponBtn.innerHTML = 'Apply';
                                 applyCouponBtn.style.backgroundColor = ''; // Reset style
                                 applyCouponBtn.style.color = ''; // Reset style
                             }
                             couponMessageDiv.textContent = body.message || 'Coupon removed!';
                             couponMessageDiv.style.color = 'var(--info-color)'; // Use info color
                             buttonLink.style.display = 'none'; // Hide remove button again
                             priceSummaryDiv.style.display = 'none';

                             // Reset pay button and final cost variable
                             finalCostAfterDiscount = currentBookingCost; // Reset to original cost
                             payNowBtnCustomer.textContent = `Pay Now ₹${currentBookingCost.toFixed(2)}`;
                             payNowBtnCustomer.dataset.finalAmount = currentBookingCost;

                             // Re-fetch and display available offers
                             if(availableOffersContainerDetails) {
                                 availableOffersContainerDetails.style.display = 'block'; // Show available offers section
                                 const workerIdForOffers = <?php echo json_encode($booking['worker_id']); ?>;
                                 if (workerIdForOffers) {
                                      fetch(`/dailyfix/api/get_worker_offers.php?worker_id=${workerIdForOffers}`)
                                         .then(res => res.json())
                                         .then(result => {
                                             if (result.status === 'success' && result.data) {
                                                 displayAvailableOffersDetails(result.data);
                                             } else {
                                                  availableOffersContainerDetails.innerHTML = '';
                                             }
                                         }).catch(()=>{/* handle error quietly */});
                                 }
                             }
                              // --- End UI Reset ---
                             // showStatusModal('success', 'Coupon Removed', body.message); // Can use this instead of inline message
                        } else {
                            couponMessageDiv.textContent = body.message || `Error removing coupon.`;
                            couponMessageDiv.style.color = 'var(--danger-color)';
                            buttonLink.innerHTML = originalLinkText; // Reset on error
                            buttonLink.style.pointerEvents = 'auto'; // Re-enable click
                        }
                    })
                    .catch(() => {
                        couponMessageDiv.textContent = 'Network error while removing coupon.';
                        couponMessageDiv.style.color = 'var(--danger-color)';
                        buttonLink.innerHTML = originalLinkText; // Reset on error
                        buttonLink.style.pointerEvents = 'auto'; // Re-enable click
                    });
            });
        }


        // Customer: Pay Now (Initialization and Listener)
        if (payNowBtnCustomer) {
             // Set initial button text based on potentially pre-applied discount
             payNowBtnCustomer.textContent = `Pay Now ₹${parseFloat(finalCostAfterDiscount).toFixed(2)}`;

             // Display summary/update button state if discount was applied via PHP on load
             if (isCouponPreApplied) { // Use the PHP check result
                 originalCostSpan.textContent = `₹${currentBookingCost.toFixed(2)}`;
                 discountAppliedSpan.textContent = `-₹${(<?php echo json_encode($discountAmount); ?>).toFixed(2)}`;
                 finalCostDisplaySpan.textContent = `₹${parseFloat(finalCostAfterDiscount).toFixed(2)}`;
                 priceSummaryDiv.style.display = 'block';
                 if (applyCouponBtn) { // Should not exist if pre-applied, but check anyway
                     applyCouponBtn.style.display = 'none'; // Hide apply button
                 }
                 if (removeCouponBtn) {
                     removeCouponBtn.style.display = 'inline'; // Show remove button
                     removeCouponBtn.setAttribute('data-pre-applied', 'true'); // Mark it as pre-applied
                 }
                 if (couponCodeInput) couponCodeInput.disabled = true; // Disable input
                 if(availableOffersContainerDetails) availableOffersContainerDetails.style.display = 'none'; // Hide available offers
             }

            // Attach Pay Now click listener
            payNowBtnCustomer.addEventListener('click', function() {
                const button = this;
                const originalHTML = button.innerHTML;
                // Use the JS variable 'finalCostAfterDiscount' which is updated by apply/remove logic OR initialized by PHP
                 const amountToPay = parseFloat(finalCostAfterDiscount).toFixed(2);

                const processPayment = () => {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                    const bookingId = button.dataset.bookingId;
                    const formData = new FormData();
                    formData.append('booking_id', bookingId);

                    fetch('/dailyfix/api/process-static-payment.php', { method: 'POST', body: formData })
                        .then(res => res.json().then(body => ({ ok: res.ok, body })))
                        .then(({ ok, body }) => {
                            if (ok && body.status === 'success') {
                                showStatusModal('success', 'Payment Successful!', body.message || 'Payment processed.');
                                // Reload handled by modal
                            } else {
                                showStatusModal('error', 'Payment Failed', body.message || `Payment processing error.`);
                                button.disabled = false;
                                button.innerHTML = originalHTML;
                            }
                        })
                        .catch(() => {
                            showStatusModal('error', 'Network Error', 'An unexpected network error occurred.');
                            button.disabled = false;
                            button.innerHTML = originalHTML;
                        });
                };

                showConfirmationModal(
                    'Confirm Payment',
                    `You are about to authorize a payment of <strong>₹${amountToPay}</strong>. Proceed?`,
                    processPayment
                );
            });
        }

        // Review Modal Star Click Logic
        reviewStars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.getAttribute('data-rating');
                if(ratingValueInput) ratingValueInput.value = rating;
                reviewStars.forEach(s => {
                    s.classList.toggle('selected', s.getAttribute('data-rating') <= rating);
                });
            });
        });

        // Review Form Submit Logic
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const submitButton = form.querySelector('button[type="submit"]');
                const data = Object.fromEntries(new FormData(form).entries());

                if (!data.rating) {
                    // Use the existing showStatusModal for consistency
                    showStatusModal('error', 'Validation Error', 'Please select a star rating to submit your review.');
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';

                fetch('/dailyfix/api/submit_review.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json().then(body => ({ ok: response.ok, body })))
                    .then(({ ok, body }) => {
                        closeReviewModal(); // Close modal first
                        if (ok && body.status === 'success') {
                             showStatusModal('success', 'Review Submitted!', body.message || 'Thank you for your feedback.'); // Reloads on OK
                        } else {
                            throw new Error(body.message || 'An unknown error occurred.');
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting review:', error);
                        // Show error *after* closing modal
                        showStatusModal('error', 'Submission Failed', error.message);
                        // Re-enable button on failure
                        submitButton.disabled = false;
                        submitButton.textContent = 'Submit Review';
                    });
            });
        }

    });
    </script>
    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>