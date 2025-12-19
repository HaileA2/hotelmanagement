<?php
// api/user/update_role.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../classes/User.php';
include_once '../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();
$jwt = getBearerToken();

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    if ($payload['role'] !== 'Admin') {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }

    if (!isset($data->user_id) || !isset($data->new_role)) {
        http_response_code(400);
        echo json_encode(["message" => "User ID and new role are required."]);
        exit();
    }

    if (!in_array($data->new_role, ['Admin', 'Manager', 'Customer'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid role."]);
        exit();
    }

    $user = new User($db);
    $user->id = $data->user_id;
    
    if ($user->updateRole($data->new_role)) {
        http_response_code(200);
        echo json_encode(["message" => "User role updated successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update user role."]);
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid token"]);
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>