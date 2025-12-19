<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

// Get ID of amenity to be deleted
$data = json_decode(file_get_contents("php://input"));

// Check if ID is set
if(!empty($data->id)) {
    // Set ID property of amenity to be deleted
    $amenity->id = $data->id;
    
    // Get amenity before delete for audit log
    $amenity->readOne();
    $deleted_amenity = array(
        'id' => $amenity->id,
        'name' => $amenity->name,
        'icon' => $amenity->icon,
        'description' => $amenity->description
    );
    
    // Delete the amenity
    if($amenity->delete()) {
        // Log the action
        $auditLog->logAction($db, $data->user_id ?? 0, 'DELETE', 'amenities', $deleted_amenity['id'], $deleted_amenity, null);
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Amenity was deleted."
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to delete amenity. It may be in use by hotels."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to delete amenity. ID is required."
    ));
}
?>
