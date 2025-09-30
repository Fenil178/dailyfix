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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

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

                    <div class="location-details-admin">
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
        <?php if ($user && $user['latitude'] && $user['longitude']): ?>
        var map = L.map('map').setView([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>]).addTo(map);
        <?php endif; ?>
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>