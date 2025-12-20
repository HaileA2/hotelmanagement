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

// Get the booking details to check ownership
$booking_data = $booking->getById($data->booking_id);

if (!$booking_data) {
    http_response_code(404);
    echo json_encode(["message" => "Booking not found."]);
    exit();
}

$is_admin = ($tokenData['role'] ?? '') === 'admin';
$is_owner = ($booking_data['user_id'] == $tokenData['user_id']);

// Check permissions
if (!$is_admin && !$is_owner) {
    http_response_code(403);
    echo json_encode(["message" => "You don't have permission to update this booking."]);
    exit();
}

// If not admin, only allow cancelling own bookings
if (!$is_admin && $data->status !== 'cancelled') {
    http_response_code(403);
    echo json_encode(["message" => "Only administrators can update booking status."]);
    exit();
}

// Update booking status
$booking->status = $data->status;
if ($booking->updateStatus()) {
    http_response_code(200);
    echo json_encode([
        "message" => "Booking status updated successfully.",
        "booking" => [
            "id" => $booking->id,
            "status" => $booking->status
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Unable to update booking status."]);
}