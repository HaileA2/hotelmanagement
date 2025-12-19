<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

// Get hotel ID from URL
$hotel_id = isset($_GET['id']) ? $_GET['id'] : die();

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

// Set hotel ID to be deleted
$hotel->id = $hotel_id;

// Delete the hotel
if ($hotel->delete()) {
    http_response_code(200);
    echo json_encode(["message" => "Hotel was deleted."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to delete hotel."]);
}
?>
