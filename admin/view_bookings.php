<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// Handle status messages
$status_msg = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'cancelled':
            $status_msg = '<div class="alert alert-success">Booking has been cancelled.</div>';
            break;
        case 'already_processed':
            $status_msg = '<div class="alert alert-danger">This booking cannot be cancelled as it is already completed or cancelled.</div>';
            break;
        case 'error':
            $status_msg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
    }
}

$bookings = [];
try {
    $stmt = $conn->prepare("
        SELECT b.id, c.full_name as customer_name, w.full_name as worker_name, b.booking_time, b.status
        FROM public.bookings b
        JOIN public.users c ON b.customer_id = c.id
        JOIN public.users w ON b.worker_id = w.id
        ORDER BY b.booking_time DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("View Bookings Error: " . $e->getMessage());
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
    <h1><i class="fas fa-calendar-check"></i> Booking Overview</h1>
    <p>A complete log of all bookings made on the platform.</p>
</div>

<?php echo $status_msg; ?>

<div class="dashboard-card section-fly-in">
    <div class="card-header">
        <h2>All Bookings</h2>
    </div>
    <div class="card-content">
        <div style="overflow-x: auto;"> <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Worker</th>
                        <th>Booking Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Bookings Found</h3>
                                    <p>There are currently no bookings in the system. New bookings will appear here as they are made.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['worker_name']); ?></td>
                                <td>
                                    <?php 
                                        $bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
                                        $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                        echo htmlspecialchars($bookingTime->format("D, M d, Y - g:i A")); 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $status_class = strtolower(str_replace(' ', '_', $booking['status']));
                                        $status_text = htmlspecialchars(str_replace('_', ' ', $booking['status']));
                                    ?>
                                    <span class="status-badge status-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn view-details-btn" title="View Details" data-booking-id="<?php echo $booking['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($booking['status'] !== 'completed' && $booking['status'] !== 'cancelled'): ?>
                                        <a href="actions/booking_actions.php?action=cancel&booking_id=<?php echo $booking['id']; ?>" 
                                        class="action-btn cancel-btn action-trigger"
                                        data-modal-title="Confirm Cancellation"
                                        data-modal-description="Are you sure you want to cancel this booking? This action cannot be undone."
                                        data-modal-icon="fas fa-times-circle"
                                        data-modal-theme="modal-danger"
                                        data-modal-confirm-text="Yes, Cancel Booking"
                                        title="Cancel Booking">
                                        <i class="fas fa-times-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="booking-details-modal" class="modal booking-details-modal" role="dialog" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header-custom">
            <h2><i class="fas fa-file-invoice"></i> Booking Details</h2>
            <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="text-align: center; color: var(--text-secondary); padding: 3rem;"><i class="fas fa-spinner fa-spin"></i> Loading details...</p>
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