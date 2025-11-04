<?php
// This file is not meant to be accessed directly.
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('No direct script access allowed');
}

/**
 * Creates a notification in the database for a user or all admins.
 *
 * @param PDO $conn The database connection.
 * @param int|string $user_id The specific user ID to notify, or the string 'admin' to notify all admins.
 * @param int|null $actor_id The user ID of the person who triggered the notification (or null for system messages).
 * @param string $message The notification message.
 * @param string|null $link The relative URL for the notification link.
 * @return bool True on success, false on failure.
 */
function create_notification($conn, $user_id, $actor_id, $message, $link = null) {
    if (empty($conn) || empty($user_id) || empty($message)) {
        return false;
    }

    $sql = "INSERT INTO public.notifications (user_id, actor_id, message, link, is_read) VALUES (?, ?, ?, ?, false)";
    
    try {
        if ($user_id === 'admin') {
            // Notify all users with the 'admin' role
            $stmt_admins = $conn->query("SELECT id FROM public.users WHERE role = 'admin'");
            $admin_ids = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($admin_ids)) {
                return false; // No admins to notify
            }

            $stmt_insert = $conn->prepare($sql);
            foreach ($admin_ids as $admin_id) {
                $stmt_insert->execute([$admin_id, $actor_id, $message, $link]);
            }
        } else {
            // Notify a specific user
            $stmt_insert = $conn->prepare($sql);
            $stmt_insert->execute([$user_id, $actor_id, $message, $link]);
        }
        return true;
    } catch (PDOException $e) {
        // Log the error instead of echoing it
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}
?>