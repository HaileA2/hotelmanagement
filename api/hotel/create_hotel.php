<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

// Verify token and check if user is admin
$tokenData = $jwt->getTokenPayload($token);
if (!$jwt->validateToken($token) || $tokenData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin access required."]);
    exit();
}

// Set hotel properties
$hotel->name = $data->name ?? '';
$hotel->description = $data->description ?? '';
$hotel->address = $data->address ?? '';
$hotel->city = $data->city ?? '';
$hotel->country = $data->country ?? '';
$hotel->rating = $data->rating ?? 0;
$hotel->amenities = isset($data->amenities) ? json_encode($data->amenities) : '[]';

// Validate input
if (empty($hotel->name) || empty($hotel->address) || empty($hotel->city) || empty($hotel->country)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create hotel.all feild are must filled"]);
    exit();
}

// Create the hotel
if ($hotel->create()) {
    http_response_code(201);
    echo json_encode([
        "message" => "You have successfully created hotel.",
        "hotel_id" => $hotel->id,
    ]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to create hotel Please try again."]);
}
?>
