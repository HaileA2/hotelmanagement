<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Booking.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get JWT token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler();

// Verify token
$tokenData = $jwt->getTokenPayload($token);
if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid or expired token."]);
    exit();
}

// Check if user is admin
$is_admin = ($tokenData['role'] ?? '') === 'admin';
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(["message" => "Admin access required."]);
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (!isset($data->booking_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode(["message" => "Booking ID and status are required."]);
    exit();
}

// Create booking object
$booking = new Booking($db);
$booking->id = $data->booking_id;
$booking->status = $data->status;

// Update booking status
if ($booking->updateStatus()) {
    http_response_code(200);
    echo json_encode(["message" => "Booking status updated."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to update booking status."]);
}
?>
