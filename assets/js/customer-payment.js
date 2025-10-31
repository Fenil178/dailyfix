// This is the most complex file, handling offers, coupons, and payment.
function displayAvailableOffersDetails(offers) {
    const offersContainer = document.getElementById('available-offers-container-details');
    if (!offersContainer) return;

    if (offers.length === 0) {
        offersContainer.innerHTML = ''; return;
    }

    let offersHtml = '<p style="font-size: 0.9em; font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light);">Available Offers:</p>';
    offersHtml += '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">';

    // *** CHANGED: Read from config object ***
    const currentCost = parseFloat(window.bookingPageConfig.originalCost);

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


// --- *** DOMContentLoaded Listener for Customer Payment *** ---
document.addEventListener('DOMContentLoaded', function() {

    // Check if the config object is loaded
    if (typeof window.bookingPageConfig === 'undefined') {
        console.error('Booking Config Object not found!');
        return;
    }
    
    // --- Variable Declarations ---
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

    // *** CHANGED: Get initial costs and state from config object ***
    let currentBookingCost = parseFloat(window.bookingPageConfig.originalCost);
    let finalCostAfterDiscount = parseFloat(window.bookingPageConfig.finalCostAfterDiscount);
    const isCouponPreApplied = window.bookingPageConfig.isCouponPreApplied;

    // --- Fetch and display available offers (if payment panel exists AND coupon not pre-applied) ---
     if (payNowBtnCustomer && !isCouponPreApplied && availableOffersContainerDetails) {
          // *** CHANGED: Read from config object ***
          const workerIdForOffers = window.bookingPageConfig.workerId;
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
                             // *** CHANGED: Read from config object ***
                             const workerIdForOffers = window.bookingPageConfig.workerId;
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
             // *** CHANGED: Read from config object ***
             discountAppliedSpan.textContent = `-₹${parseFloat(window.bookingPageConfig.discountAmount).toFixed(2)}`;
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
});