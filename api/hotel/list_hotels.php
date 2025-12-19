<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and hotel model
include_once '../../config/database.php';
include_once '../../models/Hotel.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize hotel object
$hotel = new Hotel($db);

try {
    // Query hotels
    $stmt = $hotel->read();
    $num = $stmt->rowCount();

    $hotels_arr = array();
    $hotels_arr["data"] = array();

    if ($num > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $hotel_item = array(
                "id" => $id,
                "name" => $name,
                "location" => $location,
                "description" => $description,
                "amenities" => json_decode($amenities),
                "created_at" => $created_at
            );

            array_push($hotels_arr["data"], $hotel_item);
        }

        http_response_code(200);
        echo json_encode($hotels_arr);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "No hotels found."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving hotels: " . $e->getMessage()));
}
?>
