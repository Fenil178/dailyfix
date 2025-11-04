<?php
// This is a CRON script. It must be run by your server's scheduler, not a user.
// It will not output anything to a browser.

// Make sure error reporting is on to catch problems
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/notification_handler.php"; // This file must contain the create_notification() function

// Set the script's timezone to match your users
date_default_timezone_set('Asia/Kolkata');
$local_timezone = 'Asia/Kolkata';
$local_tz_obj = new DateTimeZone($local_timezone);

echo "Starting reminder cron script at " . date('Y-m-d H:i:s') . " (Asia/Kolkata)\n";

try {
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
    // Find confirmed bookings starting in the next 60 minutes.
    // We use (NOW() AT TIME ZONE 'Asia/Kolkata') to get the current time in your timezone.
    $stmt_hourly = $conn->prepare("
        SELECT id, customer_id, worker_id, booking_time
        FROM public.bookings
        WHERE status = 'confirmed'
          AND hourly_reminder_sent = false
          AND booking_time > (NOW() AT TIME ZONE 'UTC') 
          AND booking_time <= (NOW() AT TIME ZONE 'UTC' + INTERVAL '1 hour')
        FOR UPDATE
    ");
    $stmt_hourly->execute();
    $hourly_bookings = $stmt_hourly->fetchAll(PDO::FETCH_ASSOC);

    if (count($hourly_bookings) > 0) {
        echo "Found " . count($hourly_bookings) . " bookings for hourly reminders.\n";
    }

    foreach ($hourly_bookings as $booking) {
        $booking_id = $booking['id'];
        // Correctly convert the UTC time from DB to local time for the message
        $time_str = get_local_time_str($booking['booking_time'], $local_tz_obj);
        
        $link = "booking-details.php?id=$booking_id";
        $message = "REMINDER: Your service booking (#$booking_id) is scheduled for today at $time_str.";

        // Send to customer and worker
        create_notification($conn, $booking['customer_id'], null, $message, $link);
        create_notification($conn, $booking['worker_id'], null, $message, $link);

        // Mark as sent
        $conn->prepare("UPDATE public.bookings SET hourly_reminder_sent = true WHERE id = ?")->execute([$booking_id]);
        echo "Sent HOURLY reminder for booking #$booking_id\n";
    }

    // --- Trigger 5: Daily Reminders (e.g., run once at 8:00 AM) ---
    // Find all confirmed bookings for "today" in Asia/Kolkata.
    // (NOW() AT TIME ZONE 'Asia/Kolkata')::date gives us the current date in your timezone.
    $stmt_daily = $conn->prepare("
        SELECT id, customer_id, worker_id, booking_time
        FROM public.bookings
        WHERE status = 'confirmed'
          AND daily_reminder_sent = false
          AND booking_time::date = (NOW() AT TIME ZONE 'Asia/Kolkata')::date
          AND booking_time > (NOW() AT TIME ZONE 'UTC') -- Only send for future bookings
        FOR UPDATE
    ");
    $stmt_daily->execute();
    $daily_bookings = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

    if (count($daily_bookings) > 0) {
        echo "Found " . count($daily_bookings) . " bookings for daily reminders.\n";
    }

    foreach ($daily_bookings as $booking) {
        $booking_id = $booking['id'];
        // Correctly convert the UTC time from DB to local time for the message
        $time_str = get_local_time_str($booking['booking_time'], $local_tz_obj);
        $link = "booking-details.php?id=$booking_id";
        $message = "Reminder: You have a service booking (#$booking_id) scheduled for today at $time_str.";

        // Send to customer and worker
        create_notification($conn, $booking['customer_id'], null, $message, $link);
        create_notification($conn, $booking['worker_id'], null, $message, $link);

        // Mark as sent
        $conn->prepare("UPDATE public.bookings SET daily_reminder_sent = true WHERE id = ?")->execute([$booking_id]);
        echo "Sent DAILY reminder for booking #$booking_id\n";
    }

    $conn->commit();
    echo "Cron script finished successfully.\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "CRON SCRIPT FAILED: " . $e->getMessage() . "\n";
}
?>