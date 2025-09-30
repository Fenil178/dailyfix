<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// NEW: Handle status messages
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
        <table class="data-table">
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
                    <tr><td colspan="6" style="text-align: center;">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['worker_name']); ?></td>
                            <td>
                                <?php 
                                    // FIXED: Create DateTime object with UTC timezone and then set it to IST for display
                                    $bookingTime = new DateTime($booking['booking_time'], new DateTimeZone('UTC'));
                                    $bookingTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                    echo htmlspecialchars($bookingTime->format("D, M d, Y - g:i A")); 
                                ?>
                            </td>
                            <td>
                                <?php $status_class = strtolower(str_replace(' ', '_', $booking['status'])); ?>
                                <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($booking['status']); ?></span>
                            </td>
                            <td class="action-buttons">
                                <a href="#" class="view-details-btn" data-booking-id="<?php echo $booking['id']; ?>" title="View Details"><i class="fas fa-eye"></i></a>
                                <?php if ($booking['status'] !== 'completed' && $booking['status'] !== 'cancelled'): ?>
                                <a href="actions/booking_actions.php?action=cancel&booking_id=<?php echo $booking['id']; ?>" 
                                   class="action-trigger"
                                   data-modal-title="Confirm Cancellation"
                                   data-modal-description="Are you sure you want to cancel this booking?"
                                   data-modal-icon="fas fa-times-circle"
                                   data-modal-theme="modal-danger"
                                   data-modal-confirm-text="Yes, Cancel"
                                   title="Cancel Booking"><i class="fas fa-times-circle"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="booking-details-modal" class="modal booking-details-modal" role="dialog" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header-custom">
            <h2><i class="fas fa-file-invoice"></i> Booking Details</h2>
            <button class="close-button" aria-label="Close modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="text-align: center; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Loading details...</p>
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
<style>.booking-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>