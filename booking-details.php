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

                        <?php if ($booking['status'] === 'pending'): ?>
                        <div class="action-panel">
                            <h2>New Job Request</h2>
                            <p>Review the details and either accept or decline this new request.</p>
                            <div classs="action-buttons-container" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                <button onclick="handleJobAction(<?php echo $booking['id']; ?>, 'confirmed', null, this)" class="btn btn-main" style="flex: 1;">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button onclick="handleJobAction(<?php echo $booking['id']; ?>, 'rejected', null, this)" class="btn btn-secondary" style="flex: 1; background: var(--danger-color-light); border-color: var(--danger-color-light); color: var(--danger-color-dark);">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

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
        // STEP 1: This object passes all dynamic PHP data to your external JS files
        window.bookingPageConfig = {
            // General Info
            bookingId: <?php echo json_encode($booking['id']); ?>,
            role: <?php echo json_encode($role); ?>,
            
            // Payment & Coupon Data
            originalCost: <?php echo json_encode($originalCost); ?>,
            finalCostAfterDiscount: <?php echo json_encode($finalCostAfterDiscount); ?>,
            discountAmount: <?php echo json_encode($discountAmount); ?>,
            isCouponPreApplied: <?php echo json_encode($booking['applied_offer_id'] && $appliedCouponCode); ?>,
            workerId: <?php echo json_encode($booking['worker_id']); ?>
        };
    </script>

    <script defer src="/dailyfix/assets/js/modals.js"></script>

    <?php if ($role === 'worker'): ?>
        
        <?php // Only load worker scripts if there are actions to perform
        if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed' || ($booking['status'] === 'in_progress' && !$booking['work_completed_by_worker'])): ?>
            <script defer src="/dailyfix/assets/js/worker-actions.js"></script>
        <?php endif; ?>

    <?php elseif ($role === 'customer'): ?>
        
        <?php // Only load payment logic if the payment panel is visible
        if ($booking['work_completed_by_worker'] && $booking['payment_status'] === 'unpaid'): ?>
            <script defer src="/dailyfix/assets/js/customer-payment.js"></script>
        <?php endif; ?>

        <?php // Only load review logic if the review button is visible
        if ($booking['status'] === 'completed' && $booking['payment_status'] === 'paid' && !$booking['review_id']): ?>
             <script defer src="/dailyfix/assets/js/customer-review.js"></script>
        <?php endif; ?>

    <?php endif; ?>

    <?php include_once __DIR__ . "/api/footer.php"; ?>
</body>
</html>