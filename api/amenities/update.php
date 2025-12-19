<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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

// Get ID of amenity to be edited
$data = json_decode(file_get_contents("php://input"));

// Check if ID is set
if(!empty($data->id)) {
    // Set ID property of amenity to be edited
    $amenity->id = $data->id;
    
    // Get amenity before update for audit log
    $amenity->readOne();
    $old_amenity = array(
        'name' => $amenity->name,
        'icon' => $amenity->icon,
        'description' => $amenity->description
    );
    
    // Set amenity property values
    $amenity->name = !empty($data->name) ? $data->name : $amenity->name;
    $amenity->icon = isset($data->icon) ? $data->icon : $amenity->icon;
    $amenity->description = isset($data->description) ? $data->description : $amenity->description;
    
    // Update the amenity
    if($amenity->update()) {
        // Log the action
        $auditLog->logAction($db, $data->user_id ?? 0, 'UPDATE', 'amenities', $amenity->id, $old_amenity, array(
            'name' => $amenity->name,
            'icon' => $amenity->icon,
            'description' => $amenity->description
        ));
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Amenity was updated."
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to update amenity."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to update amenity. ID is required."
    ));
}
?>
