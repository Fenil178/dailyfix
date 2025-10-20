<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$error = '';
$success = '';

// Handle form submission for updating user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (empty($full_name) || empty($email) || empty($role)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE public.users SET full_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role, $user_id]);
            $success = "User details updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating user. The email might already be in use.";
            error_log("User Update Error: " . $e->getMessage());
        }
    }
}

// Fetch current user data to display
if ($user_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT full_name, email, role, profile_image, account_status, created_at, address_line1, address_line2, city, state, pincode, latitude, longitude FROM public.users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = "User not found or you are trying to edit an admin.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching user data.";
        error_log("Edit User Fetch Error: " . $e->getMessage());
    }
} else {
    $error = "No user ID specified.";
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<link rel="stylesheet" href="/dailyfix/assets/css/profile.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">

<style>
    /* Override font from profile.css to match the admin theme */
    .form-group input,
    .form-group select {
        font-family: 'Inter', sans-serif;
    }

    .read-only-overlay {
        position: relative;
    }
    .read-only-overlay::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: transparent;
        cursor: not-allowed;
        z-index: 1;
    }
    .read-only-banner {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border: 1px solid #ffc107;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    body.dark-mode .read-only-banner {
        background-color: #664d03;
        color: #fff3cd;
        border-color: #ffc107;
    }
    .calendar-day.read-only,
    .time-slot.read-only {
        opacity: 0.8;
    }
    .time-slot.read-only {
        cursor: not-allowed !important;
    }
    .time-slot.selected.read-only {
        background-color: var(--primary-color);
        cursor: not-allowed !important;
        color: #fff;
        border-color: var(--primary-color);
    }
