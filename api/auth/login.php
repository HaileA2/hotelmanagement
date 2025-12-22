<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
include_once '../../config/database.php';
include_once '../../classes/User.php';
include_once '../../helpers/jwt_helper.php';

// Function to log errors
function logError($message) {
    $logDir = __DIR__ . '/../../logs';
    $logFile = $logDir . '/auth_errors.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    // Log to file if possible, otherwise log to PHP error log
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    if (is_writable($logDir)) {
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    } else {
        error_log("Failed to write to log file: $logFile. Message: $message");
    }
}

try {
    // Get posted data
    $json = file_get_contents("php://input");
    $data = json_decode($json);

    // Validate input
    if (empty($data->email) || empty($data->password)) {
        throw new Exception('Email and password are required');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    $user->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    if (!$user->emailExists()) {
        logError("Login attempt with non-existent email: " . $user->email);
        throw new Exception('Invalid email or password');
    }
    
    // Verify password
    if (!password_verify($data->password, $user->password)) {
        logError(sprintf(
            "Failed login attempt for user %s: %s", 
            $user->email,
            "Invalid password"
        ));
        throw new Exception('Invalid email or password');
    }
    
    // Generate JWT token
    $jwt = new JwtHandler();
    $token = $jwt->generateToken($user->id, $user->role);
    
    // Get user details
    $user_data = [
        "id" => $user->id,
        "email" => $user->email,
        "first_name" => $user->first_name,
        "last_name" => $user->last_name,
        "role" => $user->role,
        "created_at" => $user->created_at
    ];
    
    // Log successful login
    logError(sprintf("Successful login for user: %s", $user->email));
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "token" => $token,
        "user" => $user_data
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
