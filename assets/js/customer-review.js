// --- Review Modal Functions ---
// These are called by inline onclick="...", so they must be in the global scope.
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


// --- *** DOMContentLoaded Listener for Customer Review *** ---
document.addEventListener('DOMContentLoaded', function() {

    // Review Modal Variables
    const reviewForm = document.getElementById('reviewForm');
    const reviewStars = document.querySelectorAll('#reviewModal .rating .fa-star');
    const ratingValueInput = document.getElementById('ratingValue');

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