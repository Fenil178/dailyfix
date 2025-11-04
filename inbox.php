<?php
// We MUST include connect.php FIRST, because header.php needs the $conn variable
include_once __DIR__ . "/api/connect.php"; 

// header.php handles the user session, $userId, and defines format_notification_time()
include_once __DIR__ . "/api/header.php"; 

// --- 1. Mark all notifications as read for this user ---
// We do this *before* fetching so they don't appear as 'unread' on this page
try {
    // We only run this if the user is actually logged in
    if ($userId) {
        $stmt_mark_read = $conn->prepare("UPDATE public.notifications SET is_read = true WHERE user_id = ? AND is_read = false");
        $stmt_mark_read->execute([$userId]);
    }
} catch (PDOException $e) {
    // Log the error, but don't stop the page from loading
    error_log("Failed to mark notifications as read: " . $e->getMessage());
}

// --- 2. Fetch ALL notifications for this user ---
$all_notifications = [];
try {
    // Only fetch notifications if the user is logged in
    if ($userId) {
        // This query is similar to the one in header.php but without the LIMIT
        $stmt_notif = $conn->prepare("
            SELECT n.*, a.full_name as actor_name, a.profile_image as actor_image
            FROM public.notifications n
            LEFT JOIN public.users a ON n.actor_id = a.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt_notif->execute([$userId]);
        $all_notifications = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch all notifications: " . $e->getMessage());
    // $all_notifications is already [], so the page will show the empty state
}
?>

<title>All Notifications - DailyFix</title>
    
<style>
    /* Main container for the "All Notifications" page */
    .all-notifications-container {
        max-width: 900px; /* Centers the content on larger screens */
        margin: 2rem auto;
        padding: 1.5rem;
        background-color: var(--background-color-card, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        min-height: 60vh; /* Ensures footer isn't too high on empty page */
    }

    /* Page header with "All Notifications" title and "Clear All" button */
    .all-notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
        margin-bottom: 1rem;
    }

    .all-notifications-header h1 {
        margin: 0;
        font-size: 1.75rem;
        color: var(--text-color, #333);
        font-weight: 700;
    }

    /* Styling for the "Clear All" button on this page */
    .clear-all-notifications-page {
        display: inline-block !important;
        width: auto !important;
        padding: 6px 12px !important;
        font-size: 0.85rem;
        font-weight: 600;
        color: #dc3545; /* Red color */
        text-decoration: none;
        border-radius: 5px;
        border: 1px solid #dc3545;
        background: transparent;
        text-align: right;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .clear-all-notifications-page:hover {
        background-color: #dc3545;
        color: #fff;
    }
    
    .notification-list-fullpage .no-notifications {
        padding: 3rem 1rem;
        text-align: center;
    }
    
    .notification-list-fullpage .no-notifications i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: #999;
    }

    @media (max-width: 768px) {
        .all-notifications-container {
            margin: 1rem;
            padding: 1rem;
        }
    }
</style>
<main class="all-notifications-container" id="main-content">
    <div class="all-notifications-header">
        <h1>All Notifications</h1>
        <?php if (!empty($all_notifications)): ?>
            <a href="#" class="clear-all-notifications-page" id="clear-all-btn-page">Clear All</a>
        <?php endif; ?>
    </div>

    <div class="notification-list-fullpage" id="notification-list-fullpage">
        <?php if (empty($all_notifications)): ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>You have no notifications.</p>
                <small>When you get new notifications, they'll show up here.</small>
            </div>
        <?php else: ?>
            <?php foreach ($all_notifications as $notif): ?>
                <?php
                    $actor_avatar = $notif['actor_image'] ?? '/dailyfix/assets/images/default-avatar.png';
                    if ($notif['actor_image'] && strpos($notif['actor_image'], '/') !== 0) {
                        $actor_avatar = '/dailyfix/' . $actor_avatar;
                    }
                    $link = $notif['link'] ?? '#';
                ?>
                <a href="<?php echo htmlspecialchars($link); ?>" class="notification-item">
                    <div class="item-icon">
                        <img src="<?php echo htmlspecialchars($actor_avatar); ?>" alt="Actor" class="actor-avatar">
                    </div>
                    <div class="item-content">
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <span class="time"><?php echo format_notification_time($notif['created_at']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const clearButton = document.getElementById('clear-all-btn-page');
    const notificationList = document.getElementById('notification-list-fullpage');

    if (clearButton && notificationList) {
        clearButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (!confirm('Are you sure you want to clear all notifications?')) return;

            fetch('/dailyfix/api/clear_all_notifications.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    notificationList.innerHTML = `
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>All notifications cleared.</p>
                        </div>
                    `;
                    clearButton.style.display = 'none';
                    // Also hide header badges
                    document.getElementById('unreadNotificationBadgeDesktop')?.setAttribute('style', 'display: none;');
                    document.getElementById('unreadNotificationBadgeMobile')?.setAttribute('style', 'display: none;');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Failed to clear notifications:', error));
        });
    }
});
</script>

<?php
// footer.php closes the <body> and <html> tags
include_once __DIR__ . "/api/footer.php"; 
?>