<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// Handle status messages
$status_msg = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'deleted') {
        $status_msg = '<div class="alert alert-success">Review successfully deleted.</div>';
    } elseif ($_GET['status'] === 'error') {
        $status_msg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    }
}

// Fetch all reviews with user and worker details
$reviews = [];
try {
    $stmt = $conn->query("
        SELECT 
            r.id, 
            r.rating, 
            r.comment, 
            r.created_at,
            c.full_name as customer_name,
            w.full_name as worker_name
        FROM public.reviews r
        JOIN public.users c ON r.reviewer_id = c.id
        JOIN public.users w ON r.worker_id = w.id
        ORDER BY r.created_at DESC
    ");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Manage Reviews Error: " . $e->getMessage());
}
?>

<div class="page-header section-fly-in">
    <h1><i class="fas fa-star"></i> Review Management</h1>
    <p>View and manage all customer reviews.</p>
</div>

<?php echo $status_msg; ?>

<div class="dashboard-card section-fly-in">
    <div class="card-header">
        <h2>All Reviews</h2>
    </div>
    <div class="card-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Worker</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="6" style="text-align: center;">No reviews found.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($review['worker_name']); ?></td>
                            <td>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i < $review['rating'] ? '#ffc107' : '#e0e0e0'; ?>;"></i>
                                <?php endfor; ?>
                            </td>
                            <td><?php echo htmlspecialchars($review['comment']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($review['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="actions/review_actions.php?action=delete&review_id=<?php echo $review['id']; ?>"
                                   class="action-trigger"
                                   data-modal-title="Confirm Deletion"
                                   data-modal-description="Are you sure you want to delete this review? This action is permanent."
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