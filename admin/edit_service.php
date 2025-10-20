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