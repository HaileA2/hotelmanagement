<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../classes/User.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get token from header
//authMiddleware($db);

// Get token from header
$headers = apache_request_headers();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

try {
    // Here you could add the token to a blacklist in the database
    // For now, we'll just return success since the client will clear the token
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Tell the user logout was successful
    echo json_encode(array(
        "status" => "success",
        "message" => "Successfully logged out"
    ));
    
} catch (Exception $e) {
    // Set response code - 500 Internal Server Error
    http_response_code(500);
    
    // Tell the user something went wrong
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
}
?>
