<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Room.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create room object
$room = new Room($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if user is admin
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler();

// Verify token and check if user is admin
$tokenData = $jwt->getTokenPayload($token);
if (!$jwt->validateToken($token) || $tokenData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin access required."]);
    exit();
}

// Set room properties
$room->id = $data->id ?? 0;
$room->hotel_id = $data->hotel_id ?? 0;
$room->type = $data->type ?? '';
$room->price_per_night = $data->price_per_night ?? 0;
$room->capacity = $data->capacity ?? 1;
$room->description = $data->description ?? '';
$room->status = $data->status ?? 'available';

// Validate input
if (empty($room->id) || empty($room->hotel_id) || empty($room->type) || $room->price_per_night <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update room. Data is incomplete or invalid."]);
    exit();
}

// Update the room
if ($room->update()) {
    http_response_code(200);
    echo json_encode(["message" => "Room was updated."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to update room."]);
}
?>