</style>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-user-edit"></i> Edit User</h1>
    <p>Modify the details for <?php if ($user) echo '<strong>' . htmlspecialchars($user['full_name']) . '</strong>'; ?>.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-content">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($user): ?>
            <div class="edit-user-layout">
                <div class="user-profile-summary">
                    <?php 
                        $avatar_path = $user['profile_image'] ?: '/dailyfix/assets/images/default-avatar.png';
                        if ($user['profile_image'] && strpos($user['profile_image'], '/') !== 0) {
                            $avatar_path = '/dailyfix/' . $user['profile_image'];
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Profile Avatar" class="profile-avatar">
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <div class="user-meta">
                        <span class="status-badge-table status-<?php echo strtolower($user['account_status']); ?>">
                            <?php echo htmlspecialchars($user['account_status']); ?>
                        </span>
                        <p style="margin-top: 1rem;">Member since:<br><?php echo date("M d, Y", strtotime($user['created_at'])); ?></p>
                    </div>
                </div>

                <div class="user-edit-form">
                    <form method="POST" action="edit_user.php?id=<?php echo $user_id; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="customer" <?php echo ($user['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="worker" <?php echo ($user['role'] === 'worker') ? 'selected' : ''; ?>>Worker</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                    <?php if ($user['role'] === 'worker'): ?>
                    <div class="location-details-admin" style="margin-top: 2rem;">
                        <h4><i class="fas fa-clock"></i> Worker Availability (Read-Only)</h4>
                        <div class="read-only-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>This is a read-only view. Workers manage their own availability from their profile.</span>
                        </div>
                        <div id="availability-section"> 
                            <div id="calendar-container">
                                <div id="calendar-days" class="calendar-grid"></div>
                            </div>

                            <div id="time-slot-container" style="display: none; margin-top: 2rem;">
                                <h4>Time Slots for <span id="selected-date-text"></span></h4>
                                <div id="slots-grid" class="slots-grid"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="location-details-admin" style="margin-top: 2rem;">
                        <h4><i class="fas fa-map-marked-alt"></i> User Location</h4>
                        <?php if ($user['address_line1']): ?>
                            <div class="address-block">
                                <p><?php echo htmlspecialchars($user['address_line1']); ?></p>
                                <?php if ($user['address_line2']): ?>
                                    <p><?php echo htmlspecialchars($user['address_line2']); ?></p>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($user['city'] . ', ' . $user['state'] . ' - ' . $user['pincode']); ?></p>
                            </div>
                            <div id="map"></div>
                        <?php else: ?>
                            <p>No location data provided by the user.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p>Could not load user data to edit.</p>
            <a href="manage_users.php" class="btn btn-secondary">&larr; Back to User List</a>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userRole = '<?php echo $user['role'] ?? ''; ?>';
        const workerId = <?php echo $user_id; ?>;

        // Initialize location map
        <?php if ($user && $user['latitude'] && $user['longitude']): ?>
        var locationMap = L.map('map').setView([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(locationMap);
        L.marker([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>]).addTo(locationMap);
        <?php endif; ?>

        // Initialize availability calendar for workers (read-only)
        if (userRole === 'worker') {
            const calendarDaysContainer = document.getElementById('calendar-days');
            const timeSlotContainer = document.getElementById('time-slot-container');
            const selectedDateText = document.getElementById('selected-date-text');
            const slotsGrid = document.getElementById('slots-grid');
            let selectedDate = null;

            function generateCalendar() {
                const today = new Date();
                calendarDaysContainer.innerHTML = '';
                for (let i = 0; i < 7; i++) {
                    const date = new Date(today);
                    date.setDate(today.getDate() + i);

                    const dayElement = document.createElement('div');
                    dayElement.classList.add('calendar-day', 'read-only');
                    dayElement.dataset.date = date.toISOString().split('T')[0];
                    dayElement.innerHTML = `
                        <div class="date-day">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                        <div class="date-number">${date.getDate()}</div>
                        <div class="date-month">${date.toLocaleDateString('en-US', { month: 'short' })}</div>
                    `;
                    calendarDaysContainer.appendChild(dayElement);

                    dayElement.addEventListener('click', () => {
                        selectedDate = dayElement.dataset.date;
                        selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
                        document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                        dayElement.classList.add('selected');
                        fetchAndPopulateAvailability(selectedDate);
                    });
                }
            }

            function fetchAndPopulateAvailability(date) {
                fetchAvailability(date).then(slots => {
                    if (slots.length > 0) {
                        populateTimeSlots(slots);
                    } else {
                        const selectedDay = new Date(date + 'T00:00:00');
                        const previousDay = new Date(selectedDay);
                        previousDay.setDate(selectedDay.getDate() - 1);
                        const previousDateString = previousDay.toISOString().split('T')[0];

                        const firstCalendarDay = document.querySelector('.calendar-day').dataset.date;
                        if (previousDateString >= firstCalendarDay) {
                            fetchAvailability(previousDateString).then(prevSlots => {
                                populateTimeSlots(prevSlots);
                            });
                        } else {
                            populateTimeSlots([]);
                        }
                    }
                });
            }

            function fetchAvailability(date) {
                return fetch(`/dailyfix/api/get_availability.php?date=${date}&worker_id=${workerId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const bookedSet = new Set(data.booked || []);
                            return (data.slots || []).filter(slot => !bookedSet.has(slot));
                        } else {
                            console.error('Error fetching availability:', data.message);
                            return [];
                        }
                    })
                    .catch(error => {
                        console.error('Fetch network error:', error);
                        return [];
                    });
            }

            function populateTimeSlots(savedSlots) {
                slotsGrid.innerHTML = '';
                const allSlots = generateAllTimeSlots();

                allSlots.forEach(slotElement => {
                    if (savedSlots.includes(slotElement.dataset.time)) {
                        slotElement.classList.add('selected', 'read-only');
                    } else {
                        slotElement.classList.add('read-only');
                    }
                    slotsGrid.appendChild(slotElement);
                });

                timeSlotContainer.style.display = 'block';
            }

            function generateAllTimeSlots() {
                const slots = [];
                const startHour = 9;
                const endHour = 22;

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

            // Initialize calendar and select first day
            generateCalendar();
            const firstDay = document.querySelector('.calendar-day');
            if (firstDay) {
                selectedDate = firstDay.dataset.date;
                firstDay.classList.add('selected');
                selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
                fetchAndPopulateAvailability(selectedDate);
            }
        }
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>