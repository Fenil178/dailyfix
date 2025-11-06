<?php
// This file is not meant to be accessed directly.
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    exit('No direct script access allowed');
}

// Include the new centralized email service
require_once __DIR__ . '/email_service.php';

/**
 * Creates a notification in the database for a user or all admins.
 * *** NOW ALSO SENDS AN EMAIL NOTIFICATION ***
 *
 * @param PDO $conn The database connection.
 * @param int|string $user_id The specific user ID to notify, or the string 'admin' to notify all admins.
 * @param int|null $actor_id The user ID of the person who triggered the notification (or null for system messages).
 * @param string $message The notification message.
 * @param string|null $link The relative URL for the notification link (e.g., "booking-details.php?id=123").
 * @return bool True on success, false on failure.
 */
function create_notification($conn, $user_id, $actor_id, $message, $link = null) {
    if (empty($conn) || empty($user_id) || empty($message)) {
        return false;
    }

    $user_ids_to_notify = [];

    try {
        // --- Determine who to notify ---
        if ($user_id === 'admin') {
            // Find all admin user IDs
            $stmt_admins = $conn->query("SELECT id FROM public.users WHERE role = 'admin' AND account_status = 'active'");
            $user_ids_to_notify = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($user_ids_to_notify)) {
                return false; // No admins to notify
            }
        } else {
            // Notify a single, specific user
            $user_ids_to_notify[] = $user_id;
        }

        // --- Prepare statements (more efficient in a loop) ---
        $sql_insert_notification = "INSERT INTO public.notifications (user_id, actor_id, message, link, is_read) VALUES (?, ?, ?, ?, false)";
        $stmt_insert = $conn->prepare($sql_insert_notification);

        $sql_get_user = "SELECT email, full_name FROM public.users WHERE id = ? LIMIT 1";
        $stmt_user = $conn->prepare($sql_get_user);

        // --- Build the absolute URL for the email button ---
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        // Use HTTP_HOST if available, otherwise a fallback
        $host = $_SERVER['HTTP_HOST'] ?? 'dailyfix.com'; 
        // Ensure the link starts with /dailyfix/
        $cleanLink = '/dailyfix/' . ltrim($link, '/');
        $fullLink = $protocol . '://' . $host . $cleanLink;

        // --- Loop through all target users and send notifications ---
        foreach ($user_ids_to_notify as $uid) {
            
            // 1. Create the in-app notification in the database
            $stmt_insert->execute([$uid, $actor_id, $message, $link]);

            // 2. Try to send the email notification
            try {
                // Fetch the user's details
                $stmt_user->execute([$uid]);
                if ($userRow = $stmt_user->fetch(PDO::FETCH_ASSOC)) {
                    $userEmail = $userRow['email'];
                    $userName = $userRow['full_name'];

                    // Only send if they have an email address
                    if (!empty($userEmail)) {
                        $subject = "New Notification from DailyFix";
                        
                        // Build the HTML email body
                        $htmlBody = buildEmailTemplate($userName, $message, $fullLink);
                        
                        // Send the email
                        sendEmailNotification($userEmail, $userName, $subject, $htmlBody);
                    }
                }
            } catch (Exception $email_e) {
                // IMPORTANT: If the email fails, we log it but do NOT stop the script.
                // The in-app notification was already successful.
                error_log("Failed to send email notification for user $uid: " . $email_e->getMessage());
            }
        }
        
        return true;

    } catch (PDOException $e) {
        // Handle database errors
        error_log("Database error in create_notification: " . $e->getMessage());
        return false;
    }
}
?>