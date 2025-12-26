<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Hotel.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create hotel object
$hotel = new Hotel($db);

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

// Verify token and check if user is admin or manager
$tokenData = $jwt->getTokenPayload($token);
if (!$jwt->validateToken($token) || !in_array($tokenData['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin or Manager access required."]);
    exit();
}

// Set hotel ID and properties
$hotel->id = $data->id ?? 0;
$hotel->name = $data->name ?? '';
$hotel->location = $data->location ?? '';
$hotel->address = $data->address ?? '';
$hotel->city = $data->city ?? '';
$hotel->country = $data->country ?? '';
$hotel->description = $data->description ?? '';
$hotel->price_per_night = $data->price_per_night ?? 0;
$hotel->rating = $data->rating ?? 0;
$hotel->amenities = isset($data->amenities) ? json_encode($data->amenities) : '[]';

// Validate input
if (empty($hotel->id) || empty($hotel->name)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update hotel. Data is incomplete."]);
    exit();
}

// Update the hotel
if ($hotel->update()) {
    http_response_code(200);
    echo json_encode(["message" => "Hotel was updated."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to update hotel."]);
}
?>
