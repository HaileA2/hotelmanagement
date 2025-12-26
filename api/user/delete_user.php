<?php
// api/user/delete_user.php

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files with absolute paths
$root = $_SERVER['DOCUMENT_ROOT'];
include_once $root . '/hotel-management-system/config/database.php';
include_once $root . '/hotel-management-system/classes/User.php';
include_once $root . '/hotel-management-system/helpers/jwt_helper.php';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection error",
        "error" => $e->getMessage()
    ]);
    exit();
}

// Get JWT token from Authorization header
$headers = apache_request_headers();
$jwt = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
    }
}

if (!$jwt) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Access denied. No authentication token provided."
    ]);
    exit();
}

try {
    // Verify JWT token
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Access denied. Invalid or expired token."
        ]);
        exit();
    }
    
    // Check if user has admin role
    if (strtolower($payload['role']) !== 'admin') {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Insufficient permissions. Admin access required."
        ]);
        exit();
    }

    // Get request data
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate input
    if (empty($data) || !isset($data->id) || !is_numeric($data->id)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid request. User ID is required and must be a number."
        ]);
        exit();
    }

    // Check if trying to delete self
    if ($payload['user_id'] == $data->id) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Cannot delete your own account."
        ]);
        exit();
    }

    // Initialize and delete user
    $user = new User($db);
    $user->id = $data->id;

    if ($user->delete()) {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "User was deleted successfully.",
            "user_id" => $data->id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Unable to delete user. The user may not exist or there was an error.",
            "user_id" => $data->id
        ]);
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