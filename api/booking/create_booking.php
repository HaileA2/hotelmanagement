<?php
/** api/booking/create_booking.php */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../config/api_keys.php';
include_once '../../classes/User.php';
include_once '../../models/Booking.php';
include_once '../../models/Room.php';
include_once '../../helpers/jwt_helper.php';

// 1. Initialize Database & Connection
$database = new Database();
$db = $database->getConnection();

// 2. Check for API-KEY or JWT token
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$apiKey = $headers['x-api-key'] ?? null;
$authHeader = $headers['authorization'] ?? null;

$isApiKeyAuth = false;
$user_id = null;

if ($apiKey) {
    // API-KEY authentication for external services
    if (validateApiKey($apiKey) && apiKeyHasPermission($apiKey, 'create_booking')) {
        $isApiKeyAuth = true;
        // For API-KEY bookings, use the system customer account
        $systemCustomer = new User($db);
        if ($systemCustomer->emailExists('api-customer@hotelhub.com')) {
            $user_id = $systemCustomer->id;
        } else {
            // Fallback to ID 1 if system customer not found
            $user_id = 1;
        }
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid API key or insufficient permissions."]);
        exit();
    }
} elseif ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    // JWT authentication for regular users
    $token = $matches[1];

    // 3. Initialize JwtHandler with DB
    $jwt = new JwtHandler($db);
    $payload = $jwt->getTokenPayload($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
        exit();
    }

    // Extract user info from payload
    $user_id = $payload['user_id'];
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Access denied. Authorization token or API key missing."]);
    exit();
}

// 4. Get Posted Data
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->room_id) &&
    !empty($data->hotel_id) &&
    !empty($data->check_in) &&
    !empty($data->check_out) &&
    !empty($data->guest_count) &&
    !empty($data->total_price)
) {
    $booking = new Booking($db);
    $room = new Room($db);

    // 5. Final Server-Side Availability Check
    $check_in = date('Y-m-d', strtotime($data->check_in));
    $check_out = date('Y-m-d', strtotime($data->check_out));

    // Prevent double-booking (Requires checkAvailability in Booking.php)
    if (!$booking->checkAvailability($data->room_id, $check_in, $check_out)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Room is no longer available for these dates."]);
        exit();
    }

    // 6. Map Data to Booking Object
    $booking->user_id = $user_id;
    $booking->hotel_id = $data->hotel_id;
    $booking->room_id = $data->room_id;
    $booking->check_in = $check_in;
    $booking->check_out = $check_out;
    $booking->guest_count = $data->guest_count;
    $booking->total_price = $data->total_price;
    $booking->special_requests = $data->special_requests ?? '';
    $booking->status = 'confirmed';

    

    // 7. Create the Booking (local-only)
    if ($booking->create()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Booking created successfully.",
            "booking_id" => $booking->id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Unable to save booking to database."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete booking data provided."]);
}
?>