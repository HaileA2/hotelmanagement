<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database and object files
include_once '../../config/database.php';
include_once '../../models/Amenity.php';

// Instantiate database and amenity object
$database = new Database();
$db = $database->getConnection();

// Initialize object
$amenity = new Amenity($db);

// Query amenities
$stmt = $amenity->read();
$num = $stmt->rowCount();

// Check if more than 0 records found
if($num > 0) {
    // Amenities array
    $amenities_arr = array();
    $amenities_arr["records"] = array();

    // Retrieve table contents
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $amenity_item = array(
            "id" => $id,
            "name" => $name,
            "icon" => $icon,
            "description" => $description,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        );
        
        array_push($amenities_arr["records"], $amenity_item);
    }
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Show amenities data in JSON format
    echo json_encode($amenities_arr);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user no amenities found
    echo json_encode(
        array("message" => "No amenities found.")
    );
}
?>
