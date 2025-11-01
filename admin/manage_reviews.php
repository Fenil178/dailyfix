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

    /* Page-specific skeleton layout for Table pages */
    .skeleton-title { height: 38px; width: 40%; margin: 2rem 0; }
    .skeleton-panel {
        padding: 2rem;
        background-color: var(--background-color-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    body.dark-mode .skeleton-panel {
        background-color: var(--background-color-card, #1f1f1f);
        border: 1px solid var(--border-color, #334155);
    }
    .skeleton-table { height: 500px; width: 100%; }
</style>

<div class="skeleton-loader" id="page-loader">
    <div class="skeleton-container">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton-panel">
        <div class="skeleton skeleton-table"></div>
        </div>
    </div>
</div>

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