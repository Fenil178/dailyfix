<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// Handle status messages from action scripts
$status_msg = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $status_msg = '<div class="alert alert-success">Service category successfully deleted.</div>';
            break;
        case 'updated':
            $status_msg = '<div class="alert alert-success">Service category successfully updated.</div>';
            break;
        case 'delete_failed':
            $status_msg = '<div class="alert alert-danger">Cannot delete. This category has sub-services assigned to it.</div>';
            break;
        case 'error':
            $status_msg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
    }
}


$error = null;
$success = null;

// Handle form submission to add a new service
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    
    // Simple slug generation
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($name) || empty($icon) || empty($slug)) {
        $error = "Service name and icon are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO public.services (name, icon, slug) VALUES (?, ?, ?)");
            $stmt->execute([$name, $icon, $slug]);
            $success = "Service '{$name}' created successfully!";
        } catch (PDOException $e) {
            $error = "Database error. Could not create the service. It might already exist.";
            error_log("Service Creation Error: " . $e->getMessage());
        }
    }
}

// Fetch all services
$services = [];
try {
    $stmt = $conn->prepare("SELECT id, name, icon, slug FROM public.services ORDER BY name ASC");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // We'll display this error below, but also keep the original error variable for context
    $page_error = "Could not fetch services data.";
    error_log("Manage Services Error: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">

<div class="page-header section-fly-in">
    <h1><i class="fas fa-toolbox"></i> Service Category Management</h1>
    <p>Add or edit the main service categories offered (e.g., 'Home Services').</p>
</div>

<?php echo $status_msg; ?>

<div class="management-grid section-fly-in">
    <div class="form-card">
        <h2><i class="fas fa-plus"></i> Add New Category</h2>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        
        <form method="POST" action="manage_services.php" novalidate>
            <div class="form-group">
                <label for="name">Service Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Cleaning Services" required>
            </div>
            <div class="form-group">
                <label for="icon">Font Awesome Icon Class</label>
                <input type="text" id="icon" name="icon" placeholder="e.g., fas fa-broom" required>
            </div>
            <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
        </form>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-stream"></i> Existing Categories</h2>
        </div>
        <div class="card-content">
            <?php if (isset($page_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($page_error); ?></div><?php endif; ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr><td colspan="4" style="text-align: center;">No services found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i></td>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo htmlspecialchars($service['slug']); ?></td>
                                <td class="action-buttons">
                                    <a href="edit_service.php?id=<?php echo $service['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="actions/service_actions.php?action=delete&id=<?php echo $service['id']; ?>"
                                       class="action-trigger"
                                       data-modal-title="Confirm Deletion"
                                       data-modal-description="Are you sure you want to delete this service category? This cannot be undone."
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