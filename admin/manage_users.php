<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// Handle status messages from the action script
$status_msg = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $status_msg = '<div class="alert alert-success">User successfully deleted.</div>';
            break;
        case 'updated':
            $status_msg = '<div class="alert alert-success">User status successfully updated.</div>';
            break;
        case 'error':
            $status_msg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
        case 'unauthorized':
            $status_msg = '<div class="alert alert-danger">You cannot perform this action on an admin account.</div>';
            break;
    }
}


$users = [];
$users_locations = [];
try {
    $stmt = $conn->prepare("SELECT id, full_name, email, role, account_status, created_at, city, state, latitude, longitude FROM public.users WHERE role != 'admin' ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user locations for the map
    $users_locations_stmt = $conn->query("
        SELECT full_name, latitude, longitude, role
        FROM public.users
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    ");
    $users_locations = $users_locations_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Manage Users Error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
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

    /* Page-specific skeleton layout for manage_users.php */
    .skeleton-title { height: 38px; width: 40%; margin: 2rem 0; }
    .skeleton-panel {
        padding: 2rem;
        background-color: var(--background-color-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    body.dark-mode .skeleton-panel {
        background-color: var(--background-color-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-panel-title { height: 24px; width: 30%; margin-bottom: 1.5rem; }
    .skeleton-map { height: 300px; width: 100%; }
    .skeleton-table { height: 400px; width: 100%; }
    
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton skeleton-title"></div>
        
        <div class="skeleton-panel">
        <div class="skeleton skeleton-panel-title"></div>
        <div class="skeleton skeleton-map"></div>
        </div>
        
        <div class="skeleton-panel">
        <div class="skeleton skeleton-panel-title" style="width: 20%;"></div>
        <div class="skeleton skeleton-table"></div>
        </div>
    </div>
</div>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-users"></i> User Management</h1>
    <p>View, search, and manage all registered customers and workers.</p>
</div>

<?php echo $status_msg; ?>

<div class="dashboard-card section-fly-in">
    <div class="card-header">
        <h2><i class="fas fa-map-marked-alt"></i> User Locations</h2>
    </div>
    <div class="card-content">
        <div id="user-map" style="height: 400px;"></div>
    </div>
</div>

<div class="dashboard-card section-fly-in" style="margin-top: 2rem;">
    <div class="card-header">
        <h2>All Users</h2>
    </div>
    <div class="card-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" style="text-align: center;">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge role-<?php echo strtolower($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="status-badge-table status-<?php echo strtolower($user['account_status']); ?>"><?php echo htmlspecialchars($user['account_status']); ?></span></td>
                            <td><?php echo htmlspecialchars($user['city'] . ', ' . $user['state']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                
                                <?php if ($user['account_status'] === 'active'): ?>
                                    <a href="actions/user_actions.php?action=toggle_status&user_id=<?php echo $user['id']; ?>" 
                                    class="action-trigger action-btn"
                                        data-modal-title="Confirm Suspension"
                                        data-modal-description="Are you sure you want to suspend this user? A worker will not be able to use it to register."
                                        data-modal-icon="fas fa-ban"
                                        data-modal-theme="modal-warning"
                                        data-modal-confirm-text="Yes, Suspend"
                                        title="Suspend"><i class="fas fa-ban"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="actions/user_actions.php?action=toggle_status&user_id=<?php echo $user['id']; ?>"
                                        class="action-trigger action-btn"
                                        data-modal-title="Confirm Activation"
                                        data-modal-description="Are you sure you want to active this user? A worker will not be able to use it to register."
                                        data-modal-icon="fas fa-ban"
                                        data-modal-theme="modal-warning"
                                        data-modal-confirm-text="Yes, Active"
                                        title="Activate"><i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="actions/user_actions.php?action=delete&user_id=<?php echo $user['id']; ?>"
                                    class="action-trigger action-btn"
                                    data-modal-title="Confirm Deletion"
                                    data-modal-description="Are you sure you want to delete this user? This action is permanent."
                                    data-modal-icon="fas fa-trash"
                                    data-modal-theme="modal-danger"
                                    data-modal-confirm-text="Yes, Delete"
                                    title="Delete"><i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="confirmation-modal" class="modal confirmation-modal" role="dialog" aria-hidden="true">
    <div class="modal-content">
        <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
        <div class="modal-icon"><i class=""></i></div>
        <h2 id="modal-title">Confirmation</h2>
        <p id="modal-description">Are you sure?</p>
        <div class="modal-buttons">
            <button id="confirm-action-btn" class="btn btn-confirm" type="button">Confirm</button>
            <button id="cancel-action-btn" class="btn btn-secondary" type="button">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. YOUR MAP CODE (Unchanged) ---
    const userMap = document.getElementById('user-map');
    if (userMap) {
        const map = L.map('user-map').setView([21.1702, 72.8311], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const users = <?php echo json_encode($users_locations); ?>;
        users.forEach(user => {
            const marker = L.marker([user.latitude, user.longitude]).addTo(map);
            marker.bindPopup(`<b>${user.full_name}</b><br>${user.role}`);
        });
    }

    // --- 2. THE MODAL SCRIPT (This is the fix) ---
    const modal = document.getElementById('confirmation-modal');
    if (modal) {
        const modalTitle = document.getElementById('modal-title');
        const modalDescription = document.getElementById('modal-description');
        const modalIcon = modal.querySelector('.modal-icon i');
        const confirmBtn = document.getElementById('confirm-action-btn');
        const cancelBtn = document.getElementById('cancel-action-btn');
        const closeBtn = modal.querySelector('.close-button');
        let confirmUrl = '#';

        document.querySelectorAll('.action-trigger').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault(); // Stop the link from navigating immediately
                
                // Get data from the link you clicked
                confirmUrl = this.href;
                const title = this.dataset.modalTitle;
                const description = this.dataset.modalDescription;
                const icon = this.dataset.modalIcon;
                const theme = this.dataset.modalTheme;
                const confirmText = this.dataset.modalConfirmText; // Get the text

                // Populate the modal
                modalTitle.textContent = title;
                modalDescription.textContent = description;
                modalIcon.className = icon; 
                
                // THIS IS THE LINE THAT FIXES YOUR BUTTON
                confirmBtn.textContent = confirmText; 
                
                // Reset/apply theme classes
                modal.className = 'modal confirmation-modal'; // Reset
                if (theme) {
                    modal.classList.add(theme);
                }
                
                // Show the modal (using .show to match your admin_style.css)
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        // Function to close the modal
        function closeModal() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            confirmUrl = '#'; // Clear the URL
        }

        // Add event listeners to close buttons
        confirmBtn.addEventListener('click', () => {
            if (confirmUrl !== '#') {
                window.location.href = confirmUrl; // Go to the delete/suspend URL
            }
        });
        
        cancelBtn.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeModal();
            }
        });
    }

    // --- 3. SKELETON LOADER SCRIPT ---
    const loader = document.getElementById('page-loader');
    if (loader) {
        // Hide the loader once the window is fully loaded
        window.addEventListener('load', () => {
            loader.classList.add('hidden');
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>