<?php
// --- PHP code remains the same as the previous version ---
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$error = '';
$success = '';
$worker_offers = []; // Initialize worker offers array
$customer_bookings = []; // Initialize customer bookings array

//Handle form submission for updating user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if (empty($full_name) || empty($email)) {
        $error = "Full name and email are required.";
    } else {
        try {
            // Updated SQL Query: Removed role update
            $stmt = $conn->prepare("UPDATE public.users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
            $success = "User details updated successfully!";
             // Re-fetch user data after update to reflect changes immediately
             $stmt_refetch = $conn->prepare("SELECT full_name, email, role, profile_image, account_status, created_at, address_line1, address_line2, city, state, pincode, latitude, longitude FROM public.users WHERE id = ? AND role != 'admin'");
             $stmt_refetch->execute([$user_id]);
             $user = $stmt_refetch->fetch(PDO::FETCH_ASSOC); // Update the $user variable

              // Re-fetch related data based on role
              if ($user) {
                  if ($user['role'] === 'worker') {
                    $stmt_offers = $conn->prepare("SELECT * FROM public.worker_offers WHERE worker_id = ? ORDER BY created_at DESC");
                    $stmt_offers->execute([$user_id]);
                    $worker_offers = $stmt_offers->fetchAll(PDO::FETCH_ASSOC);
                 } elseif ($user['role'] === 'customer') {
                    // *** MODIFIED: Re-fetch customer bookings with service details after update ***
                    $stmt_bookings = $conn->prepare("
                        SELECT b.id, b.booking_time, b.status, b.service_details, w.full_name as worker_name
                        FROM public.bookings b
                        JOIN public.users w ON b.worker_id = w.id
                        WHERE b.customer_id = ? ORDER BY b.booking_time DESC LIMIT 5
                    ");
                    $stmt_bookings->execute([$user_id]);
                    $customer_bookings = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);
                    // *** END MODIFIED ***
                 }
              }

        } catch (PDOException $e) {
            $error = "Error updating user. The email might already be in use.";
            error_log("User Update Error: " . $e->getMessage());
        }
    }
} else { // Fetch data only on initial GET request or if POST failed validation
    if ($user_id > 0) {
        try {
            $stmt = $conn->prepare("SELECT full_name, email, role, profile_image, account_status, created_at, address_line1, address_line2, city, state, pincode, latitude, longitude FROM public.users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $error = "User not found or you are trying to edit an admin.";
            } else {
                // Fetch role-specific data
                if ($user['role'] === 'worker') {
                    $stmt_offers = $conn->prepare("SELECT * FROM public.worker_offers WHERE worker_id = ? ORDER BY created_at DESC");
                    $stmt_offers->execute([$user_id]);
                    $worker_offers = $stmt_offers->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($user['role'] === 'customer') {
                    // *** MODIFIED: Fetch customer bookings with service details on initial load ***
                    $stmt_bookings = $conn->prepare("
                        SELECT b.id, b.booking_time, b.status, b.service_details, w.full_name as worker_name
                        FROM public.bookings b
                        JOIN public.users w ON b.worker_id = w.id
                        WHERE b.customer_id = ? ORDER BY b.booking_time DESC LIMIT 5
                    ");
                    $stmt_bookings->execute([$user_id]);
                    $customer_bookings = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);
                    // *** END MODIFIED ***
                }
            }
        } catch (PDOException $e) {
            $error = "Error fetching user data.";
            error_log("Edit User Fetch Error: " . $e->getMessage());
        }
    } else {
        $error = "No user ID specified.";
    }
}

// Helper function to parse service details string
function parseServiceDetails($detailsString) {
    $details = ['service' => 'N/A', 'item' => 'N/A'];
    $lines = explode("\n", $detailsString ?? '');
    foreach ($lines as $line) {
        if (strpos($line, 'Service:') !== false) {
            $details['service'] = trim(str_replace('Service:', '', $line));
        } elseif (strpos($line, 'Item:') !== false) {
            $details['item'] = trim(str_replace('Item:', '', $line));
        }
    }
    return $details;
}

?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<link rel="stylesheet" href="/dailyfix/assets/css/profile.css" />
<link rel="stylesheet" href="/dailyfix/admin/assets/css/edit_user.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">

<style>
    /* Common skeleton styles (loader, shimmer, dark-mode) */
    .skeleton-loader {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background-color: var(--background-color-body, #f9f9f9);
        z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
    }
    .skeleton-loader.hidden { opacity: 0; pointer-events: none; }
    .skeleton-container {
        max-width: 1100px; width: 100%;
        padding: 0 1rem;
        margin: 1rem auto;
        margin-top: 80px; /* Adjust to match your header's height */
    }
    @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
    .skeleton {
        animation: shimmer 1.5s infinite linear;
        background: linear-gradient(to right, 
        var(--hover-color, #f0f0f0) 8%, 
        var(--border-color, #e2e8f0) 18%, 
        var(--hover-color, #f0f0f0) 33%);
        background-size: 800px 104px; border-radius: 6px;
    }
    body.dark-mode .skeleton-loader { background-color: var(--background-color-body, #121212); }
    body.dark-mode .skeleton {
        background: linear-gradient(to right, 
        var(--hover-color, #2c2c2c) 8%, 
        var(--border-color, #334155) 18%, 
        var(--hover-color, #2c2c2c) 33%);
        background-size: 800px 104px;
    }

    /* Page-specific skeleton layout for edit_user.php */
    .skeleton-title { height: 38px; width: 40%; margin: 2rem 0; }
    .skeleton-edit-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .skeleton-panel {
        padding: 2rem;
        background-color: var(--background-color-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
    }
    body.dark-mode .skeleton-panel {
        background-color: var(--background-color-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-panel-title { height: 24px; width: 50%; margin-bottom: 2rem; }
    .skeleton-label { height: 14px; width: 100px; margin-bottom: 0.5rem; }
    .skeleton-input { height: 40px; width: 100%; margin-bottom: 1.5rem; }
    .skeleton-button { height: 45px; width: 120px; margin-top: 1rem; }
    .skeleton-list-item { height: 40px; width: 100%; margin-bottom: 1rem; }

    @media (max-width: 900px) {
        .skeleton-edit-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton skeleton-title"></div>
        
        <div class="skeleton-edit-grid">
        <div class="skeleton-panel">
            <div class="skeleton skeleton-panel-title"></div>
            <div class="skeleton skeleton-label"></div>
            <div class="skeleton skeleton-input"></div>
            <div class="skeleton skeleton-label"></div>
            <div class="skeleton skeleton-input"></div>
            <div class="skeleton skeleton-label"></div>
            <div class="skeleton skeleton-input"></div>
            <div class="skeleton skeleton-button"></div>
        </div>
        
        <div class="skeleton-panel" style="height: 400px;">
            <div class="skeleton skeleton-panel-title"></div>
            <div class="skeleton skeleton-list-item"></div>
            <div class="skeleton skeleton-list-item"></div>
            <div class="skeleton skeleton-list-item"></div>
        </div>
        </div>
    </div>
</div>

<a href="manage_users.php" class="back-link section-fly-in" style="margin-bottom: 1.5rem; display: block; max-width: 1400px; margin-left: auto; margin-right: auto; padding-left: 2rem; padding-right: 2rem;">
    <i class="fas fa-arrow-left"></i> Back to User List
</a>

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
                            <select id="role" name="role" required disabled>
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

                     <div class="location-details-admin" style="margin-top: 2rem;">
                        <h4><i class="fas fa-tags"></i> Worker Offers (Read-Only)</h4>
                        <div class="read-only-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>This is a read-only view. Workers manage their own offers from their profile.</span>
                        </div>
                        <div class="worker-offers-list">
                            <?php if (empty($worker_offers)): ?>
                                <p>This worker has not created any offers yet.</p>
                            <?php else: ?>
                                <?php foreach ($worker_offers as $offer): ?>
                                    <div class="offer-item-admin">
                                        <div class="offer-details-admin">
                                            <strong><?php echo htmlspecialchars($offer['coupon_code']); ?></strong>
                                            <p>
                                                <?php
                                                    if ($offer['discount_type'] === 'percentage') {
                                                        echo htmlspecialchars(number_format($offer['discount_value'], 2)) . '% off';
                                                    } else {
                                                        echo '₹' . htmlspecialchars(number_format($offer['discount_value'], 2)) . ' off';
                                                    }
                                                    if ((float)$offer['min_booking_amount'] > 0) {
                                                        echo ' (min ₹' . number_format($offer['min_booking_amount'], 2) . ')';
                                                    }
                                                ?>
                                            </p>
                                            <p style="font-size: 0.8em;">
                                                <?php if ($offer['valid_until']): ?>
                                                    Expires: <?php echo date("M d, Y", strtotime($offer['valid_until'])); ?> |
                                                <?php endif; ?>
                                                Usage: <?php echo $offer['uses_count']; ?><?php if ($offer['max_uses']) echo ' / ' . $offer['max_uses']; ?>
                                            </p>
                                        </div>
                                        <span class="offer-status-admin <?php echo $offer['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $offer['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; // End worker-specific sections ?>
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
                            <?php if ($user['latitude'] && $user['longitude']): ?>
                                <div id="map"></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No location data provided by the user.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($user['role'] === 'customer'): ?>
                   
                    <div class="location-details-admin" style="margin-top: 2rem;">
                        <h4><i class="fas fa-history"></i> Recent Booking History (Read-Only)</h4>
                        <div class="customer-bookings-list">
                            <?php if (empty($customer_bookings)): ?>
                                <p>This customer has not made any bookings yet.</p>
                            <?php else: ?>
                                <table class="data-table booking-history-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Sub-Service</th>
                                            <th>Item</th>
                                            <th>Worker</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customer_bookings as $booking):
                                            $serviceDetails = parseServiceDetails($booking['service_details']);
                                        ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                                <td><?php echo htmlspecialchars($serviceDetails['service']); ?></td>
                                                <td><?php echo htmlspecialchars($serviceDetails['item']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['worker_name']); ?></td>
                                                <td>
                                                    <?php
                                                        $bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
                                                        $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                                        echo $bookingTime->format("M d, Y, g:i A");
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge-table status-<?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php
                                try {
                                    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM public.bookings WHERE customer_id = ?");
                                    $stmt_count->execute([$user_id]);
                                    $total_bookings = $stmt_count->fetchColumn();
                                    if ($total_bookings > 5) {
                                        echo '<p style="margin-top: 1rem; font-size: 0.9em; color: var(--text-secondary);">View all bookings on the main <a href="view_bookings.php" style="color: var(--primary-color);">Bookings page</a>.</p>';
                                    }
                                } catch (PDOException $e) { /* Ignore count error */ }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; // End customer-specific sections ?>
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
        const workerId = <?php echo json_encode($user_id); ?>;

        // --- Initialize Location Map (unchanged) ---
        <?php if ($user && $user['latitude'] && $user['longitude']): ?>
        try {
            var locationMap = L.map('map').setView([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(locationMap);
            L.marker([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>]).addTo(locationMap);
             setTimeout(() => { if(locationMap) locationMap.invalidateSize(); }, 100);
        } catch(e) { /* Error handling... */ }
        <?php endif; ?>

        // --- Initialize Worker Availability Calendar (logic modified) ---
        if (userRole === 'worker') {
            const calendarDaysContainer = document.getElementById('calendar-days');
            const timeSlotContainer = document.getElementById('time-slot-container');
            const selectedDateText = document.getElementById('selected-date-text');
            const slotsGrid = document.getElementById('slots-grid');
            let selectedDate = null;

            // --- generateCalendar function (unchanged) ---
            function generateCalendar() {
                if (!calendarDaysContainer) return;
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
                        if(selectedDateText) selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
                        document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
                        dayElement.classList.add('selected');
                        fetchAndPopulateAvailability(selectedDate); // Fetch new data on click
                    });
                }
            }

            // --- fetchAndPopulateAvailability function (modified to fetch both slot types) ---
            function fetchAndPopulateAvailability(date) {
                if (!workerId || !date) return;
                // Fetch both available and booked slots
                 fetch(`/dailyfix/api/get_availability.php?date=${date}&worker_id=${workerId}`) // Make sure worker_id is passed
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Pass both arrays to populateTimeSlots
                            populateTimeSlots(data.slots || [], data.booked || []);
                        } else {
                            console.error('Error fetching availability:', data.message);
                            populateTimeSlots([], []); // Clear grid on error
                        }
                    })
                    .catch(error => {
                        console.error('Fetch network error:', error);
                         populateTimeSlots([], []); // Clear grid on network error
                    });
            }

            // --- populateTimeSlots function (MODIFIED to handle booked slots) ---
             function populateTimeSlots(availableSlots, bookedSlots) {
                if (!slotsGrid) return;
                const availableSet = new Set(availableSlots);
                const bookedSet = new Set(bookedSlots); // Create a Set for booked slots
                slotsGrid.innerHTML = '';
                const allSlots = generateAllTimeSlots();

                allSlots.forEach(slotElement => {
                    const slotTime = slotElement.dataset.time;

                    // Reset classes first
                    slotElement.className = 'time-slot read-only'; // Start with base classes

                    if (bookedSet.has(slotTime)) { // **PRIORITY: Check if booked first**
                        slotElement.classList.add('unavailable'); // Mark as unavailable (CSS handles visual style)
                        // Add floating label structure for booked slots
                        slotElement.classList.add('floating-label');
                        const timeText = formatTime12hr(slotTime);
                        slotElement.innerHTML = `
                            <span class="slot-label">Booked</span>
                            <span class="slot-content">${timeText}</span>
                        `;
                    } else if (availableSet.has(slotTime)) { // If not booked, check if available
                        slotElement.classList.add('selected');
                        slotElement.textContent = formatTime12hr(slotTime); // Set text content for available
                    } else {
                        // If neither booked nor available, just keep the base read-only style
                         slotElement.textContent = formatTime12hr(slotTime); // Set text content for non-available
                    }
                    slotsGrid.appendChild(slotElement);
                });

                if(timeSlotContainer) timeSlotContainer.style.display = 'block';
            }

            // --- generateAllTimeSlots function (unchanged) ---
            function generateAllTimeSlots() {
                 const slots = [];
                const startHour = 9;
                const endHour = 22;

                for (let hour = startHour; hour <= endHour; hour++) {
                    const time24hr = `${String(hour).padStart(2, '0')}:00:00`;
                    const slotElement = document.createElement('div');
                    // slotElement.classList.add('time-slot'); // Removed base class here, added in populateTimeSlots
                    slotElement.dataset.time = time24hr;
                    // slotElement.textContent = formatTime12hr(time24hr); // Text set in populateTimeSlots
                    slots.push(slotElement);
                }
                return slots;
            }

            // --- formatTime12hr function (unchanged) ---
            function formatTime12hr(time24hr) {
                 if (!time24hr) return '';
                try {
                    const [hourStr, minuteStr] = time24hr.split(':');
                    const hour = parseInt(hourStr, 10);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const hour12 = hour % 12 || 12;
                    return `${hour12}:${minuteStr} ${ampm}`;
                } catch (e) { /* Error handling... */ return time24hr;}
            }

            // --- Initialize calendar (unchanged) ---
            generateCalendar();
            const firstDay = document.querySelector('.calendar-day');
            if (firstDay) {
                selectedDate = firstDay.dataset.date;
                firstDay.classList.add('selected');
                if(selectedDateText) selectedDateText.textContent = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { dateStyle: 'full' });
                fetchAndPopulateAvailability(selectedDate); // Initial fetch
            }
        }
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>