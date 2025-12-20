<?php
// api/booking/update_booking.php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Check if user is admin or the owner of the booking
$user_id = $tokenData['user_id'] ?? 0;
$is_admin = ($tokenData['role'] ?? '') === 'admin';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (!isset($data->booking_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Booking ID is required."]);
    exit();
}

$booking_id = $data->booking_id;

// Create booking object and check ownership
$booking = new Booking($db);
if (!$booking->getById($booking_id)) {
    http_response_code(404);
    echo json_encode(["message" => "Booking not found."]);
    exit();
}

if (!$is_admin && $booking->user_id != $user_id) {
    http_response_code(403);
    echo json_encode(["message" => "You can only update your own bookings."]);
    exit();
}

// Update booking fields if provided
$booking->id = $booking_id;

if (isset($data->room_id)) {
    $booking->room_id = $data->room_id;
}

if (isset($data->check_in)) {
    $booking->check_in = $data->check_in;
}

if (isset($data->check_out)) {
    $booking->check_out = $data->check_out;
}

if (isset($data->guest_count)) {
    $booking->guest_count = $data->guest_count;
}

if (isset($data->special_requests)) {
    $booking->special_requests = $data->special_requests;
}

// Update the booking
if ($booking->update()) {
    http_response_code(200);
    echo json_encode(["message" => "Booking updated successfully."]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to update booking. Room may not be available for the selected dates."]);
}
?>