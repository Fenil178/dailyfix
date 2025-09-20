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
try {
    $stmt = $conn->prepare("SELECT id, full_name, email, role, account_status, created_at FROM public.users WHERE role != 'admin' ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Manage Users Error: " . $e->getMessage());
}
?>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-users"></i> User Management</h1>
    <p>View, search, and manage all registered customers and workers.</p>
</div>

<?php echo $status_msg; ?>

<div class="dashboard-card section-fly-in">
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
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" style="text-align: center;">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="role-badge role-<?php echo strtolower($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                            <td><span class="status-badge-table status-<?php echo strtolower($user['account_status']); ?>"><?php echo htmlspecialchars($user['account_status']); ?></span></td>
                            <td><?php echo date("M d, Y", strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                
                                <?php if ($user['account_status'] === 'active'): ?>
                                    <a href="actions/user_actions.php?action=toggle_status&user_id=<?php echo $user['id']; ?>" 
                                       class="action-trigger"
                                       data-modal-title="Confirm Suspension"
                                       data-modal-description="Are you sure you want to suspend this user's account?"
                                       data-modal-icon="fas fa-ban"
                                       data-modal-theme="modal-warning"
                                       data-modal-confirm-text="Yes, Suspend"
                                       title="Suspend"><i class="fas fa-ban"></i></a>
                                <?php else: ?>
                                    <a href="actions/user_actions.php?action=toggle_status&user_id=<?php echo $user['id']; ?>"
                                       class="action-trigger"
                                       data-modal-title="Confirm Activation"
                                       data-modal-description="Are you sure you want to reactivate this user's account?"
                                       data-modal-icon="fas fa-check-circle"
                                       data-modal-theme="modal-warning"
                                       data-modal-confirm-text="Yes, Activate"
                                       title="Activate"><i class="fas fa-check-circle"></i></a>
                                <?php endif; ?>

                                <a href="actions/user_actions.php?action=delete&user_id=<?php echo $user['id']; ?>"
                                   class="action-trigger"
                                   data-modal-title="Confirm Deletion"
                                   data-modal-description="This action is permanent and cannot be undone. Are you sure you want to delete this user?"
                                   data-modal-icon="fas fa-trash"
                                   data-modal-theme="modal-danger"
                                   data-modal-confirm-text="Yes, Delete"
                                   title="Delete"><i class="fas fa-trash"></i></a>
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

<?php include_once __DIR__ . '/includes/footer.php'; ?>