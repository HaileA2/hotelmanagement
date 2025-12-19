// api/user/update_profile.php
<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
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
    
    $user_id = $payload['user_id'];
    $data = json_decode(file_get_contents("php://input"));
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Initialize user object
    $user = new User($db);
    $user->id = $user_id;
    
    // Update user fields if provided
    $update_fields = [];
    
    if (isset($data->email)) {
        $user->email = $data->email;
        $update_fields[] = 'email';
    }
    
    if (isset($data->password) && !empty(trim($data->password))) {
        // Hash the password before saving
        $user->password = password_hash($data->password, PASSWORD_BCRYPT);
        $update_fields[] = 'password';
    }
    
    if (isset($data->role)) {
        $user->role = $data->role;
        $update_fields[] = 'role';
    }
    
    if (isset($data->details)) {
        if (is_string($data->details)) {
            $user->details = $data->details;
        } else {
            $user->details = json_encode($data->details);
        }
        $update_fields[] = 'details';
    }
    
    if (empty($update_fields)) {
        throw new Exception('No fields to update');
    }
    
    // Update the user
    if ($user->update()) {
        // Fetch updated user data
        if ($user->readOne()) {
            $user_data = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'details' => json_decode($user->details ?? '{}', true)
            ];
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Profile updated successfully.",
                "updated_fields" => $update_fields,
                "user" => $user_data
            ]);
        } else {
            throw new Exception('Profile updated but could not fetch updated data');
        }
    } else {
        throw new Exception('Unable to update profile. Please try again.');
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