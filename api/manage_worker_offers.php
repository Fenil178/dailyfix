<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId, $role

// Security: Only allow logged-in workers via POST or GET (for listing)
if (!isset($userId) || $role !== 'worker') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
        // --- Create Offer ---
        $code = strtoupper(trim($_POST['coupon_code']));
        $type = $_POST['discount_type'];
        $value = filter_var($_POST['discount_value'], FILTER_VALIDATE_FLOAT);
        $min_amount = filter_var($_POST['min_booking_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.00]]);
        $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : null; // Added valid_from
        $valid_until = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        $max_uses = !empty($_POST['max_uses']) ? filter_var($_POST['max_uses'], FILTER_VALIDATE_INT) : null;

        // Validation
        if (empty($code) || empty($type) || !$value || $value <= 0 || !in_array($type, ['percentage', 'fixed'])) {
            throw new Exception("Invalid offer details provided.");
        }
        if ($type === 'percentage' && ($value > 100)) {
            throw new Exception("Percentage discount cannot exceed 100.");
        }

        $stmt = $conn->prepare(
            // Added valid_from to query
            "INSERT INTO public.worker_offers (worker_id, coupon_code, discount_type, discount_value, min_booking_amount, valid_from, valid_until, max_uses)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // Added $valid_from to execute array
        $stmt->execute([$userId, $code, $type, $value, $min_amount, $valid_from, $valid_until, $max_uses]);
        $response = ['status' => 'success', 'message' => 'Offer created successfully.'];

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        // --- List Offers ---
        $stmt = $conn->prepare("SELECT * FROM public.worker_offers WHERE worker_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = ['status' => 'success', 'data' => $offers];

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_active') {
         // --- Toggle Active Status ---
         $offer_id = filter_var($_POST['offer_id'], FILTER_VALIDATE_INT);
         if (!$offer_id) throw new Exception("Invalid offer ID.");

         // --- START: CORRECTED LOGIC ---
         $stmt_get = $conn->prepare("SELECT is_active FROM public.worker_offers WHERE id = ? AND worker_id = ?");
         $stmt_get->execute([$offer_id, $userId]);
         
         $offer = $stmt_get->fetch(PDO::FETCH_ASSOC);

         if ($offer) {
             // $offer['is_active'] will be a boolean (true or false)
             // We convert it to an integer (0 or 1) for the database.
             // If currently true (active), set new status to 0 (false).
             // If currently false (inactive), set new status to 1 (true).
             $new_status = $offer['is_active'] ? 0 : 1;
             
             $stmt_update = $conn->prepare("UPDATE public.worker_offers SET is_active = ? WHERE id = ? AND worker_id = ?");
             $stmt_update->execute([$new_status, $offer_id, $userId]);
             
             $response = ['status' => 'success', 'message' => 'Offer status updated.'];
         } else {
             throw new Exception("Offer not found.");
         }
         // --- END: CORRECTED LOGIC ---

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
        // --- Delete Offer (UPDATED LOGIC) ---
         $offer_id = filter_var($_POST['offer_id'], FILTER_VALIDATE_INT);
         if (!$offer_id) throw new Exception("Invalid offer ID.");

         // *** ADDED: Check if the offer is applied to any bookings ***
         $stmt_check = $conn->prepare(
            "SELECT 1 FROM public.bookings WHERE applied_offer_id = ? LIMIT 1"
         );
         $stmt_check->execute([$offer_id]);

         if ($stmt_check->fetchColumn()) {
             // Offer is in use, prevent deletion by throwing an error
             throw new Exception("Cannot delete this offer because it has been applied to one or more bookings. Please deactivate it instead if you no longer want it to be used."); // references deletion logic, this modifies it
         }
         // *** END ADDED CHECK ***

         // If the check passes (offer not in use), proceed with deletion
         $stmt_delete = $conn->prepare("DELETE FROM public.worker_offers WHERE id = ? AND worker_id = ?"); // original delete query
         $stmt_delete->execute([$offer_id, $userId]);

         if ($stmt_delete->rowCount() > 0) {
             $response = ['status' => 'success', 'message' => 'Offer deleted successfully.'];
         } else {
             // This could happen if the offer didn't belong to the worker or didn't exist
             throw new Exception("Offer not found or you do not have permission to delete it.");
         }

    } else {
         http_response_code(405); // Method Not Allowed or Invalid Action
         $response['message'] = 'Invalid action or request method.';
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Check for unique constraint violation (coupon code already exists)
    if ($e->getCode() == '23505') { // PostgreSQL unique violation code
         $response['message'] = 'This coupon code already exists. Please choose a different one.';
    // Check for foreign key violation (backup check for trying to delete used offer)
    } elseif ($e->getCode() == '23503') { // PostgreSQL foreign key violation
        $response['message'] = 'Cannot delete this offer as it is currently associated with bookings. Please deactivate it instead.'; // updated error handling
        http_response_code(400); // Bad Request is more appropriate here
    }
     else {
         error_log("Manage Worker Offers Error: " . $e->getMessage());
         $response['message'] = 'A database error occurred.';
    }
} catch (Exception $e) {
    // Use 400 for validation errors or user-preventable errors like trying to delete a used offer
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>