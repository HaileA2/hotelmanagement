<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../helpers/jwt_helper.php';

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Only allow GET requests
if ($method !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

// Get the Authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : null;

// Check if token exists
if (!$authHeader) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        "valid" => false,
        "message" => "Access denied. No token provided."
    ]);
    exit();
}

// Extract the token from the header
$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        "valid" => false,
        "message" => "Invalid token format"
    ]);
    exit();
}

try {
    // Initialize JWT handler
    $jwt = new JwtHandler();
    
    // Verify the token
    $decoded = $jwt->validateToken($token);
    
    if ($decoded) {
        // Token is valid
        http_response_code(200);
        echo json_encode([
            "valid" => true,
            "user_id" => $decoded->user_id,
            "role" => $decoded->role,
            "message" => "Token is valid"
        ]);
    } else {
        // Token is invalid
        http_response_code(401);
        echo json_encode([
            "valid" => false,
            "message" => "Invalid or expired token"
        ]);
    }
} catch (Exception $e) {
    // Handle any exceptions
    http_response_code(500);
    echo json_encode([
        "valid" => false,
        "message" => "Token validation failed: " . $e->getMessage()
    ]);
}
?>
