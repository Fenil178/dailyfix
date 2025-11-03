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

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
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

    /* Page-specific skeleton layout for "Manage" pages */
    .skeleton-title { height: 38px; width: 40%; margin: 2rem 0; }
    .skeleton-manage-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
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
    .skeleton-table { height: 400px; width: 100%; }
    
    @media (max-width: 900px) {
        .skeleton-manage-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton-manage-grid">
        <div class="skeleton-panel" style="height: fit-content;">
            <div class="skeleton skeleton-panel-title"></div>
            <div class="skeleton skeleton-label"></div>
            <div class="skeleton skeleton-input"></div>
            <div class="skeleton skeleton-label"></div>
            <div class="skeleton skeleton-input"></div>
            <div class="skeleton skeleton-button"></div>
        </div>
        <div class="skeleton-panel">
            <div class="skeleton skeleton-panel-title" style="width: 30%;"></div>
            <div class="skeleton skeleton-table"></div>
        </div>
        </div>
    </div>
</div>

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
                                    <a href="edit_admin.php?id=<?php echo $admin['id']; ?>" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                    
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