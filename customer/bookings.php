<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Fetch customer's bookings
$bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT
            b.*,
            w.full_name as worker_name,
            w.profile_image as worker_avatar,
            r.id as review_id
        FROM public.bookings b
        JOIN public.users w ON b.worker_id = w.id
        LEFT JOIN public.reviews r ON b.id = r.booking_id
        WHERE b.customer_id = ?
        ORDER BY b.booking_time DESC
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Customer bookings fetch error: " . $e->getMessage());
}

// --- NEW DEBT CHECK ---
$hasOutstandingDebt = false;
try {
    $stmt_debt_check = $conn->prepare("
        SELECT COUNT(*) FROM public.bookings
        WHERE customer_id = ? 
        AND status = 'completed'
        AND payment_status = 'pending' 
    ");
    // Assuming $userId is available from included header/session check
    $stmt_debt_check->execute([$userId]); 
    if ($stmt_debt_check->fetchColumn() > 0) {
        $hasOutstandingDebt = true;
    }
} catch (PDOException $e) {
    error_log("Customer debt check failed: " . $e->getMessage());
    // Continue even on error, so user isn't blocked by a temporary DB issue
}
// --- END NEW DEBT CHECK ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Bookings - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/header.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style>
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
        margin-top: 80px; 
    }

    /* Shimmer animation */
    @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
    .skeleton {
        animation: shimmer 1.5s infinite linear;
        background: linear-gradient(to right, 
        var(--hover-color, #f0f0f0) 8%, 
        var(--border-color, #e2e8f0) 18%, 
        var(--hover-color, #f0f0f0) 33%);
        background-size: 800px 104px; border-radius: 6px;
    }

    /* Customer Bookings Page Specific Skeleton Layout */
    .skeleton-title-bar { 
        display: flex; justify-content: space-between; align-items: center; margin: 2rem 0; 
    }
    .skeleton-title { height: 38px; width: 300px; }
    .skeleton-tabs { display: flex; gap: 1rem; height: 36px; margin-bottom: 2rem; }
    .skeleton-tab-item { width: 120px; height: 100%; }
    
    .skeleton-booking-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;
    }
    .skeleton-booking-card { height: 250px; }
    @media (max-width: 768px) {
        .skeleton-booking-grid { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton-title-bar">
            <div class="skeleton skeleton-title"></div>
            </div>
            
            <div class="skeleton-tabs">
            <div class="skeleton skeleton-tab-item"></div>
            <div class="skeleton skeleton-tab-item"></div>
            <div class="skeleton skeleton-tab-item"></div>
            </div>
            
            <div class="skeleton-booking-grid">
            <div class="skeleton skeleton-booking-card"></div>
            <div class="skeleton skeleton-booking-card"></div>
            <div class="skeleton skeleton-booking-card"></div>
            <div class="skeleton skeleton-booking-card"></div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">My Bookings</h1>
            <?php if ($hasOutstandingDebt): ?>
                <div class="alert debt-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Payment Required!</strong> You have outstanding payments for completed services. Please view details and complete payment to be eligible for new bookings.</p>
                </div>
            <?php endif; ?>
            <?php if (count($bookings) > 0) : ?>
                <div class="job-card-grid">
                    <?php foreach ($bookings as $booking) : ?>
                        <div class="job-card">
                            <div class="job-card-header">
                                <img src="/dailyfix/<?php echo htmlspecialchars($booking['worker_avatar'] ?: 'assets/images/default-avatar.png'); ?>" alt="Worker" class="job-card-avatar">
                                <div class="job-card-customer-info">
                                    <h3><?php echo htmlspecialchars($booking['worker_name']); ?></h3>
                                    <p>Booked for: 
                                        <?php $bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC')); // Specify it's UTC
                                        $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata')); // Convert to local
                                        echo $bookingTime->format("D, M j, Y, g:i A"); // Now format the local time 
                                        ?></p>
                                </div>
                            </div>
                            <div class="job-card-body">
                                <p><strong>Details:</strong> <br><?php echo nl2br(htmlspecialchars($booking['service_details'])); ?></p>

                                <?php

                                $originalCost = (float)($booking['final_cost'] ?? 0.00);
                                $discountAmount = (float)($booking['discount_amount'] ?? 0.00);
                                $finalCostAfterDiscount = max(0, $originalCost - $discountAmount);
                                ?>

                                <p style="margin-top: 10px;"><strong>Base Cost:</strong> ₹<?php echo number_format($originalCost, 2); ?></p>
                                
                                <?php if ($discountAmount > 0): ?>
                                    <p><strong>Discount:</strong> <span style="color: var(--success-color);">-₹<?php echo number_format($discountAmount, 2); ?></span></p>
                                    <p><strong>Final Cost:</strong> <strong style="font-size: 1.05em;">₹<?php echo number_format($finalCostAfterDiscount, 2); ?></strong></p>
                                <?php endif; ?>

                                <?php if (!empty($booking['rejection_reason'])): ?>
                                    <p style="margin-top: 10px;"><strong>Rejection Reason:</strong> <br><span style="color: var(--danger-color); font-style: italic;"><?php echo htmlspecialchars($booking['rejection_reason']); ?></span></p>
                                <?php endif; ?>

                                <?php if (!empty($booking['cancellation_reason'])): ?>
                                    <p style="margin-top: 10px;"><strong>Cancellation Reason:</strong> <br><span style="color: var(--danger-color); font-style: italic;"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></span></p>
                                <?php endif; ?>
                                <p style="margin-top: 10px;"><strong>Status:</strong> <span class="item-status <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
                            </div>

                            <div class="job-card-actions">
                                <a href="/dailyfix/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-main">View Details</a>
                                
                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])) : ?>
                                    <button class="btn cancel" onclick="openCancelModal(<?php echo $booking['id']; ?>)">Cancel Booking</button>
                                <?php endif; ?>

                                <?php if ($booking['status'] === 'completed' && $booking['payment_status'] === 'paid' && !$booking['review_id']) : ?>
                                    <button class="btn accept" onclick="openReviewModal(<?php echo $booking['id']; ?>)">Leave a Review</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't booked any services. <a href="/dailyfix/customer/services.php">Find a service</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

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
    
    <div id="cancelModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeCancelModal()">&times;</span>
            <h2>Cancel Booking</h2>
            <p>Please provide a mandatory reason for cancelling the booking.</p>
            <form id="cancelForm">
                <input type="hidden" id="cancelBookingId" name="booking_id">
                <div class="form-group">
                    <textarea id="cancellationReason" name="cancellation_reason" placeholder="Reason for cancellation..." rows="4" required></textarea>
                </div>
                <button type="submit" class="btn cancel">Confirm Cancellation</button>
            </form>
        </div>
    </div>
    
    <div id="messageModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeMessageModal()">&times;</span>
            <h2 id="messageModalTitle"></h2>
            <p id="messageModalText"></p>
            <button id="messageModalButton" class="btn btn-main">OK</button>
        </div>
    </div>

    <script>
    // --- Review Modal Functions (Existing) ---
    function openReviewModal(bookingId) {
        document.getElementById('bookingId').value = bookingId;
        document.getElementById('reviewModal').style.display = 'flex';
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
        // Reset form for next time
        document.getElementById('reviewForm').reset();
        stars.forEach(s => s.classList.remove('selected'));
        document.getElementById('ratingValue').value = '';
    }

    const stars = document.querySelectorAll('#reviewModal .rating .fa-star');
    stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = star.getAttribute('data-rating');
            document.getElementById('ratingValue').value = rating;
            stars.forEach(s => {
                s.classList.toggle('selected', s.getAttribute('data-rating') <= rating);
            });
        });
    });

    // --- NEW Cancellation Modal Functions ---
    function openCancelModal(bookingId) {
        document.getElementById('cancelBookingId').value = bookingId;
        document.getElementById('cancelModal').style.display = 'flex';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        document.getElementById('cancelForm').reset();
    }

    // --- Message Modal Functions (Existing) ---
    const messageModal = document.getElementById('messageModal');
    const messageModalTitle = document.getElementById('messageModalTitle');
    const messageModalText = document.getElementById('messageModalText');
    const messageModalButton = document.getElementById('messageModalButton');
    const messageModalContent = messageModal.querySelector('.modal-content');

    function showMessageModal(title, message, type = 'error') {
        messageModalTitle.textContent = title;
        messageModalText.textContent = message;

        messageModalContent.classList.remove('success', 'error');
        messageModalContent.classList.add(type); // 'success' or 'error'

        messageModal.style.display = 'flex';

        // Set button behavior
        messageModalButton.onclick = () => {
            closeMessageModal();
            if (type === 'success') {
                window.location.reload(); // Reload only on success
            }
        };
    }

    function closeMessageModal() {
        messageModal.style.display = 'none';
    }


    // --- UPDATED Review Form Submit Event Listener (Existing) ---
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const submitButton = form.querySelector('button[type="submit"]');
        const data = Object.fromEntries(new FormData(form).entries());

        if (!data.rating) {
            showMessageModal('Validation Error', 'Please select a star rating to submit your review.');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';

        fetch('/dailyfix/api/submit_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                return response.json().then(body => ({ ok: response.ok, body }));
            })
            .then(({ ok, body }) => {
                closeReviewModal();
                if (ok) {
                    showMessageModal('Success!', body.message, 'success');
                } else {
                    throw new Error(body.message || 'An unknown error occurred.');
                }
            })
            .catch(error => {
                console.error('Error submitting review:', error);
                closeReviewModal();
                showMessageModal('Submission Failed', error.message, 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Submit Review';
            });
    });
    
    // --- NEW Cancellation Form Submit Event Listener ---
    document.getElementById('cancelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const submitButton = form.querySelector('button[type="submit"]');
        const data = Object.fromEntries(new FormData(form).entries());
        
        if (!data.cancellation_reason.trim()) {
            showMessageModal('Validation Error', 'Cancellation reason is mandatory.');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Cancelling...';

        // NOTE: This calls the new customer cancellation API logic
        fetch('/dailyfix/api/customer_cancel_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: parseInt(data.booking_id),
                    cancellation_reason: data.cancellation_reason.trim()
                })
            })
            .then(response => {
                return response.json().then(body => ({ ok: response.ok, body }));
            })
            .then(({ ok, body }) => {
                closeCancelModal();
                if (ok) {
                    showMessageModal('Cancellation Success', body.message, 'success');
                } else {
                    throw new Error(body.message || 'Cancellation failed. An unknown error occurred.');
                }
            })
            .catch(error => {
                console.error('Error submitting cancellation:', error);
                closeCancelModal();
                showMessageModal('Cancellation Failed', error.message, 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Confirm Cancellation';
            });
    });
    </script>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>