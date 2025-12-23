<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include_once '../../config/database.php';
// include_once '../../classes/Room.php';
include_once '../../models/Room.php';
include_once '../../helpers/jwt_helper.php';

try {
    // Get JWT from Authorization header
    $headers = getallheaders();
    $jwt = isset($headers['Authorization']) ? trim(str_replace('Bearer ', '', $headers['Authorization'])) : null;
    if (!$jwt) throw new Exception('Access denied: No token provided.');

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->getTokenPayload($jwt);
    if (!$decoded || !is_array($decoded)) {
        throw new Exception('Access denied: Invalid token.');
    }

    // Optional: you can enforce admin/manager role here
    $role = $decoded['role'] ?? null;
    if (!$role || !in_array(strtolower($role), ['admin','manager'])) {
        throw new Exception('Access denied: Insufficient permissions.');
    }

    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);

    // Optional hotel filter via GET
    $hotelId = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : null;
    $rooms = $room->getAll($hotelId);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $rooms]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
