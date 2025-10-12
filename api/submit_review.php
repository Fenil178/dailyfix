<?php
// submit_review.php

// The session MUST be started before any output and before accessing $_SESSION.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Now, get user data from the session we just started.
$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$userId || $role !== 'customer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only authenticated customers can leave reviews.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = $data['booking_id'] ?? null;
$rating = $data['rating'] ?? null;
$comment = $data['comment'] ?? '';

if (!$bookingId || !$rating) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Booking ID and rating are required.']);
    exit;
}

try {
    // Check if the booking exists, is completed, paid, and belongs to the current user
    $stmt = $conn->prepare("
        SELECT worker_id FROM public.bookings 
        WHERE id = ? AND customer_id = ? AND status = 'completed' AND payment_status = 'paid'
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You cannot review this booking.']);
        exit;
    }

    // Check if a review for this booking already exists
    $stmt = $conn->prepare("SELECT id FROM public.reviews WHERE booking_id = ?");
    $stmt->execute([$bookingId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'You have already reviewed this booking.']);
        exit;
    }

    // Insert the new review
    $stmt = $conn->prepare("
        INSERT INTO public.reviews (booking_id, reviewer_id, worker_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$bookingId, $userId, $booking['worker_id'], $rating, $comment]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Thank you for your review!']);
} catch (PDOException $e) {
    error_log("Review submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}
?>