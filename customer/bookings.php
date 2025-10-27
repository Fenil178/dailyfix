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
</head>
<body>
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
                                <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($booking['service_details'])); ?></p>
                                <p><strong>Status:</strong> <span class="item-status <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></p>
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