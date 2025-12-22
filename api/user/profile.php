<?php
// api/user/profile.php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the document root and include required files
$root = $_SERVER['DOCUMENT_ROOT'] . '/hotel-management-system';
require_once $root . '/config/database.php';
require_once $root . '/classes/User.php';
require_once $root . '/helpers/jwt_helper.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Get JWT token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = null;

// Extract the token from the Authorization header
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

try {
    if (empty($token)) {
        throw new Exception('No authentication token provided');
    }

    // Initialize JWT handler with database connection
    $jwt = new JwtHandler($db);
    
    // First validate the token
    if (!$jwt->validateToken($token)) {
        throw new Exception('Invalid or expired token. Please log in again.');
    }
    
    // Get the token payload
    $payload = $jwt->getTokenPayload($token);
    if (!$payload || !isset($payload['user_id'])) {
        throw new Exception('Invalid token data');
    }
    
    $current_user_id = $payload['user_id'];
    $current_user_role = $payload['role'] ?? '';

    // Check if requesting another user's profile
    $requested_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    if ($requested_user_id && $requested_user_id !== $current_user_id) {
        // Check if current user has permission to view other profiles
        if (!in_array(strtolower($current_user_role), ['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Insufficient permissions. Admin or Manager access required to view other user profiles."
            ]);
            exit();
        }
        $user_id = $requested_user_id;
    } else {
        $user_id = $current_user_id;
    }

    // Initialize user object
    $user = new User($db);
    $user->id = $user_id;

    // Fetch user data
    if ($user->getById($user_id)) {
        $user_data = [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role,
            'professional_details' => json_decode($user->professional_details ?? '{}', true),
            'created_at' => $user->created_at
        ];
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Profile retrieved successfully.",
            "user" => $user_data
        ]);
    } else {
        throw new Exception('Unable to retrieve profile.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "token_received" => !empty($token)
    ]);
}
?>