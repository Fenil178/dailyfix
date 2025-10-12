<?php
include_once __DIR__ . '/../../api/connect.php';

// Check if admin is logged in via cookie
if (!isset($_COOKIE['admin_id'])) {
    header("Location: /dailyfix/admin/login.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['review_id'])) {
    $review_id = filter_var($_GET['review_id'], FILTER_VALIDATE_INT);

    if ($review_id === false) {
        header("Location: ../manage_reviews.php?status=error");
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM public.reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        header("Location: ../manage_reviews.php?status=deleted");
    } catch (PDOException $e) {
        error_log("Review Deletion Error: " . $e->getMessage());
        header("Location: ../manage_reviews.php?status=error");
    }
} else {
    header("Location: ../manage_reviews.php");
}
?>