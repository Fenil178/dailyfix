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

    if (!availabilityTabLink || !calendarDaysContainer) {
        return;
    }

    // --- Tab Logic ---
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabLinks.forEach(link => {
        link.addEventListener('click', () => {
            const tabId = link.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            link.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            if (tabId === 'availability' && !initialLoadComplete) {
                initializeAvailabilityTab();
                initialLoadComplete = true;
            }
        });
    });

    // --- Calendar & Time Slot Logic ---
    function initializeAvailabilityTab() {
        generateCalendar();
        
        const firstDay = document.querySelector('.calendar-day');
        if (firstDay) {
            selectedDate = firstDay.dataset.date;
            firstDay.classList.add('selected');
            selectedDateText.textContent = new Date(selectedDate).toLocaleDateString('en-US', { dateStyle: 'full' });
            fetchAvailability(selectedDate);
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
                selectedDateText.textContent = new Date(selectedDate).toLocaleDateString('en-US', { dateStyle: 'full' });
                document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                dayElement.classList.add('selected');
                
                fetchAvailability(selectedDate);
            });
        }
    }

    // Fetches saved availability from the new API endpoint
    function fetchAvailability(date) {
        fetch(`/dailyfix/api/get_availability.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    populateTimeSlots(data.slots);
                } else {
                    alert('Error fetching availability: ' + data.message);
                    populateTimeSlots([]);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('A network error occurred while fetching availability. Please try again.');
                populateTimeSlots([]);
            });
    }

    function populateTimeSlots(slotsToSelect) {
        const allSlots = generateAllTimeSlots();
        slotsGrid.innerHTML = '';

        allSlots.forEach(slot => {
            if (slotsToSelect.includes(slot.dataset.time)) {
                slot.classList.add('selected');
            }

            slot.addEventListener('click', () => {
                slot.classList.toggle('selected');
            });
            slotsGrid.appendChild(slot);
        });

        timeSlotContainer.style.display = 'block';
    }

    function generateAllTimeSlots() {
        const slots = [];
        const startHour = 9;
        const endHour = 22;

        for (let hour = startHour; hour <= endHour; hour++) {
            // FIX: Append seconds to match the database format
            const time24hr = `${String(hour).padStart(2, '0')}:00:00`; 
            const slotElement = document.createElement('div');
            slotElement.classList.add('time-slot', 'available');
            slotElement.dataset.time = time24hr;
            slotElement.textContent = formatTime(time24hr);
            slots.push(slotElement);
        }
        return slots;
    }

    function formatTime(time24hr) {
        let [hour, minute] = time24hr.split(':');
        hour = parseInt(hour, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return `${hour}:${minute} ${ampm}`;
    }

    // --- Save Logic ---
    saveScopeToggle.addEventListener('change', (event) => {
        if (event.target.checked) {
            toggleText.textContent = 'Save for All Upcoming Days';
        } else {
            toggleText.textContent = 'Save for this Day';
        }
    });

    saveFinalBtn.addEventListener('click', () => {
        if (!selectedDate) {
            alert('Please select a day first.');
            return;
        }

        // Use the same format for saving
        const selectedSlots = Array.from(document.querySelectorAll('.time-slot.selected')).map(el => el.dataset.time);
        
        if (selectedSlots.length === 0) {
            alert('Please select at least one time slot.');
            return;
        }
        
        const saveForAllDays = saveScopeToggle.checked;
        const datesToSave = saveForAllDays ?
            Array.from(document.querySelectorAll('.calendar-day')).map(el => el.dataset.date) :
            [selectedDate];
        
        const savePromises = datesToSave.map(date => {
            const formData = new FormData();
            formData.append('date', date);
            selectedSlots.forEach(slot => formData.append('time_slots[]', slot));

            return fetch('/dailyfix/api/manage_availability.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      return { status: 'success', date: date };
                  } else {
                      return { status: 'error', date: date, message: data.message };
                  }
              });
        });

        Promise.all(savePromises).then(results => {
            const errors = results.filter(r => r.status === 'error');
            if (errors.length > 0) {
                const errorMessages = errors.map(e => `Error saving for ${new Date(e.date).toLocaleDateString()}: ${e.message}`).join('\n');
                alert(errorMessages);
            } else {
                alert('Availability saved successfully!');
            }

            fetchAvailability(selectedDate);
            
        }).catch(error => {
            console.error('Final save error:', error);
            alert('A network error occurred during the save process. Please try again.');
        });
    });

    const activeTab = document.querySelector('.tab-link.active');
    if (activeTab && activeTab.dataset.tab === 'availability') {
        initializeAvailabilityTab();
        initialLoadComplete = true;
    }
});