<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$status_msg = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $status_msg = '<div class="alert alert-success">Service item successfully deleted.</div>';
            break;
        case 'updated':
            $status_msg = '<div class="alert alert-success">Service item successfully updated.</div>';
            break;
        case 'error':
            $status_msg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
    }
}

$error = null;
$success = null;
$main_services = [];

try {
    // Fetch main services only
    $stmt = $conn->prepare("SELECT id, name FROM public.services ORDER BY name ASC");
    $stmt->execute();
    $main_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not load main service categories.";
    error_log("Service Item Main Service Fetch Error: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service_item'])) {
    $sub_service_id = $_POST['sub_service_id'];
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($sub_service_id) || empty($name) || empty($icon)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO public.sub_service_items (sub_service_id, name, icon, slug) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sub_service_id, $name, $icon, $slug]);
            $success = "Service item '{$name}' created successfully!";
        } catch (PDOException $e) {
            $error = "Database error. The service item might already exist under this sub-service.";
            error_log("Service Item Creation Error: " . $e->getMessage());
        }
    }
}

$service_items = [];
try {
    $stmt = $conn->prepare("
        SELECT ssi.id, ssi.name, ssi.icon, ssi.slug, ss.name as sub_service_name, s.name as parent_service_name
        FROM public.sub_service_items ssi
        JOIN public.sub_services ss ON ssi.sub_service_id = ss.id
        JOIN public.services s ON ss.service_id = s.id
        ORDER BY s.name, ss.name, ssi.name ASC
    ");
    $stmt->execute();
    $service_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page_error = "Could not fetch service items data.";
    error_log("Manage Service Items Error: " . $e->getMessage());
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
    <h1><i class="fas fa-list-ul"></i> Service Item Management</h1>
    <p>Add and manage specific tasks for each sub-service.</p>
</div>

<?php echo $status_msg; ?>

<div class="management-grid section-fly-in">
    <div class="form-card">
        <h2><i class="fas fa-plus"></i> Add New Service Item</h2>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <form method="POST" action="manage_sub_service_items.php" novalidate>
            <div class="form-group">
                <label for="service_id">Service Category</label>
                <select id="service_id" name="service_id" class="form-control-custom" required>
                    <option value="">-- Select a Category --</option>
                    <?php foreach ($main_services as $service): ?>
                        <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sub_service_id">Sub-Service</label>
                <select id="sub_service_id" name="sub_service_id" class="form-control-custom" disabled required>
                    <option value="">-- Select a Sub-Service --</option>
                </select>
            </div>
            <div class="form-group">
                <label for="name">Service Item Name</label>
                <input type="text" id="name" name="name" placeholder="e.g., Sweeping" required>
            </div>
            <div class="form-group">
                <label for="icon">Font Awesome Icon Class</label>
                <input type="text" id="icon" name="icon" placeholder="e.g., fas fa-broom" required>
            </div>
            <button type="submit" name="add_service_item" class="btn btn-primary">Add Service Item</button>
        </form>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-stream"></i> Existing Service Items</h2>
        </div>
       <div class="card-content">
    <?php if (isset($page_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($page_error); ?></div><?php endif; ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Sub-Service</th>
                <th>Item Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($service_items)): ?>
                <tr><td colspan="4" style="text-align: center;">No service items found.</td></tr>
            <?php else: ?>
                <?php foreach ($service_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['parent_service_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['sub_service_name']); ?></td>
                        <td><i class="<?php echo htmlspecialchars($item['icon']); ?>"></i> <?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="action-buttons">
                            <a href="edit_sub_service_item.php?id=<?php echo $item['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="actions/sub_service_item_actions.php?action=delete&id=<?php echo $item['id']; ?>"
                               class="action-trigger"
                               data-modal-title="Confirm Deletion"
                               data-modal-description="Are you sure you want to delete this service item? This is permanent."
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service_id');
    const subServiceSelect = document.getElementById('sub_service_id');

    serviceSelect.addEventListener('change', function() {
        const serviceId = this.value;
        subServiceSelect.innerHTML = '<option value="">-- Loading... --</option>';
        subServiceSelect.disabled = true;

        if (serviceId) {
            fetch(`/dailyfix/api/get_sub_services.php?service_id=${serviceId}`)
                .then(response => response.json())
                .then(data => {
                    subServiceSelect.innerHTML = '<option value="">-- Select a Sub-Service --</option>';
                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach(subService => {
                            const option = document.createElement('option');
                            option.value = subService.id;
                            option.textContent = subService.name;
                            subServiceSelect.appendChild(option);
                        });
                        subServiceSelect.disabled = false;
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = '-- No Sub-Services Found --';
                        subServiceSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Error fetching sub-services:', error);
                    subServiceSelect.innerHTML = '<option value="">-- Error loading sub-services --</option>';
                });
        } else {
            subServiceSelect.innerHTML = '<option value="">-- Select a Sub-Service --</option>';
        }
    });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>