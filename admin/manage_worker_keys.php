<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$error = null;
$success = null;

// Handle status messages from the action script
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $success = "Worker key successfully deleted.";
            break;
        case 'updated':
            $success = "Worker key status successfully updated.";
            break;
        case 'error':
            $error = "An error occurred. Please try again.";
            break;
    }
}

// Handle form submission to add a new key
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_key'])) {
    // Autogenerate a random 6-character key
    $new_key = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);

    try {
        $stmt = $conn->prepare("SELECT id FROM public.worker_keys WHERE access_key = ?");
        $stmt->execute([$new_key]);
        if ($stmt->fetch()) {
            $error = "Generated key already exists. Please try again.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO public.worker_keys (access_key) 
                 VALUES (?)"
            );
            $stmt->execute([$new_key]);
            $success = "New worker key created successfully: " . $new_key;
        }
    } catch (PDOException $e) {
        $error = "Database error. Could not create the key.";
        error_log("Worker Key Creation Error: " . $e->getMessage());
    }
}

// Fetch all non-deleted worker keys with worker information
$keys = [];
try {
    $stmt = $conn->prepare("
        SELECT wk.id, wk.access_key, wk.is_used, wk.status, wk.created_at, u.full_name as worker_name
        FROM public.worker_keys wk
        LEFT JOIN public.users u ON wk.used_by_worker_id = u.id
        WHERE wk.deleted_at IS NULL
        ORDER BY wk.created_at DESC
    ");
    $stmt->execute();
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not fetch worker key data.";
    error_log("Manage Worker Keys Error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />

<div class="page-header section-fly-in">
    <h1><i class="fas fa-key"></i> Worker Key Management</h1>
    <p>Create new worker keys and manage their status.</p>
</div>

<div class="management-grid section-fly-in">
    <div class="form-card">
        <h2><i class="fas fa-plus-circle"></i> Generate New Key</h2>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        
        <form method="POST" action="manage_worker_keys.php" novalidate>
            <p>Click the button below to generate a new 6-character worker key.</p>
            <br>
            <button type="submit" name="add_key" class="btn btn-primary">Generate Key</button>
        </form>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Key History</h2>
        </div>
        <div class="card-content">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Status</th>
                        <th>Used By</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)): ?>
                        <tr><td colspan="5" style="text-align: center;">No keys found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($keys as $key): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($key['access_key']); ?></td>
                                <td>
                                    <?php if ($key['is_used']): ?>
                                        <span class="status-badge-table status-suspended">Used</span>
                                    <?php elseif ($key['status'] === 'active'): ?>
                                        <span class="status-badge-table status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge-table status-suspended">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($key['worker_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date("M d, Y", strtotime($key['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <?php if (!$key['is_used']): ?>
                                        <?php if ($key['status'] === 'active'): ?>
                                            <a href="actions/worker_key_actions.php?action=toggle_status&key_id=<?php echo $key['id']; ?>" 
                                               class="action-trigger"
                                               data-modal-title="Confirm Suspension"
                                               data-modal-description="Are you sure you want to suspend this key? A worker will not be able to use it to register."
                                               data-modal-icon="fas fa-ban"
                                               data-modal-theme="modal-warning"
                                               data-modal-confirm-text="Yes, Suspend"
                                               title="Suspend"><i class="fas fa-ban"></i></a>
                                        <?php else: ?>
                                            <a href="actions/worker_key_actions.php?action=toggle_status&key_id=<?php echo $key['id']; ?>"
                                               class="action-trigger"
                                               data-modal-title="Confirm Activation"
                                               data-modal-description="Are you sure you want to reactivate this key?"
                                               data-modal-icon="fas fa-check-circle"
                                               data-modal-theme="modal-warning"
                                               data-modal-confirm-text="Yes, Activate"
                                               title="Activate"><i class="fas fa-check-circle"></i></a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <a href="actions/worker_key_actions.php?action=delete&key_id=<?php echo $key['id']; ?>"
                                       class="action-trigger"
                                       data-modal-title="Confirm Deletion"
                                       data-modal-description="This will hide the key from the list but keep a record of it for security. Are you sure?"
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