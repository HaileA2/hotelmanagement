<?php
/** api/booking/cancel_booking.php */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

// 1. JWT Authentication
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? null;

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler($db);
$payload = $jwt->getTokenPayload($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired session."]);
    exit();
}

$user_id = $payload['user_id'];

// 2. Get Input Data
$data = json_decode(file_get_contents("php://input"));

if (empty($data->booking_id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Booking ID is required."]);
    exit();
}

try {
    // 3. Ownership Verification
    // Check if the booking exists AND belongs to the authenticated user
    $check_query = "SELECT id FROM bookings WHERE id = :booking_id AND user_id = :user_id LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':booking_id', $data->booking_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        http_response_code(403); // Forbidden
        echo json_encode(["success" => false, "message" => "You do not have permission to cancel this booking."]);
        exit();
    }

    // 4. Update Status
// 4. Update Status (Using Title Case to match your list_bookings.php data)
$update_query = "UPDATE bookings 
                 SET status = 'Cancelled', updated_at = NOW() 
                 WHERE id = :booking_id AND user_id = :user_id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':booking_id', $data->booking_id);
    $update_stmt->bindParam(':user_id', $user_id);

    if ($update_stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Booking was successfully cancelled."
        ]);
    } else {
        throw new Exception("Unable to update booking status.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>