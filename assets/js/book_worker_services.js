document.addEventListener('DOMContentLoaded', function() {
    const serviceSelectionGrid = document.getElementById('service-selection-grid');
    const bookingForm = document.getElementById('booking-form');

    if (!serviceSelectionGrid || !bookingForm) {
        console.error("Service selection elements not found.");
        return;
    }

    // Add a hidden input to hold the selected service slug
    const hiddenServiceInput = document.createElement('input');
    hiddenServiceInput.type = 'hidden';
    hiddenServiceInput.name = 'selected_service_slug';
    bookingForm.appendChild(hiddenServiceInput);

    serviceSelectionGrid.addEventListener('click', function(e) {
        const clickedService = e.target.closest('.service-option');
        if (!clickedService) return;

        // Deselect all other services
        document.querySelectorAll('.service-option').forEach(option => {
            option.classList.remove('selected');
        });

        // Select the clicked service
        clickedService.classList.add('selected');
        
        // Update the hidden input value
        hiddenServiceInput.value = clickedService.dataset.slug;
    });

    // Optional: Add form validation to ensure a service is selected
    bookingForm.addEventListener('submit', function(e) {
        if (!hiddenServiceInput.value) {
            e.preventDefault();
            alert('Please select a service before submitting the booking.');
        }
    });
});