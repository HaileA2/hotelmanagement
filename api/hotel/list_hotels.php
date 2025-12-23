<?php
/** api/hotel/list_hotels.php */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files
include_once '../../config/database.php';
include_once '../../models/Hotel.php';
include_once '../../helpers/jwt_helper.php'; // Included JWT helper

// Get database connection
$database = new Database();
$db = $database->getConnection();

// --- START JWT AUTHENTICATION SECTION ---
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

// Check if Bearer token is present
if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["message" => "Authorization token is missing."]);
    exit();
}

$token = $matches[1];
$jwt = new JwtHandler();

// Validate token
if (!$jwt->validateToken($token)) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid or expired token."]);
    exit();
}

// Optional: Get payload if you need to filter results by user/role
$tokenData = $jwt->getTokenPayload($token);
// --- END JWT AUTHENTICATION SECTION ---

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

            // Robust amenity decoding (handles JSON strings or plain text)
            $decoded_amenities = json_decode($amenities);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded_amenities = $amenities ? array_map('trim', explode(',', $amenities)) : [];
            }

            $hotel_item = array(
                "id" => $id,
                "name" => $name,
                "location" => $location,
                "description" => $description,
                "amenities" => $decoded_amenities,
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