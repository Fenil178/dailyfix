<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$current_admin_id = $_SESSION['admin_id'] ?? 0;
$error = null;
$success = null;

// Handle status messages from the action script
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $success = "Admin user successfully deleted.";
            break;
        case 'self_delete_error':
            $error = "You cannot delete your own account.";
            break;
        case 'error':
            $error = "An error occurred. Please try again.";
            break;
    }
}

// Handle form submission to add a new admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id FROM public.users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO public.users (full_name, email, password, role, account_status) 
                     VALUES (?, ?, ?, 'admin', 'active')"
                );
                $stmt->execute([$full_name, $email, $hashedPassword]);
                $success = "New admin user created successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error. Could not create admin.";
            error_log("Admin Creation Error: " . $e->getMessage());
        }
    }
}

// Fetch all admin users
$admins = [];
try {
    $stmt = $conn->prepare("SELECT id, full_name, email FROM public.users WHERE role = 'admin' ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not fetch admin data.";
    error_log("Manage Admins Error: " . $e->getMessage());
}
?>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-user-shield"></i> Administrator Management</h1>
    <p>Create new admin accounts and view existing ones.</p>
</div>

<div class="management-grid section-fly-in">
    <div class="form-card">
        <h2><i class="fas fa-user-plus"></i> Add New Admin</h2>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        
        <form method="POST" action="manage_admins.php" novalidate>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="add_admin" class="btn btn-primary">Create Admin</button>
        </form>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-users-cog"></i> Existing Admins</h2>
        </div>
        <div class="card-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr><td colspan="3" style="text-align: center;">No admins found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?> <?php if($admin['id'] == $current_admin_id) echo '(You)'; ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td class="action-buttons">
                                    <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                    
                                    <?php if ($admin['id'] != $current_admin_id): ?>
                                    <a href="actions/admin_actions.php?action=delete&admin_id=<?php echo $admin['id']; ?>"
                                       class="action-trigger"
                                       data-modal-title="Confirm Deletion"
                                       data-modal-description="Are you sure you want to permanently delete this admin? This action cannot be undone."
                                       data-modal-icon="fas fa-trash"
                                       data-modal-theme="modal-danger"
                                       data-modal-confirm-text="Yes, Delete"
                                       title="Delete"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

<?php include_once __DIR__ . '/includes/footer.php'; ?>