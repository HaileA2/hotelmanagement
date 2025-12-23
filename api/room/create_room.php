<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../classes/Room.php';
include_once '../../helpers/jwt_helper.php';

try {
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? trim(str_replace('Bearer ', '', $headers['Authorization'])) : null;
    if (!$jwt) throw new Exception('Access denied: No token provided.');

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->getTokenPayload($jwt); // <-- use this
    if (!$decoded || !is_array($decoded)) {
        throw new Exception('Access denied: Invalid token.');
    }

    $role = $decoded['role'] ?? null;
    if (!$role || !in_array(strtolower($role), ['admin','manager'])) {
        throw new Exception('Access denied: Insufficient permissions.');
    }

    $data = json_decode(file_get_contents("php://input"));
    if (
        !$data->hotel_id ||
        !$data->room_number ||
        !$data->type ||
        !$data->price_per_night ||
        !$data->capacity
    ) {
        throw new Exception('All room fields are required.');
    }

    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);

    $room->hotel_id = $data->hotel_id;
    $room->room_number = $data->room_number;
    $room->type = $data->type;
    $room->price_per_night = $data->price_per_night;
    $room->capacity = $data->capacity;
    $room->status = $data->status ?? 'Available';

    if ($room->create()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Room added successfully.',
            'room_id' => $room->id
        ]);
    } else {
        throw new Exception('Failed to add room.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
