<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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
if (!empty($data->id)) {
    // Set ID property of hotel amenity to be updated
    $hotelAmenity->id = $data->id;
    
    // Get the record before updating for audit log
    $hotelAmenity->readOne();
    $old_record = array(
        'hotel_id' => $hotelAmenity->hotel_id,
        'amenity_id' => $hotelAmenity->amenity_id,
        'is_available' => $hotelAmenity->is_available,
        'additional_charge' => $hotelAmenity->additional_charge,
        'details' => $hotelAmenity->details
    );
    
    // Set property values
    if (isset($data->is_available)) {
        $hotelAmenity->is_available = $data->is_available;
    }
    
    if (isset($data->additional_charge)) {
        $hotelAmenity->additional_charge = $data->additional_charge;
    }
    
    if (isset($data->details)) {
        $hotelAmenity->details = $data->details;
    }
    
    // Update the hotel amenity
    if ($hotelAmenity->update()) {
        // Get the updated record for audit log
        $hotelAmenity->readOne();
        $new_record = array(
            'hotel_id' => $hotelAmenity->hotel_id,
            'amenity_id' => $hotelAmenity->amenity_id,
            'is_available' => $hotelAmenity->is_available,
            'additional_charge' => $hotelAmenity->additional_charge,
            'details' => $hotelAmenity->details
        );
        
        // Log the action
        $auditLog->logAction($db, $data->user_id ?? 0, 'UPDATE_AMENITY', 'hotel_amenities', $hotelAmenity->id, $old_record, $new_record);
        
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Hotel amenity was updated."
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to update hotel amenity."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to update hotel amenity. ID is required."
    ));
}
?>
