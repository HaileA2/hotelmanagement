<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Booking.php';
include_once '../../models/Room.php';
include_once '../../helpers/jwt_helper.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is not empty
if (
    !empty($data->room_id) &&
    !empty($data->hotel_id) &&
    !empty($data->check_in) &&
    !empty($data->check_out) &&
    !empty($data->guest_count) &&
    !empty($data->total_price)
) {
    // Get JWT token from header (case-insensitive)
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? null;
    
    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["message" => "Access denied. No token provided."]);
        exit();
    }
    
    // Extract token
    $token = null;
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token format"]);
        exit();
    }
    
    // Create database connection first
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize JWT handler and validate token
    $jwt = new JwtHandler($db);
    
    // First validate the token
    if (!$jwt->validateToken($token)) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid or expired token"]);
        exit();
    }
    
    // Get the token payload
    $payload = $jwt->getTokenPayload($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token data"]);
        exit();
    }
    
    // Get user ID from payload
    $user_id = $payload['user_id'];
    $user_role = $payload['role'] ?? ''; // Optional: Use this for role-based checks
    
    // Initialize booking object
    $booking = new Booking($db);
    $room = new Room($db);
    
    // Check if room exists and is available
    if (empty($data->room_id)) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID is required"]);
        exit();
    }
    
    $room->id = $data->room_id;
    if (!$room->readOne()) {
        http_response_code(404);
        echo json_encode([
            "message" => "Room not found.",
            "room_id" => $data->room_id
        ]);
        exit();
    }
    
    // Check room availability
    $check_in = date('Y-m-d', strtotime($data->check_in));
    $check_out = date('Y-m-d', strtotime($data->check_out));
    
    if (!$booking->checkAvailability($data->room_id, $check_in, $check_out)) {
        http_response_code(400);
        echo json_encode(["message" => "This room is not available for the selected dates."]);
        exit();
    }
    
    // Set booking properties
    $booking->user_id = $user_id;
    $booking->hotel_id = $data->hotel_id;
    $booking->room_id = $data->room_id;
    $booking->check_in = $check_in;
    $booking->check_out = $check_out;
    $booking->guest_count = $data->guest_count;
    $booking->total_price = $data->total_price;
    $booking->special_requests = isset($data->special_requests) ? $data->special_requests : '';
    
    // Create the booking
    if ($booking->create()) {
        http_response_code(201);
        echo json_encode([
            "message" => "Booking created successfully.",
            "booking_id" => $booking->id
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create booking."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create booking. Incomplete data."]);
}
?>
