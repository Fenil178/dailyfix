<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$admin_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$admin = null;
$error = '';
$success = '';

// --- REVISED LOGIC for updating user ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $admin_id = (int)$_POST['admin_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        $update_password = false;
        // Check if a password update is requested
        if (!empty($password)) {
            if ($password === $confirm_password) {
                $update_password = true;
            } else {
                $error = "Passwords do not match.";
            }
        }

        // Proceed with update only if there are no errors
        if (empty($error)) {
            try {
                // Build the query and parameters dynamically
                $params = [$full_name, $email];
                $sql = "UPDATE public.users SET full_name = ?, email = ?";

                if ($update_password) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $hashedPassword;
                }

                $sql .= " WHERE id = ? AND role = 'admin'";
                $params[] = $admin_id;

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $success = "Admin details updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating admin. The email might already be in use.";
                error_log("Admin Update Error: " . $e->getMessage());
            }
        }
    }
}

// Fetch current admin data to display
if ($admin_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT full_name, email FROM public.users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            $error = "Admin user not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching admin data.";
        error_log("Edit Admin Fetch Error: " . $e->getMessage());
    }
} else {
    $error = "No admin ID specified.";
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />

<div class="page-header section-fly-in">
    <h1><i class="fas fa-user-edit"></i> Edit Administrator</h1>
    <p>Modify the details for the selected admin account.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-content">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($admin): ?>
            <div class="edit-entity-layout">
                <div class="profile-summary">
                    <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                    <h3><?php echo htmlspecialchars($admin['full_name']); ?></h3>
                    <div class="user-meta">
                        <p><?php echo htmlspecialchars($admin['email']); ?></p>
                        <span class="role-badge" style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);">Administrator</span>
                    </div>
                </div>

                <div class="edit-form">
                    <form method="POST" action="edit_admin.php?id=<?php echo $admin_id; ?>">
                        <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">
                        
                        <div class="form-section">
                            <h3>Account Information</h3>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Update Password</h3>
                            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: -0.5rem; margin-bottom: 1.5rem;">Leave both fields blank to keep the current password.</p>
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_admin" class="btn btn-primary">Save Changes</button>
                            <a href="manage_admins.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <p>Could not load admin data to edit.</p>
            <a href="manage_admins.php" class="btn btn-secondary">&larr; Back to Admin List</a>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>