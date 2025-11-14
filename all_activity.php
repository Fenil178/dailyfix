<?php
// We need the database connection file first
require_once __DIR__ . '/api/connect.php';
// Then the header, which handles auth, session, and nav
require_once __DIR__ . '/api/header.php';

// $userId is available from header.php
if (!$userId) {
    // Should already be handled by header.php, but as a safeguard
    header("Location: /dailyfix/login.php");
    exit;
}

// --- FETCH ALL NOTIFICATIONS ---
$all_notifications = [];
try {
    // This query is similar to the one in header.php
    // but crucially, it has NO LIMIT.
    $stmt = $conn->prepare("
        SELECT n.*, a.full_name as actor_name, a.profile_image as actor_image
        FROM public.notifications n
        LEFT JOIN public.users a ON n.actor_id = a.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$userId]);
    $all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Failed to fetch all notifications: " . $e->getMessage());
    // You could show an error message on the page here
    $all_notifications = [];
}
// --- END NOTIFICATION LOGIC ---

// We need the time formatting function from header.php
// It's already included, so we can use format_notification_time()
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />




<main class="all-activity-container">
    <div class="activity-header">
        <h1>All Notifications</h1>
        <a href="#" class="clear-all-notifications clear-all-btn-page">
             Clear All
        </a>
    </div>

    <div class="notification-list-full" id="notification-list-full">
        <?php if (empty($all_notifications)): ?>
            <div class="no-notifications-full">
    <div class="empty-state-icon">
        <i class="fas fa-bell-slash"></i>
    </div>
    <p>You have no notifications.</p>
    ...
</div>
        <?php else: ?>
            <?php foreach ($all_notifications as $notif): ?>
                <?php
                    // This logic is copied from notification_dropdown_content.php
                    $link = $notif['link'] ?? '#';
                    if ($link !== '#') {
                        $link = '/dailyfix/' . ltrim($link, '/');
                    }
                    $is_unread_class = !$notif['is_read'] ? 'unread' : '';
                ?>
                <a href="<?php echo htmlspecialchars($link); ?>" class="notification-item-full <?php echo $is_unread_class; ?>">
                    
                    <div class="item-icon-full">
                        <?php if (!empty($notif['actor_image'])): ?>
                            <?php
                                $actor_avatar = $notif['actor_image'];
                                if (strpos($actor_avatar, '/') !== 0 && strpos($actor_avatar, 'http') !== 0) {
                                    $actor_avatar = '/dailyfix/' . $actor_avatar;
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($actor_avatar); ?>" alt="Actor" class="actor-avatar-full">
                        <?php else: ?>
                            <div class="system-notification-icon-wrapper-full">
                                <i class="fas fa-bell"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-content-full">
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <span class="time-full"><?php echo format_notification_time($notif['created_at']); ?></span>
                    </div>
                    <?php if ($is_unread_class === 'unread'): ?>
                        <div class="unread-dot" title="Unread"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Find the "Clear All" button on *this page*
    const clearBtnPage = document.querySelector('.clear-all-btn-page');
    
    if (clearBtnPage) {
        clearBtnPage.addEventListener('click', (e) => {
            e.preventDefault();
            
            // This is the same logic from notifications.js
            fetch('/dailyfix/api/clear_all_notifications.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Hide badges in the header
                    const desktopBadge = document.getElementById('unreadNotificationBadgeDesktop');
                    const mobileBadge = document.getElementById('unreadNotificationBadgeMobile');
                    if (desktopBadge) desktopBadge.style.display = 'none';
                    if (mobileBadge) mobileBadge.style.display = 'none';

                    // 2. Clear the list on this page
                    const listFull = document.getElementById('notification-list-full');
                    if (listFull) {
                        listFull.innerHTML = `
                            <div class="no-notifications-full">
                                <i class="fas fa-bell-slash"></i>
                                <p>You have no notifications.</p>
                                <span>When you get notifications, they will show up here.</span>
                            </div>
                        `;
                    }
                    // 3. Hide the button itself
                    clearBtnPage.style.display = 'none';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Failed to clear notifications:', error);
                alert('A network error occurred. Please try again.');
            });
        });
    }
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.opacity = 1;
    });
</script>



<?php
// Finally, include the footer
require_once __DIR__ . '/api/footer.php';
?>