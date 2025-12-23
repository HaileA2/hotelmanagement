<?php
// api/hotel/hotel_details.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../classes/Room.php';

try {
    if (!isset($_GET['hotel_id'])) {
        throw new Exception('hotel_id is required');
    }

    $hotelId = intval($_GET['hotel_id']);

    $database = new Database();
    $db = $database->getConnection();

    // Fetch hotel info
    $stmt = $db->prepare("SELECT id, name, location, description, amenities, price, rating, created_at FROM hotels WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $hotelId, PDO::PARAM_INT);
    $stmt->execute();
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        throw new Exception('Hotel not found');
    }

    // Try decode amenities if stored as JSON
    if (isset($hotel['amenities']) && $hotel['amenities']) {
        $a = json_decode($hotel['amenities'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $hotel['amenities'] = $a;
        } else {
            // split by comma fallback
            $hotel['amenities'] = array_map('trim', explode(',', $hotel['amenities']));
        }
    } else {
        $hotel['amenities'] = [];
    }

    // Get rooms for hotel
    $roomObj = new Room($db);
    $rooms = $roomObj->getAll($hotelId); // returns array

    // Return consistent data
    echo json_encode(['success' => true, 'data' => [
        'hotel' => $hotel,
        'rooms' => $rooms
    ]]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
