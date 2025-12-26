<?php
// api/user/update_user.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../classes/User.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();

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

if (!$jwt->validateToken($token) || !in_array(strtolower($tokenData['role']), ['admin'])) {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized. Admin access required."]);
    exit();
}

// Set user properties
$user = new User($db);
$user->id = $data->id ?? 0;
$user->email = $data->email ?? '';
$user->first_name = $data->first_name ?? '';
$user->last_name = $data->last_name ?? '';
$user->phone = $data->phone ?? null;
$user->profile_image = $data->profile_image ?? null;
$user->role = $data->role ?? '';
$user->status = $data->status ?? 'active';
$user->professional_details = isset($data->professional_details) ? json_encode($data->professional_details) : null;

// If password is provided, hash it
if (!empty($data->password ?? '')) {
    $user->password = password_hash($data->password, PASSWORD_BCRYPT);
} else {
    // If no password, don't update it - load current
    $currentUser = new User($db);
    if ($currentUser->getById($user->id)) {
        $user->password = $currentUser->password;
    }
}

// Validation
if (empty($user->id) || empty($user->email) || empty($user->first_name)) {
    http_response_code(400);
    echo json_encode(["message" => "ID, email, and first name are required."]);
    exit();
}

if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email format."]);
    exit();
}

try {
    if ($user->update()) {
        http_response_code(200);
        echo json_encode(["message" => "User updated successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update user."]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["message" => $e->getMessage()]);
}
?>