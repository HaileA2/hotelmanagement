<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include required files with absolute paths
$root = $_SERVER['DOCUMENT_ROOT'];
include_once $root . '/hotel-management-system/config/database.php';
include_once $root . '/hotel-management-system/helpers/jwt_helper.php';

// Create database connection
try {
    $database = new Database();
    $db = $database->getConnection();

    // Get query parameters
    $check_in = isset($_GET['check_in']) ? $_GET['check_in'] : null;
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : null;
$hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;
$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : null;
$adults = isset($_GET['adults']) ? (int)$_GET['adults'] : 1;
$children = isset($_GET['children']) ? (int)$_GET['children'] : 0;

// Validate required parameters
if (!$check_in || !$check_out) {
    http_response_code(400);
    echo json_encode(["message" => "Check-in and check-out dates are required."]);
    exit();
}

try {
    // Convert dates to proper format
    $check_in_date = date('Y-m-d', strtotime($check_in));
    $check_out_date = date('Y-m-d', strtotime($check_out));
    
    // Validate dates
    if ($check_in_date < date('Y-m-d')) {
        http_response_code(400);
        echo json_encode(["message" => "Check-in date cannot be in the past."]);
        exit();
    }
    
    if ($check_out_date <= $check_in_date) {
        http_response_code(400);
        echo json_encode(["message" => "Check-out date must be after check-in date."]);
        exit();
    }
    
    // Build the base query
    $query = "
        SELECT 
            r.*,
            rt.name as room_type_name,
            rt.base_price,
            rt.max_occupancy,
            (SELECT image_url FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image,
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.room_id = r.id 
             AND b.status IN ('confirmed', 'checked_in')
             AND (
                 (b.check_in <= :check_out AND b.check_out >= :check_in)
             )) as booking_count
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status = 'available'
        AND rt.max_occupancy >= :total_guests
    ";
    
    $params = [
        ':check_in' => $check_in_date,
        ':check_out' => $check_out_date,
        ':total_guests' => $adults + $children
    ];
    
    // Add filters
    if ($hotel_id) {
        $query .= " AND r.hotel_id = :hotel_id";
        $params[':hotel_id'] = $hotel_id;
    }
    
    if ($room_type_id) {
        $query .= " AND r.room_type_id = :room_type_id";
        $params[':room_type_id'] = $room_type_id;
    }
    
    // Only include rooms that are not booked for the given dates
    $query .= " HAVING booking_count = 0";
    
    // Prepare and execute the query
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $available_rooms = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate total price for the stay
        $nights = (strtotime($check_out_date) - strtotime($check_in_date)) / (60 * 60 * 24);
        $total_price = $row['base_price'] * $nights;
        
        // Get all images for the room
        $imageStmt = $db->prepare("SELECT * FROM room_images WHERE room_id = :room_id ORDER BY is_primary DESC");
        $imageStmt->execute([':room_type_id' => $row['room_type_id']]);
        $images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get room type details
        $typeStmt = $db->prepare("SELECT * FROM room_types WHERE id = :room_type_id");
        $typeStmt->execute([':room_type_id' => $row['room_type_id']]);
        $room_type = $typeStmt->fetch(PDO::FETCH_ASSOC);
        
        $available_rooms[] = [
            'id' => $row['id'],
            'room_number' => $row['room_number'],
            'room_type' => $room_type,
            'hotel_id' => $row['hotel_id'],
            'price_per_night' => $row['base_price'],
            'total_price' => $total_price,
            'max_occupancy' => $row['max_occupancy'],
            'images' => $images,
            'amenities' => json_decode($row['amenities'], true) ?: [],
            'check_in' => $check_in_date,
            'check_out' => $check_out_date,
            'nights' => $nights
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'check_in' => $check_in_date,
            'check_out' => $check_out_date,
            'available_rooms' => $available_rooms
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error checking room availability: ' . $e->getMessage()
    ]);
    exit();
}
?>
<?php
// Close the try block at the end of file
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit();
}
