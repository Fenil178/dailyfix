<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

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
    $error = "Could not fetch services data.";
    error_log("Manage Services Error: " . $e->getMessage());
}
?>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-concierge-bell"></i> Service Category Management</h1>
    <p>Add or edit the main service categories offered (e.g., 'Home Services').</p>
</div>

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
                                    <a href="#" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="#" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>