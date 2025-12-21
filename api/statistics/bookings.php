<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../helpers/jwt_helper.php';

$database = new Database();
$db = $database->getConnection();
$jwt = getBearerToken();

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit();
}

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    if (!in_array($payload['role'], ['Admin', 'Manager'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }

    // Get parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
    $hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;
    $room_type = isset($_GET['room_type']) ? $_GET['room_type'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;

    // Build query
    $query = "SELECT 
                b.id,
                u.email as customer_email,
                h.name as hotel_name,
                r.type as room_type,
                b.check_in,
                b.check_out,
                b.status,
                b.created_at,
                r.price as room_price,
                DATEDIFF(b.check_out, b.check_in) as nights,
                (DATEDIFF(b.check_out, b.check_in) * r.price) as total_amount
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN rooms r ON b.room_id = r.id
              JOIN hotels h ON r.hotel_id = h.id
              WHERE b.created_at BETWEEN :start_date AND :end_date";
    
    $params = [
        ':start_date' => $start_date . ' 00:00:00',
        ':end_date' => $end_date . ' 23:59:59'
    ];

    if ($hotel_id) {
        $query .= " AND h.id = :hotel_id";
        $params[':hotel_id'] = $hotel_id;
    }

    if ($room_type) {
        $query .= " AND r.type = :room_type";
        $params[':room_type'] = $room_type;
    }

    if ($status) {
        $query .= " AND b.status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY b.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $bookings = array();
    $bookings["period"] = array(
        "start_date" => $start_date,
        "end_date" => $end_date
    );
    $bookings["data"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($bookings["data"], array(
            "id" => $row['id'],
            "customer_email" => $row['customer_email'],
            "hotel_name" => $row['hotel_name'],
            "room_type" => $row['room_type'],
            "check_in" => $row['check_in'],
            "check_out" => $row['check_out'],
            "status" => $row['status'],
            "created_at" => $row['created_at'],
            "room_price" => (float)$row['room_price'],
            "nights" => (int)$row['nights'],
            "total_amount" => (float)$row['total_amount']
        ));
    }

    // Calculate summary
    $summary = array(
        "total_bookings" => count($bookings["data"]),
        "total_revenue" => array_sum(array_column($bookings["data"], 'total_amount')),
        "average_stay" => count($bookings["data"]) > 0 
            ? array_sum(array_column($bookings["data"], 'nights')) / count($bookings["data"])
            : 0
    );

    $bookings["summary"] = $summary;

    http_response_code(200);
    echo json_encode($bookings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error retrieving booking statistics: " . $e->getMessage()]);
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}
?>
