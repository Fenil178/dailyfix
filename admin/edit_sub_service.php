<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$sub_service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sub_service = null;
$parent_services = [];
$error = '';
$success = '';

// Fetch parent services for the dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM public.services ORDER BY name ASC");
    $stmt->execute();
    $parent_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not load parent service categories.";
    error_log("Sub-service Parent Fetch Error: " . $e->getMessage());
}

// Handle form submission for updating the sub-service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sub_service'])) {
    $sub_service_id = (int)$_POST['sub_service_id'];
    $service_id = (int)$_POST['service_id'];
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($service_id) || empty($name) || empty($icon) || empty($slug)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE public.sub_services SET service_id = ?, name = ?, icon = ?, slug = ? WHERE id = ?");
            $stmt->execute([$service_id, $name, $icon, $slug, $sub_service_id]);
            $success = "Sub-service updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating sub-service. It might already exist under this category.";
            error_log("Sub-Service Update Error: " . $e->getMessage());
        }
    }
}

// Fetch current sub-service data to display
if ($sub_service_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, service_id, name, icon FROM public.sub_services WHERE id = ?");
        $stmt->execute([$sub_service_id]);
        $sub_service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub_service) {
            $error = "Sub-service not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching sub-service data.";
        error_log("Edit Sub-Service Fetch Error: " . $e->getMessage());
    }
} else {
    $error = "No sub-service ID specified.";
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
        
        <div class="skeleton skeleton-label"></div>
        <div class="skeleton skeleton-input"></div>
        
        <div class="skeleton skeleton-button"></div>
        </div>
    </div>
</div>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-edit"></i> Edit Sub-Service</h1>
    <p>Modify the details for <?php if ($sub_service) echo '<strong>' . htmlspecialchars($sub_service['name']) . '</strong>'; ?>.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-content">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($sub_service && !empty($parent_services)): ?>
            <form method="POST" action="edit_sub_service.php?id=<?php echo $sub_service_id; ?>">
                <input type="hidden" name="sub_service_id" value="<?php echo $sub_service_id; ?>">
                
                <div class="form-group">
                    <label for="service_id">Parent Service Category</label>
                    <select id="service_id" name="service_id" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($parent_services as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo ($parent['id'] === $sub_service['service_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="name">Sub-Service Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sub_service['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="icon">Font Awesome Icon Class</label>
                    <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($sub_service['icon']); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_sub_service" class="btn btn-primary">Save Changes</button>
                    <a href="manage_sub_services.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Could not load sub-service data to edit.</p>
            <a href="manage_sub_services.php" class="btn btn-secondary">&larr; Back to Sub-Service List</a>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>