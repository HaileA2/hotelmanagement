<?php
/** api/hotel/hotel_details.php */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Hotel.php';
include_once '../../models/Room.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

// --- START JWT AUTHENTICATION ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Authorization token is missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler();

if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit();
}
// --- END AUTHENTICATION ---

// Get Parameters
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : null;
$check_in = $_GET['check_in'] ?? null;
$check_out = $_GET['check_out'] ?? null;
$guests = $_GET['guests'] ?? null;

if (!$hotel_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Hotel ID is required."]);
    exit();
}

$hotel = new Hotel($db);
$room = new Room($db);

try {
    // 1. Fetch Hotel Details (Using the updated readOne that returns an array)
    $hotel_data = $hotel->readOne($hotel_id);

    if (!$hotel_data) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Hotel not found."]);
        exit();
    }

    // 2. Fetch Rooms (Filtered by availability if dates are provided)
    if ($check_in && $check_out) {
        $rooms_data = $room->getAvailableRooms($hotel_id, $check_in, $check_out, $guests);
    } else {
        $rooms_data = $room->getAll($hotel_id);
    }

    // 3. Decode Amenities
    $amenities_raw = $hotel_data['amenities'] ?? '';
    $decoded_amenities = json_decode($amenities_raw);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $decoded_amenities = $amenities_raw ? array_map('trim', explode(',', $amenities_raw)) : [];
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "hotel" => [
                "id" => $hotel_data['id'],
                "name" => $hotel_data['name'],
                "location" => $hotel_data['location'],
                "description" => $hotel_data['description'],
                "amenities" => $decoded_amenities,
                "image_url" => $hotel_data['image_url'] ?? null
            ],
            "rooms" => $rooms_data
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>