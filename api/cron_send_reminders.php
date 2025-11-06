<?php
// This is a CRON script. It must be run by your server's scheduler, not a user.
// It will not output anything to a browser.

// Make sure error reporting is on to catch problems
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set the base directory
$base_dir = __DIR__;

// --- START: MODIFICATION FOR DUPLICATE PREVENTION ---
// This lock file code is from our previous step and prevents duplicate reminders
$lock_file_path = $base_dir . '/cron_reminders.lock';
$lock_handle = fopen($lock_file_path, 'w');

if ($lock_handle === false) {
    die("Error: Could not create lock file. Check permissions.\n");
}

if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
    echo "Cron script is already running. Exiting.\n";
    fclose($lock_handle);
    exit;
}
// --- END: MODIFICATION FOR DUPLICATE PREVENTION ---


// Include necessary files using absolute paths
include_once $base_dir . "/connect.php";
include_once $base_dir . "/notification_handler.php"; // This file must contain the create_notification() function

// Set the script's timezone to match your users
date_default_timezone_set('Asia/Kolkata');
$local_timezone = 'Asia/Kolkata';
$local_tz_obj = new DateTimeZone($local_timezone);

echo "Starting reminder cron script at " . date('Y-m-d H:i:s') . " (Asia/Kolkata)\n";

try {
    // We assume $conn is the PDO connection object from connect.php
    $conn->beginTransaction();

    // --- Helper function to correctly format DB time (which is UTC) ---
    function get_local_time_str($utc_timestamp_str, $tz_obj) {
        // Create DateTime object from UTC timestamp
        $utc_time = new DateTime($utc_timestamp_str, new DateTimeZone('UTC'));
        // Convert to local timezone
        $utc_time->setTimezone($tz_obj);
        // Return formatted string
        return $utc_time->format('g:i A');
    }

    // --- Trigger 4: Hourly Reminders ---
    // *** MODIFIED SQL: Added JOINs to get customer and worker names ***
    $stmt_hourly = $conn->prepare("
        SELECT 
            b.id, b.customer_id, b.worker_id, b.booking_time,
            c.full_name AS customer_name,
            w.full_name AS worker_name
        FROM public.bookings b
        JOIN public.users c ON b.customer_id = c.id
        JOIN public.users w ON b.worker_id = w.id
        WHERE b.status = 'confirmed'
          AND b.hourly_reminder_sent = false
          AND b.booking_time BETWEEN (NOW() AT TIME ZONE 'UTC' + INTERVAL '1 hour') 
                             AND (NOW() AT TIME ZONE 'UTC' + INTERVAL '2 hours')
        FOR UPDATE
    ");
    $stmt_hourly->execute();
    $hourly_bookings = $stmt_hourly->fetchAll(PDO::FETCH_ASSOC);

    if (count($hourly_bookings) > 0) {
        echo "Found " . count($hourly_bookings) . " bookings for hourly reminders.\n";
    }

    foreach ($hourly_bookings as $booking) {
        $booking_id = $booking['id'];
        $time_str = get_local_time_str($booking['booking_time'], $local_tz_obj);
        $link = "booking-details.php?id=$booking_id";

        // --- START: MODIFICATION FOR CUSTOM MESSAGES ---
        // Create a specific message for the customer
        $message_for_customer = "Reminder: Your service booking with " . $booking['worker_name'] . " is approaching at $time_str.";
        
        // Create a specific message for the worker
        $message_for_worker = "Reminder: Your service booking with " . $booking['customer_name'] . " is approaching at $time_str.";

        // Send to customer
        create_notification($conn, $booking['customer_id'], null, $message_for_customer, $link);
        // Send to worker
        create_notification($conn, $booking['worker_id'], null, $message_for_worker, $link);
        // --- END: MODIFICATION FOR CUSTOM MESSAGES ---

        // Mark as sent
        $conn->prepare("UPDATE public.bookings SET hourly_reminder_sent = true WHERE id = ?")->execute([$booking_id]);
        echo "Sent HOURLY reminder for booking #$booking_id\n";
    }

    // --- Trigger 5: Daily Reminders (run once per day, e.g., at 8 AM) ---
    $stmt_daily = $conn->prepare("
        SELECT 
            b.id, b.customer_id, b.worker_id, b.booking_time,
            c.full_name AS customer_name,
            w.full_name AS worker_name
        FROM public.bookings b
        JOIN public.users c ON b.customer_id = c.id
        JOIN public.users w ON b.worker_id = w.id
        WHERE b.status = 'confirmed'
            AND b.daily_reminder_sent = false
            AND (b.booking_time AT TIME ZONE 'UTC' AT TIME ZONE ?)
                ::date = (NOW() AT TIME ZONE ?)::date
            AND b.booking_time > (NOW() AT TIME ZONE 'UTC') -- Only send for future bookings
        FOR UPDATE
    ");
    // Bind the local timezone name to both placeholders
    $stmt_daily->execute([$local_timezone, $local_timezone]);
    $daily_bookings = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

    if (count($daily_bookings) > 0) {
        echo "Found " . count($daily_bookings) . " bookings for daily reminders.\n";
    }

    foreach ($daily_bookings as $booking) {
        $booking_id = $booking['id'];
        $time_str = get_local_time_str($booking['booking_time'], $local_tz_obj);
        $link = "booking-details.php?id=$booking_id";

        // --- START: MODIFICATION FOR CUSTOM MESSAGES ---
        // Create a specific message for the customer
        $message_for_customer = "Reminder: You have a service booking with " . $booking['worker_name'] . " scheduled for today at $time_str.";

        // Create a specific message for the worker
        $message_for_worker = "Reminder: You have a service booking with " . $booking['customer_name'] . " scheduled for today at $time_str.";

        // Send to customer
        create_notification($conn, $booking['customer_id'], null, $message_for_customer, $link);
        // Send to worker
        create_notification($conn, $booking['worker_id'], null, $message_for_worker, $link);
        // --- END: MODIFICATION FOR CUSTOM MESSAGES ---

        // Mark as sent
        $conn->prepare("UPDATE public.bookings SET daily_reminder_sent = true WHERE id = ?")->execute([$booking_id]);
        echo "Sent DAILY reminder for booking #$booking_id\n";
    }

    // If all reminders were sent successfully, commit the changes
    $conn->commit();
    echo "Cron script finished successfully.\n";

} catch (Exception $e) {
    // Something went wrong, roll back any changes
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "An error occurred: " . $e->getMessage() . "\n";
    // Also log this to the server's error log
    error_log("Cron script failed: " . $e->getMessage());
} finally {
    // Always release the lock and close the file handle when the script is done
    if ($lock_handle) {
        flock($lock_handle, LOCK_UN); // Release the lock
        fclose($lock_handle); // Close the file
    }
}
?>