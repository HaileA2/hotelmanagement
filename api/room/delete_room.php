<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files with absolute paths
$root = $_SERVER['DOCUMENT_ROOT'];
include_once $root . '/hotel-management-system/config/database.php';
include_once $root . '/hotel-management-system/models/Room.php';
include_once $root . '/hotel-management-system/helpers/jwt_helper.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => ''
];

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Initialize JWT handler
    $jwtHandler = new JwtHandler($db);

    // Get room ID from URL
    $room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($room_id <= 0) {
        throw new Exception('Invalid room ID');
    }

    // Get JWT token from Authorization header
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? '';
    $token = null;

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    if (!$token) {
        http_response_code(401);
        $response['message'] = 'Authorization token is missing';
        echo json_encode($response);
        exit();
    }

    // Verify token and check if user is admin
    $tokenData = $jwtHandler->getTokenPayload($token);
    if (!$jwtHandler->validateToken($token) || ($tokenData['role'] ?? '') !== 'admin') {
        http_response_code(403);
        $response['message'] = 'Unauthorized. Admin access required.';
        echo json_encode($response);
        exit();
    }

    // Create room object and set ID
    $room = new Room($db);
    $room->id = $room_id;

    // Check if room exists
    if (!$room->readOne()) {
        http_response_code(404);
        $response['message'] = 'Room not found';
        echo json_encode($response);
        exit();
    }

    // Check for existing bookings
    $query = "SELECT COUNT(*) as booking_count FROM bookings WHERE room_id = :room_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['booking_count'] > 0) {
        http_response_code(400);
        $response['message'] = 'Cannot delete room because it has associated bookings. Please cancel or delete the bookings first.';
        $response['booking_count'] = (int)$result['booking_count'];
        echo json_encode($response);
        exit();
    }

    // Delete the room
    if ($room->delete()) {
        http_response_code(200);
        $response['status'] = 'success';
        $response['message'] = 'Room was deleted successfully';
        $response['room_id'] = $room_id;
    } else {
        throw new Exception('Unable to delete room');
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log('Error in delete_room.php: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
?>
