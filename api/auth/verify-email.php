<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
require_once '../../config/database.php';
require_once '../../classes/User.php';

// Function to send JSON response
function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    $response = ['message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

try {
    // Check if token is provided
    if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
        sendResponse(400, 'Verification token is required.');
    }

    $verification_token = trim($_GET['token']);
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify the token and update user's email verification status
    $user = new User($db);
    
    if ($user->verifyEmail($verification_token)) {
        // Email verified successfully
        sendResponse(200, 'Email verified successfully. You can now log in.');
    } else {
        // Invalid or expired token
        sendResponse(400, 'Invalid or expired verification token.');
    }
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log('Email verification error: ' . $e->getMessage());
    
    // Send a generic error message to the client
    sendResponse(500, 'An error occurred during email verification. Please try again.');
}
?>
