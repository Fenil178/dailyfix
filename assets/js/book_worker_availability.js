document.addEventListener('DOMContentLoaded', function() {
    const calendarDaysContainer = document.getElementById('calendar-days');
    const timeSlotContainer = document.getElementById('time-slot-container');
    const selectedDateText = document.getElementById('selected-date-text');
    const slotsGrid = document.getElementById('slots-grid');
    const bookingForm = document.getElementById('booking-form');
    const hiddenDateInput = document.getElementById('booking_date');
    const hiddenTimeInput = document.getElementById('booking_time_combined');

    let selectedDate = null;
    const workerId = bookingForm.dataset.workerId;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (!calendarDaysContainer || !workerId) {
        console.error("Missing required elements or worker ID.");
        return;
    }

    function initializeBooking() {
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
        const tempToday = new Date();
        calendarDaysContainer.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const date = new Date(tempToday);
            date.setDate(tempToday.getDate() + i);
            const dateISO = date.toISOString().split('T')[0];

            const dayElement = document.createElement('div');
            dayElement.classList.add('calendar-day');
            dayElement.dataset.date = dateISO;
            dayElement.innerHTML = `
                <div class="date-day">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                <div class="date-number">${date.getDate()}</div>
                <div class="date-month">${date.toLocaleDateString('en-US', { month: 'short' })}</div>
            `;
            calendarDaysContainer.appendChild(dayElement);

            dayElement.addEventListener('click', () => {
                selectedDate = dateISO;
                selectedDateText.textContent = new Date(selectedDate).toLocaleDateString('en-US', { dateStyle: 'full' });
                document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                dayElement.classList.add('selected');
                
                fetchAvailability(selectedDate);
            });
        }
    }

    function fetchAvailability(date) {
        const url = `/dailyfix/api/get_availability.php?date=${date}&worker_id=${workerId}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    populateTimeSlots(data.slots, data.booked, date);
                } else {
                    alert('Error fetching availability: ' + data.message);
                    populateTimeSlots([], [], date);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('A network error occurred while fetching availability.');
                populateTimeSlots([], [], date);
            });
    }

    function populateTimeSlots(availableSlots, bookedSlots, date) {
        slotsGrid.innerHTML = '';
        timeSlotContainer.style.display = 'none';
        
        const selectedDateObj = new Date(date);
        selectedDateObj.setHours(0, 0, 0, 0);
        const isToday = selectedDateObj.getTime() === today.getTime();
        const currentHour = new Date().getHours();

        const availableSet = new Set(availableSlots);
        const bookedSet = new Set(bookedSlots);
        let anySlotsAvailable = false;

        availableSet.forEach(slotTime => {
            const slotHour = parseInt(slotTime.split(':')[0], 10);
            
            // Filter out past time slots for the current day
            if (isToday && slotHour <= currentHour) {
                return; // Skip this slot
            }

            // Check if the slot is available and not booked
            if (!bookedSet.has(slotTime)) {
                const slotElement = document.createElement('div');
                slotElement.classList.add('time-slot', 'available');
                slotElement.dataset.time = slotTime;
                slotElement.textContent = formatTime(slotTime);

                slotElement.addEventListener('click', () => {
                    document.querySelectorAll('.time-slot.selected').forEach(s => s.classList.remove('selected'));
                    slotElement.classList.add('selected');
                    // Update hidden form fields for submission
                    hiddenDateInput.value = selectedDate;
                    hiddenTimeInput.value = slotTime;
                });
                slotsGrid.appendChild(slotElement);
                anySlotsAvailable = true;
            }
        });

        if (!anySlotsAvailable) {
            slotsGrid.innerHTML = `<p class="no-slots-message">No available time slots for this day.</p>`;
        }
        
        timeSlotContainer.style.display = 'block';
    }

    function formatTime(time24hr) {
        let [hour, minute] = time24hr.split(':');
        hour = parseInt(hour, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return `${hour}:${minute} ${ampm}`;
    }

    // New function to generate all possible slots for filtering
    function generateAllTimeSlots() {
        const slots = [];
        const startHour = 9;
        const endHour = 22;

        for (let hour = startHour; hour <= endHour; hour++) {
            const time24hr = `${String(hour).padStart(2, '0')}:00:00`;
            slots.push(time24hr);
        }
        return slots;
    }

    initializeBooking();
});