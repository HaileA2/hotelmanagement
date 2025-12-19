<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
require_once '../../config/database.php';
require_once '../../models/Room.php';
require_once '../../helpers/jwt_helper.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["message" => "Room ID is required."]);
    exit();
}

// Check if user is authenticated
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
if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid or expired token."]);
    exit();
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create room object
    $room = new Room($db);
    $room->id = $_GET['id'];
    
    // Read the room details
    if ($room->readOne()) {
        // Create array
        $room_arr = [
            "id" => $room->id,
            "hotel_id" => $room->hotel_id,
            "type" => $room->type,
            "price" => $room->price,
            "max_occupancy" => $room->max_occupancy,
            "availability" => $room->availability,
            "description" => $room->description,
            "amenities" => $room->amenities,
            "created_at" => $room->created_at,
            "updated_at" => $room->updated_at
        ];
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Make it json format
        echo json_encode($room_arr);
    } else {
        // Set response code - 404 Not found
        http_response_code(404);
        
        // Tell the user room does not exist
        echo json_encode(["message" => "Room not found."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => $e->getMessage()]);
}
?>
