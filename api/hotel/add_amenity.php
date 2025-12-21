<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../../config/database.php';
include_once '../../models/HotelAmenity.php';
include_once '../../models/Amenity.php';
include_once '../../models/AuditLog.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$hotelAmenity = new HotelAmenity($db);
$amenity = new Amenity($db);
$auditLog = new AuditLog($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if data is not empty
if (!empty($data->hotel_id) && !empty($data->amenity_id)) {
    // Check if amenity exists
    $amenity->id = $data->amenity_id;
    if (!$amenity->readOne()) {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "Amenity not found."
        ));
        exit();
    }

    // Set hotel amenity property values
    $hotelAmenity->hotel_id = $data->hotel_id;
    $hotelAmenity->amenity_id = $data->amenity_id;

    // Check if the amenity is already added to the hotel
    if ($hotelAmenity->exists()) {
        // Set response code - 409 Conflict
        http_response_code(409);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "This amenity is already added to the hotel."
        ));
        exit();
    }
    
    // Create the hotel-amenity relationship
    if ($hotelAmenity->create()) {
        // Log the action (commented out due to audit table issues)
        // $auditLog->logAction($db, $data->user_id ?? 0, 'ADD_AMENITY', 'hotel_amenities', $hotelAmenity->id, null, array(
        //     'hotel_id' => $hotelAmenity->hotel_id,
        //     'amenity_id' => $hotelAmenity->amenity_id
        // ));
        
        // Set response code - 201 created
        http_response_code(201);
        
        // Tell the user
        echo json_encode(array(
            "status" => "success",
            "message" => "Amenity was added to the hotel.",
            "id" => $hotelAmenity->id
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to add amenity to the hotel."
        ));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to add amenity to the hotel. Data is incomplete."
    ));
}
?>
