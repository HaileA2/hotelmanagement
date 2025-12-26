<?php
// api/auth/customer_registration.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

// Force role to customer regardless of what client sends
$user->role = 'customer';
$user->email = $data->email ?? '';
$user->first_name = $data->first_name ?? '';
$user->last_name = $data->last_name ?? '';
$user->password = $data->password ?? '';
$user->status = 'active'; // Default status for customers

// Validation
if (empty($user->email) || empty($user->password) || empty($user->first_name)) {
    http_response_code(400);
    echo json_encode(["message" => "Email, password, and first name are required."]);
    exit();
}

if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email format."]);
    exit();
}

try {
    if ($user->create()) {
        http_response_code(201);
        echo json_encode([
            "message" => "Customer account created successfully.",
            "user_id" => $user->id,
            "email" => $user->email,
            "role" => $user->role
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create customer account."]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["message" => $e->getMessage()]);
}
?>