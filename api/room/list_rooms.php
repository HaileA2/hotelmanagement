<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and room model
include_once $_SERVER['DOCUMENT_ROOT'] . '/hotel-management-system/config/database.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/hotel-management-system/models/Room.php';

// Create database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize room object
    $room = new Room($db);
    
    // Get hotel ID from query parameter
    $hotel_id = isset($_GET['hotel_id']) ? $_GET['hotel_id'] : null;
    
    if (!$hotel_id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Hotel ID is required."]);
        exit();
    }
    
    // Query rooms by hotel
    $stmt = $room->readByHotel($hotel_id);
    $num = $stmt->rowCount();

    $rooms_arr = [];
    $rooms_arr["status"] = "success";
    $rooms_arr["data"] = [];

    if ($num > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $room_item = [
                "id" => $row['id'] ?? null,
                "hotel_id" => $row['hotel_id'] ?? null,
                "type" => $row['type'] ?? '',
                "price" => isset($row['price']) ? (float)$row['price'] : 0.0,
                "max_occupancy" => $row['max_occupancy'] ?? 1,
                "availability" => $row['availability'] ?? 0,
                "created_at" => $row['created_at'] ?? null,
                "updated_at" => $row['updated_at'] ?? null
            ];

            $rooms_arr["data"][] = $room_item;
        }

        http_response_code(200);
        echo json_encode($rooms_arr);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "No rooms found for this hotel."));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Error retrieving rooms: " . $e->getMessage()));
}
?>
