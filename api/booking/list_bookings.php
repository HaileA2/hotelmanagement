<?php
/** api/booking/list_bookings.php */
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

// Check if user is requesting their own bookings or is an admin
$user_id = $tokenData['user_id'] ?? 0;
$is_admin = ($tokenData['role'] ?? '') === 'admin';

// Create booking object
$booking = new Booking($db);

// Get user ID from query parameter if admin, otherwise use token user_id
$requested_user_id = isset($_GET['user_id']) && $is_admin ? $_GET['user_id'] : $user_id;

// Get bookings
if ($is_admin) {
    // Admin gets all bookings with user info
    $stmt = $booking->getAllBookings();
} else {
    // Regular user gets their own bookings
    $stmt = $booking->getByUserId($requested_user_id);
}
$num = $stmt->rowCount();

// Check if any bookings found
$bookings_arr = [];
$bookings_arr["data"] = [];

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $booking_item = [
            "id" => $id,
            "user_id" => $user_id,
            "room_id" => $room_id,
            "hotel_id" => $hotel_id,
            "checkInDate" => $check_in,
            "checkOutDate" => $check_out,
            "room_price" => $room_price,
            "hotel_name" => $hotel_name,
            "room_type" => $room_type,
            "roomNumber" => $room_id, // Using room_id as room number for now
            "status" => $status,
            "guest_count" => $guest_count ?? null,
            "special_requests" => $special_requests ?? null,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        ];

        // Add guest info if available (for admin)
        if (isset($guest_email)) {
            $booking_item["guestName"] = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
            $booking_item["guestEmail"] = $guest_email;
        }

        array_push($bookings_arr["data"], $booking_item);
    }
}

http_response_code(200);
echo json_encode($bookings_arr);
?>