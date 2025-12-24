<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Hotel.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();
$hotel = new Hotel($db);

$data = json_decode(file_get_contents("php://input"));

// JWT Check
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

$jwt = new JwtHandler();
$token = $matches[1];
$tokenData = $jwt->getTokenPayload($token);

if (!$jwt->validateToken($token) || !in_array($tokenData['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin or Manager access required."]);
    exit();
}

// Set properties
$hotel->name = $data->name ?? '';
$hotel->description = $data->description ?? '';
$hotel->location = $data->location ?? ''; // Simplified location field
$hotel->rating = $data->rating ?? 0;
$hotel->amenities = isset($data->amenities) ? (is_array($data->amenities) ? json_encode($data->amenities) : $data->amenities) : '[]';

// Fixed Validation: matching properties in your Hotel class
if (empty($hotel->name) || empty($hotel->location)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create hotel. Name and Location are required."]);
    exit();
}

if ($hotel->create()) {
    http_response_code(201);
    echo json_encode([
        "message" => "You have successfully created hotel.",
        "hotel_id" => $hotel->id,
    ]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to create hotel. Please try again."]);
}