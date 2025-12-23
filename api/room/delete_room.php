<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);
    $room->id = $roomId;
    if (!$room->exists()) throw new Exception('Room not found.');

    if ($room->delete()) {
        echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
    } else {
        throw new Exception('Failed to delete room.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
