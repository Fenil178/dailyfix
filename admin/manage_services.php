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