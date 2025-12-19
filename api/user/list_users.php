<?php
// api/user/list_users.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files using absolute paths
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../../classes/User.php';
include_once __DIR__ . '/../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get headers (case-insensitive)
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

// Check if Authorization header exists
if (!isset($headers['authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

// Extract the token
$authHeader = $headers['authorization'];
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid authorization token format. Use: Bearer <token>"]);
    exit();
}

$token = $matches[1];

try {
    // Initialize JWT handler
    $jwt = new JwtHandler($db);
    
    // First validate the token
    if (!$jwt->validateToken($token)) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid or expired token."]);
        exit();
    }

    // Get and verify the token payload
    $payload = $jwt->getTokenPayload($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid token payload."]);
        exit();
    }
    
    // Log the payload for debugging (remove in production)
    error_log("Token payload: " . print_r($payload, true));
    
    // Check if user has admin role (case-insensitive check)
    if (strtolower($payload['role']) !== 'admin') {
        http_response_code(403);
        echo json_encode([
            "message" => "Insufficient permissions. Admin access required.",
            "current_role" => $payload['role']
        ]);
        exit();
    }

    // Get users
    $user = new User($db);
    $stmt = $user->read();
    
    if (!$stmt) {
        throw new Exception("Failed to fetch users.");
    }
    
    $users_arr = array();
    $users_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_item = array(
            "id" => $row['id'] ?? null,
            "email" => $row['email'] ?? null,
            "first_name" => $row['first_name'] ?? null,
            "last_name" => $row['last_name'] ?? null,
            "role" => $row['role'] ?? null,
            "created_at" => $row['created_at'] ?? null
        );
        array_push($users_arr["records"], $user_item);
    }

    http_response_code(200);
    echo json_encode($users_arr);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Error: " . $e->getMessage(),
        "error" => $e->getTraceAsString()
    ]);
}
?>