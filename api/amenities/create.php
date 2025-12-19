<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../../config/database.php';
include_once '../../models/Amenity.php';
include_once '../../models/AuditLog.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$amenity = new Amenity($db);
$auditLog = new AuditLog($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is not empty
if (
    !empty($data->name) 
) {
    // Set amenity property values
    $amenity->name = $data->name;
    $amenity->icon = $data->icon ?? null;
    $amenity->description = $data->description ?? null;

    // Create the amenity
    if ($amenity->create()) {
        // Log the action
        $auditLog->logAction($db, $data->user_id ?? 0, 'CREATE', 'amenities', $amenity->id, null, $data);
        
        // Set response code - 201 created
        http_response_code(201);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Amenity was created.",
            "id" => $amenity->id
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to create amenity."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to create amenity. Data is incomplete."
    ));
}
?>
