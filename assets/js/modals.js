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