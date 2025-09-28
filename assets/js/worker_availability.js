document.addEventListener('DOMContentLoaded', function() {
    const availabilityTabLink = document.querySelector('[data-tab="availability"]');
    const calendarDaysContainer = document.getElementById('calendar-days');
    const timeSlotContainer = document.getElementById('time-slot-container');
    const selectedDateText = document.getElementById('selected-date-text');
    const slotsGrid = document.getElementById('slots-grid');
    
    const saveScopeToggle = document.getElementById('save-scope-toggle');
    const toggleText = document.getElementById('toggle-text');
    const saveFinalBtn = document.getElementById('save-final-btn');

    let selectedDate = null;
    let initialLoadComplete = false;

    // Exit if the necessary elements for this script aren't on the page
    if (!availabilityTabLink || !calendarDaysContainer) {
        return;
    }
    
    // This function will run when the "My Availability" tab is clicked or is active on page load
    function initializeAvailabilityTab() {
        if (initialLoadComplete) return; // Prevent re-running the setup

        generateCalendar();
        
        const firstDay = document.querySelector('.calendar-day');
        if (firstDay) {
            selectedDate = firstDay.dataset.date;
            firstDay.classList.add('selected');
            // Add 'T00:00:00' to prevent timezone shifting the date
            selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
            fetchAvailability(selectedDate);
            initialLoadComplete = true; // Mark as initialized
        }
    }

    function generateCalendar() {
        const today = new Date();
        calendarDaysContainer.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);

            const dayElement = document.createElement('div');
            dayElement.classList.add('calendar-day');
            dayElement.dataset.date = date.toISOString().split('T')[0];
            dayElement.innerHTML = `
                <div class="date-day">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                <div class="date-number">${date.getDate()}</div>
                <div class="date-month">${date.toLocaleDateString('en-US', { month: 'short' })}</div>
            `;
            calendarDaysContainer.appendChild(dayElement);

            dayElement.addEventListener('click', () => {
                selectedDate = dayElement.dataset.date;
                // Add 'T00:00:00' to prevent timezone shifting the date
                selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
                document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                dayElement.classList.add('selected');
                fetchAvailability(selectedDate);
            });
        }
    }

    function fetchAvailability(date) {
        fetch(`/dailyfix/api/get_availability.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    populateTimeSlots(data.slots);
                } else {
                    console.error('Error fetching availability:', data.message);
                    populateTimeSlots([]);
                }
            })
            .catch(error => {
                console.error('Fetch network error:', error);
                populateTimeSlots([]);
            });
    }

    function populateTimeSlots(savedSlots) {
        slotsGrid.innerHTML = '';
        const allSlots = generateAllTimeSlots();

        allSlots.forEach(slotElement => {
            if (savedSlots.includes(slotElement.dataset.time)) {
                slotElement.classList.add('selected');
            }
            slotElement.addEventListener('click', () => {
                slotElement.classList.toggle('selected');
            });
            slotsGrid.appendChild(slotElement);
        });

        timeSlotContainer.style.display = 'block';
    }

    function generateAllTimeSlots() {
        const slots = [];
        const startHour = 9;  // 9 AM
        const endHour = 22; // 10 PM

        for (let hour = startHour; hour <= endHour; hour++) {
            const time24hr = `${String(hour).padStart(2, '0')}:00:00`; 
            const slotElement = document.createElement('div');
            slotElement.classList.add('time-slot');
            slotElement.dataset.time = time24hr;
            slotElement.textContent = formatTime12hr(time24hr);
            slots.push(slotElement);
        }
        return slots;
    }
    
    function formatTime12hr(time24hr) {
        const [hourStr, minuteStr] = time24hr.split(':');
        const hour = parseInt(hourStr, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minuteStr} ${ampm}`;
    }

    saveScopeToggle.addEventListener('change', (event) => {
        toggleText.textContent = event.target.checked ? 'Save for All Upcoming Days' : 'Save for this Day';
    });

    saveFinalBtn.addEventListener('click', () => {
        if (!selectedDate) {
            alert('Please select a day first.');
            return;
        }

        const selectedSlots = Array.from(document.querySelectorAll('.time-slot.selected')).map(el => el.dataset.time);
        
        // This handles the "Save for all" toggle correctly
        const datesToSave = saveScopeToggle.checked
            ? Array.from(document.querySelectorAll('.calendar-day')).map(el => el.dataset.date)
            : [selectedDate];

        const savePromises = datesToSave.map(date => {
            const formData = new FormData();
            formData.append('date', date);
            // Note: If selectedSlots is empty, this will effectively clear the availability for the day(s)
            selectedSlots.forEach(slot => formData.append('time_slots[]', slot));

            return fetch('/dailyfix/api/manage_availability.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json());
        });

        // Disable button while saving
        saveFinalBtn.disabled = true;
        saveFinalBtn.textContent = 'Saving...';

        Promise.all(savePromises).then(results => {
            const errors = results.filter(r => r.status !== 'success');
            if (errors.length > 0) {
                // If there are any errors, show them in an alert
                alert('Some availabilities could not be saved. Please try again.');
                saveFinalBtn.disabled = false;
                saveFinalBtn.textContent = 'Save Availability';
            } else {
                // *** THIS IS THE FIX ***
                // On success, redirect to the profile page to show the custom message
                window.location.href = '/dailyfix/profile.php?success=availability_updated#availability';
            }
        }).catch(error => {
            console.error('Final save error:', error);
            alert('A network error occurred. Please try again.');
            saveFinalBtn.disabled = false;
            saveFinalBtn.textContent = 'Save Availability';
        });
    });

    // --- Tab Initialization Logic ---
    // This logic ensures the calendar loads if the tab is active on page load OR when you click it.
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.attributeName === 'class' && availabilityTabLink.classList.contains('active')) {
                initializeAvailabilityTab();
            }
        });
    });
    observer.observe(availabilityTabLink, { attributes: true });

    // Also check on initial page load
    if (availabilityTabLink.classList.contains('active')) {
        initializeAvailabilityTab();
    }
});