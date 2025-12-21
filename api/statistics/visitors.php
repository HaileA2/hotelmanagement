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

    // Get date range from query parameters (default to last 30 days)
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;

    // Build query
    $query = "SELECT 
                DATE(check_in) as date,
                COUNT(DISTINCT user_id) as unique_visitors,
                COUNT(*) as total_bookings,
                SUM(price) as total_revenue
              FROM bookings b
              JOIN rooms r ON b.room_id = r.id
              WHERE check_in BETWEEN :start_date AND :end_date";
    
    $params = [
        ':start_date' => $start_date,
        ':end_date' => $end_date . ' 23:59:59'
    ];

    if ($hotel_id) {
        $query .= " AND r.hotel_id = :hotel_id";
        $params[':hotel_id'] = $hotel_id;
    }

    $query .= " GROUP BY DATE(check_in) ORDER BY date";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $stats = array();
    $stats["period"] = array(
        "start_date" => $start_date,
        "end_date" => $end_date
    );
    $stats["data"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($stats["data"], array(
            "date" => $row['date'],
            "unique_visitors" => (int)$row['unique_visitors'],
            "total_bookings" => (int)$row['total_bookings'],
            "total_revenue" => (float)$row['total_revenue']
        ));
    }

    // Calculate totals
    $totals = array(
        "total_visitors" => array_sum(array_column($stats["data"], 'unique_visitors')),
        "total_bookings" => array_sum(array_column($stats["data"], 'total_bookings')),
        "total_revenue" => array_sum(array_column($stats["data"], 'total_revenue'))
    );

    $stats["totals"] = $totals;

    http_response_code(200);
    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error retrieving statistics: " . $e->getMessage()]);
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
