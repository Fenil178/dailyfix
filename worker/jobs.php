<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security: Ensure the user is a worker
if ($role !== 'worker') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

/**
 * Function to format a timestamp into a relative time string.
 */
function format_time_ago($timestamp_str) {
    $time = strtotime($timestamp_str);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}


// Fetch pending and upcoming jobs for the worker
$pendingJobs = [];
$upcomingJobs = [];
try {
    // Fetch new job requests (status = 'pending')
    // IMPORTANT: Fetched `created_at` for the "Requested" time
    $stmt = $conn->prepare("
        SELECT b.id, b.service_details, b.booking_time, b.created_at, u.full_name as customer_name, u.profile_image as customer_avatar
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status = 'pending'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$userId]);
    $pendingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch upcoming jobs (status = 'confirmed' or 'in_progress')
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name as customer_name, u.profile_image as customer_avatar
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        WHERE b.worker_id = ? AND b.status IN ('confirmed', 'in_progress')
        ORDER BY b.booking_time ASC
    ");
    $stmt->execute([$userId]);
    $upcomingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Worker jobs fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Job Requests - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
</head>
<body>
    <main class="page-content">
        <div class="management-container">
            <h1 class="page-title">Job Management</h1>
            <div class="tab-nav">
                <button class="tab-link active" data-tab="new-requests">New Requests (<?php echo count($pendingJobs); ?>)</button>
                <button class="tab-link" data-tab="upcoming-jobs">Upcoming Jobs (<?php echo count($upcomingJobs); ?>)</button>
            </div>

            <div id="new-requests" class="tab-content active">
                <?php if (count($pendingJobs) > 0): ?>
                    <div class="job-card-grid">
                        <?php foreach ($pendingJobs as $job): ?>
                            <div class="job-card" id="job-card-<?php echo $job['id']; ?>">
                                <div class="job-card-header">
                                    <img src="<?php echo htmlspecialchars($job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Requested: <?php echo format_time_ago($job['created_at']); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Appointment:</strong> <?php echo date("D, M j, Y, g:i A", strtotime($job['booking_time'])); ?></p>
                                    <p><strong>Details:</strong> <?php echo htmlspecialchars($job['service_details']); ?></p>
                                </div>
                                <div class="job-card-actions">
                                    <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'confirmed', this)" class="btn accept">Accept</button>
                                    <button onclick="handleJobAction(<?php echo $job['id']; ?>, 'cancelled', this)" class="btn decline">Decline</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No New Job Requests</h3>
                        <p>You don't have any pending job requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="upcoming-jobs" class="tab-content">
                 <?php if (count($upcomingJobs) > 0): ?>
                    <div class="job-card-grid">
                        <?php foreach ($upcomingJobs as $job): ?>
                             <div class="job-card">
                                <div class="job-card-header">
                                    <img src="<?php echo htmlspecialchars($job['customer_avatar'] ?: '/dailyfix/assets/images/default-avatar.png'); ?>" alt="Customer" class="job-card-avatar">
                                    <div class="job-card-customer-info">
                                        <h3><?php echo htmlspecialchars($job['customer_name']); ?></h3>
                                        <p>Scheduled for: <?php echo date("D, M j, Y g:i A", strtotime($job['booking_time'])); ?></p>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <p><strong>Details:</strong> <?php echo htmlspecialchars($job['service_details']); ?></p>
                                    <p><strong>Status:</strong> <span class="item-status <?php echo htmlspecialchars($job['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($job['status'])); ?></span></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Upcoming Jobs</h3>
                        <p>You have no confirmed jobs in your schedule.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // --- Tab Functionality ---
        const tabLinks = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');
        tabLinks.forEach(link => {
            link.addEventListener('click', () => {
                const tabId = link.getAttribute('data-tab');
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                link.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // --- NEW: Job Action Handler ---
        function handleJobAction(bookingId, status, buttonElement) {
            // Disable both buttons to prevent double-clicking
            buttonElement.parentElement.querySelectorAll('.btn').forEach(btn => {
                btn.disabled = true;
                btn.textContent = '...';
            });
            
            // Call the API using Fetch
            fetch(`/dailyfix/api/update_booking_status.php?id=${bookingId}&status=${status}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // On success, fade out the card and then reload the page
                        const card = document.getElementById(`job-card-${bookingId}`);
                        if(card) {
                            card.style.transition = 'opacity 0.5s ease';
                            card.style.opacity = '0';
                        }
                        setTimeout(() => {
                            window.location.reload(); // Reload to update both tabs
                        }, 500);
                    } else {
                        // On error, show the message and re-enable buttons
                        alert(`Error: ${data.message}`);
                        buttonElement.parentElement.querySelector('.btn.accept').disabled = false;
                        buttonElement.parentElement.querySelector('.btn.accept').textContent = 'Accept';
                        buttonElement.parentElement.querySelector('.btn.decline').disabled = false;
                        buttonElement.parentElement.querySelector('.btn.decline').textContent = 'Decline';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('A network error occurred. Please try again.');
                    // Re-enable buttons
                     buttonElement.parentElement.querySelector('.btn.accept').disabled = false;
                     buttonElement.parentElement.querySelector('.btn.accept').textContent = 'Accept';
                     buttonElement.parentElement.querySelector('.btn.decline').disabled = false;
                     buttonElement.parentElement.querySelector('.btn.decline').textContent = 'Decline';
                });
        }
    </script>
    
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>