<?php
/** api/hotel/list_hotels.php */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Hotel.php';
include_once '../../helpers/jwt_helper.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// --- START JWT AUTHENTICATION ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Authorization token is missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler();

// Validate token
if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit();
}

// Get payload - We allow ANY valid role (admin, manager, or customer) to list hotels
$tokenData = $jwt->getTokenPayload($token);
// --- END AUTHENTICATION ---

// Initialize Hotel object
$hotel = new Hotel($db);



try {
    // Query hotels
    $stmt = $hotel->read();
    $num = $stmt->rowCount();

    if ($num > 0) {
        $hotels_arr = array();
        $hotels_arr["success"] = true; // Consistent with your list_rooms structure
        $hotels_arr["data"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Robust amenity decoding
            $amenities_data = $row['amenities'];
            $decoded_amenities = json_decode($amenities_data);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded_amenities = $amenities_data ? array_map('trim', explode(',', $amenities_data)) : [];
            }

            $hotel_item = array(
                "id" => $row['id'],
                "name" => $row['name'],
                "location" => $row['location'],
                "description" => $row['description'],
                "amenities" => $decoded_amenities,
                "price_per_night" => $row['price_per_night'] ?? 0,
                "rating" => $row['rating'] ?? 0,
                "created_at" => $row['created_at']
            );

            array_push($hotels_arr["data"], $hotel_item);
        }

        http_response_code(200);
        echo json_encode($hotels_arr);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "No hotels found."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>