// --- handleJobAction (for Worker status updates) ---
// This function is called by inline onclick="...", so it must be in the global scope.
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


// --- *** DOMContentLoaded Listener for Worker *** ---
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if the config object is loaded (it should be)
    if (typeof window.bookingPageConfig === 'undefined') {
        console.error('Booking Config Object not found!');
        return;
    }

    const markCompleteBtn = document.getElementById('mark-complete-btn');

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

});