<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();
$jwtHelper = new JwtHandler();

// 1. Get Token from Header
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$token = isset($headers['authorization']) ? str_replace('Bearer ', '', $headers['authorization']) : null;

// 2. Validate Admin Access
$payload = $jwtHelper->getTokenPayload($token);
if (!$payload || strtolower($payload['role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Access denied. Only admins can update roles."]);
    exit();
}

// 3. Get Request Data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->role)) {
    $user = new User($db);
    $user->id = $data->user_id;
    $newRole = strtolower($data->role);

    // Validate allowed roles
    if (!in_array($newRole, ['customer', 'manager', 'admin'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid role type."]);
        exit();
    }

    if ($user->updateRole($newRole)) {
        http_response_code(200);
        echo json_encode(["message" => "User role updated successfully to " . $newRole]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update user role."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Provide user_id and role."]);
}