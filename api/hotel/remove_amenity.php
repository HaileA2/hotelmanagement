<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../../config/database.php';
include_once '../../models/HotelAmenity.php';
include_once '../../models/AuditLog.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$hotelAmenity = new HotelAmenity($db);
$auditLog = new AuditLog($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is not empty
if (!empty($data->hotel_id) && !empty($data->amenity_id)) {
    // Set hotel amenity property values
    $hotelAmenity->hotel_id = $data->hotel_id;
    $hotelAmenity->amenity_id = $data->amenity_id;
    
    // Get the record before deleting for audit log
    $hotelAmenity->readOne();
    $deleted_record = array(
        'hotel_id' => $hotelAmenity->hotel_id,
        'amenity_id' => $hotelAmenity->amenity_id,
        'is_available' => $hotelAmenity->is_available,
        'additional_charge' => $hotelAmenity->additional_charge,
        'details' => $hotelAmenity->details
    );
    
    // Delete the hotel-amenity relationship
    if ($hotelAmenity->delete()) {
        // Log the action
        $auditLog->logAction($db, $data->user_id ?? 0, 'REMOVE_AMENITY', 'hotel_amenities', $hotelAmenity->id, $deleted_record, null);
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Amenity was removed from the hotel."
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to remove amenity from the hotel."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to remove amenity from the hotel. Data is incomplete."
    ));
}
?>
