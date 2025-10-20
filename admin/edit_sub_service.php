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