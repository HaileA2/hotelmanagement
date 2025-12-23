<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../classes/Room.php';
include_once '../../helpers/jwt_helper.php';

try {
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? trim(str_replace('Bearer ', '', $headers['Authorization'])) : null;
    if (!$jwt) throw new Exception('Access denied: No token provided.');

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->getTokenPayload($jwt);
    if (!$decoded) throw new Exception('Access denied: Invalid token.');

    $role = $decoded['role'] ?? null;
    if (!$role || !in_array(strtolower($role), ['admin','manager'])) {
        throw new Exception('Access denied: Insufficient permissions.');
    }

    $roomId = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$roomId) throw new Exception('Room ID is required.');

    $data = json_decode(file_get_contents("php://input"));
    if (!$data->hotel_id || !$data->type || !$data->price_per_night || !$data->capacity) {
        throw new Exception('All required room fields must be provided.');
    }

    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);
    $room->id = $roomId;
    if (!$room->exists()) throw new Exception('Room not found.');

    $room->hotel_id = $data->hotel_id;
    $room->room_number = $data->room_number ?? '';
    $room->type = $data->type;
    $room->price_per_night = $data->price_per_night;
    $room->capacity = $data->capacity;
    $room->status = $data->status ?? 'Available';
    $room->description = $data->description ?? '';

    if ($room->update()) {
        echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
    } else {
        throw new Exception('Failed to update room.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
