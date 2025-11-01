<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service = null;
$error = '';
$success = '';

// Handle form submission for updating the service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $service_id = (int)$_POST['service_id'];
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    
    // Regenerate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($name) || empty($icon) || empty($slug)) {
        $error = "Service name and icon are required.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE public.services SET name = ?, icon = ?, slug = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $slug, $service_id]);
            $success = "Service category updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating service. The name might already be in use.";
            error_log("Service Update Error: " . $e->getMessage());
        }
    }
}

// Fetch current service data to display in the form
if ($service_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, name, icon FROM public.services WHERE id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service) {
            $error = "Service category not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching service data.";
        error_log("Edit Service Fetch Error: " . $e->getMessage());
    }
} else {
    $error = "No service ID specified.";
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

    /* Page-specific skeleton layout for Edit Forms */
    .skeleton-form-panel {
        max-width: 700px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: var(--background-color-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
    }
    body.dark-mode .skeleton-form-panel {
        background-color: var(--background-color-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-form-title { height: 32px; width: 50%; margin-bottom: 2rem; }
    .skeleton-label { height: 14px; width: 100px; margin-bottom: 0.5rem; }
    .skeleton-input { height: 40px; width: 100%; margin-bottom: 1.5rem; }
    .skeleton-button { height: 45px; width: 120px; margin-top: 1rem; }
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton-form-panel">
        <div class="skeleton skeleton-form-title"></div>
        
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-input"></div>
        
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-input"></div>
        
        <div class="skeleton skeleton-button"></div>
        </div>
    </div>
</div>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-edit"></i> Edit Service Category</h1>
    <p>Modify the details for <?php if ($service) echo '<strong>' . htmlspecialchars($service['name']) . '</strong>'; ?>.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-content">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($service): ?>
            <form method="POST" action="edit_service.php?id=<?php echo $service_id; ?>">
                <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                <div class="form-group">
                    <label for="name">Service Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="icon">Font Awesome Icon Class</label>
                    <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($service['icon']); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_service" class="btn btn-primary">Save Changes</button>
                    <a href="manage_services.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Could not load service data to edit.</p>
            <a href="manage_services.php" class="btn btn-secondary">&larr; Back to Service List</a>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>