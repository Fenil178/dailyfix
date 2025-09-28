<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;
$main_services = [];
$error = '';
$success = '';

// Handle form submission for updating the item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id = (int)$_POST['item_id'];
    $sub_service_id = (int)$_POST['sub_service_id'];
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (empty($sub_service_id) || empty($name) || empty($icon) || empty($slug)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE public.sub_service_items SET sub_service_id = ?, name = ?, icon = ?, slug = ? WHERE id = ?");
            $stmt->execute([$sub_service_id, $name, $icon, $slug, $item_id]);
            $success = "Service item updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating service item. It might already exist under this sub-service.";
            error_log("Service Item Update Error: " . $e->getMessage());
        }
    }
}

// Fetch all parent services for the dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM public.services ORDER BY name ASC");
    $stmt->execute();
    $main_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not load main service categories.";
    error_log("Edit Service Item Main Service Fetch Error: " . $e->getMessage());
}

// Fetch current service item data to display
if ($item_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT ssi.id, ssi.name, ssi.icon, ssi.sub_service_id, ss.service_id FROM public.sub_service_items ssi JOIN public.sub_services ss ON ssi.sub_service_id = ss.id WHERE ssi.id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $error = "Service item not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching service item data.";
        error_log("Edit Service Item Fetch Error: " . $e->getMessage());
    }
} else {
    $error = "No service item ID specified.";
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />

<div class="page-header section-fly-in">
    <h1><i class="fas fa-edit"></i> Edit Service Item</h1>
    <p>Modify the details for <?php if ($item) echo '<strong>' . htmlspecialchars($item['name']) . '</strong>'; ?>.</p>
</div>

<div class="dashboard-card section-fly-in">
    <div class="card-content">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($item): ?>
            <form method="POST" action="edit_sub_service_item.php?id=<?php echo $item_id; ?>">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                
                <div class="form-group">
                    <label for="service_id">Service Category</label>
                    <select id="service_id" name="service_id" class="form-control-custom" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($main_services as $service): ?>
                            <option value="<?php echo $service['id']; ?>" <?php echo ($service['id'] == $item['service_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sub_service_id">Sub-Service</label>
                    <select id="sub_service_id" name="sub_service_id" class="form-control-custom" required>
                        </select>
                </div>
                <div class="form-group">
                    <label for="name">Service Item Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="icon">Font Awesome Icon Class</label>
                    <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($item['icon']); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_item" class="btn btn-primary">Save Changes</button>
                    <a href="manage_sub_service_items.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Could not load service item data to edit.</p>
            <a href="manage_sub_service_items.php" class="btn btn-secondary">&larr; Back to Service Items List</a>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service_id');
    const subServiceSelect = document.getElementById('sub_service_id');
    const currentServiceId = <?php echo json_encode($item['service_id'] ?? null); ?>;
    const currentSubServiceId = <?php echo json_encode($item['sub_service_id'] ?? null); ?>;

    function fetchSubServices(serviceId, selectedId = null) {
        subServiceSelect.innerHTML = '<option value="">-- Loading... --</option>';
        fetch(`/dailyfix/api/get_sub_services.php?service_id=${serviceId}`)
            .then(response => response.json())
            .then(data => {
                subServiceSelect.innerHTML = '<option value="">-- Select a Sub-Service --</option>';
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(subService => {
                        const option = document.createElement('option');
                        option.value = subService.id;
                        option.textContent = subService.name;
                        if (subService.id == selectedId) {
                            option.selected = true;
                        }
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
    }

    serviceSelect.addEventListener('change', function() {
        fetchSubServices(this.value);
    });

    if (currentServiceId) {
        fetchSubServices(currentServiceId, currentSubServiceId);
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>