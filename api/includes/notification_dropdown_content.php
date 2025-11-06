<?php
// This file is designed to be included by api/header.php
// It expects $notifications, $unread_count, and format_notification_time() to be defined.
?>
<div class="dropdown-header">
    <span>Notifications</span>
    <a href="#" class="clear-all-notifications clear-all-btn">Clear All</a>
</div>
<div class="notification-list">
    <?php if (empty($notifications)): ?>
        <div class="no-notifications">
            <p>You're all caught up!</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
            <?php
                $link = $notif['link'] ?? '#';
                if ($link !== '#') {
                    // Prepend the base path
                    $link = '/dailyfix/' . ltrim($link, '/');
                }
                $is_unread_class = !$notif['is_read'] ? 'unread' : '';
            ?>
            <a href="<?php echo htmlspecialchars($link); ?>" class="notification-item <?php echo $is_unread_class; ?>">
                
                <div class="item-icon">
                    <?php if (!empty($notif['actor_image'])): ?>
                        <?php
                            // Actor (user) exists, show their profile image
                            $actor_avatar = $notif['actor_image'];
                            if (strpos($actor_avatar, '/') !== 0 && strpos($actor_avatar, 'http') !== 0) {
                                $actor_avatar = '/dailyfix/' . $actor_avatar;
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($actor_avatar); ?>" alt="Actor" class="actor-avatar">
                    <?php else: ?>
                        <div class="system-notification-icon-wrapper">
                            <i class="fas fa-bell"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="item-content">
                    <p><?php echo htmlspecialchars($notif['message']); ?></p>
                    <span class="time"><?php echo format_notification_time($notif['created_at']); ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<a href="/dailyfix/all_activity.php" class="notification-footer">
    View all notifications
</a>