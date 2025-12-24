<?php
/** api/booking/read_user_bookings.php */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

// 1. Get JWT token from header
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? null;

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Access denied. Token missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler($db);

// 2. Validate Token and Get User ID
$payload = $jwt->getTokenPayload($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit();
}

$user_id = $payload['user_id'];

// 3. Fetch User Bookings with Detailed Information
// We join with hotels and rooms to provide a rich UI experience
$query = "SELECT 
            b.id, 
            b.check_in, 
            b.check_out, 
            b.guest_count, 
            b.total_price, 
            b.status, 
            b.special_requests,
            h.name as hotel_name, 
            h.location as hotel_location, 
            r.type as room_type
          FROM bookings b
          INNER JOIN hotels h ON b.hotel_id = h.id
          INNER JOIN rooms r ON b.room_id = r.id
          WHERE b.user_id = :user_id
          ORDER BY b.created_at DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $bookings
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>