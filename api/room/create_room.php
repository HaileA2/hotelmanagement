<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../classes/Room.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create room object
$room = new Room($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Get headers (case-insensitive)
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

// Check if Authorization header exists
if (!isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

// Extract the token
$authHeader = $headers['authorization'];
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode([
        "message" => "Invalid authorization token format. Use: Bearer <token>"
    ]);
    exit();
    echo json_encode(["message" => "Invalid authorization token format. Use: Bearer <token>"]);
    exit();
}

$token = $matches[1];

// Validate token and check if user is admin
$jwt = new JwtHandler($db);
if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid or expired token."]);
    exit();
}

// Check if user is admin
$payload = $jwt->getTokenPayload($token);
if ($payload['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Admin access required."]);
    exit();
}

// Set room properties from request data
$room->hotel_id = $data->hotel_id ?? 0;
$room->type = $data->type ?? '';
$room->price_per_night = $data->price_per_night ?? 0;
$room->capacity = $data->capacity ?? 1;
$room->description = $data->description ?? '';
$room->status = $data->status ?? 'available';

// Validate required fields
if (empty($room->hotel_id) || empty($room->type) || empty($room->price_per_night)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create room. Data is incomplete."]);
    exit();
}

// Create the room
if ($room->create()) {
    http_response_code(201);
    echo json_encode([
        "message" => "Room was created successfully.",
        "room_id" => $room->id
    ]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to create room. Please try again."]);
}
?>
