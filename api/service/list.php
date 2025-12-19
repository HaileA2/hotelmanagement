<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../helpers/jwt_helper.php';

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
    
    // Get query parameters
    $hotel_id = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : null;
    $service_type = isset($_GET['type']) ? $_GET['type'] : null;
    $is_available = isset($_GET['available']) ? (int)$_GET['available'] : 1;

    // Build query
    $query = "SELECT s.*, h.name as hotel_name 
              FROM services s
              JOIN hotels h ON s.hotel_id = h.id 
              WHERE s.is_available = :is_available";
    
    $params = [':is_available' => $is_available];

    if ($hotel_id) {
        $query .= " AND s.hotel_id = :hotel_id";
        $params[':hotel_id'] = $hotel_id;
    }

    if ($service_type) {
        $query .= " AND s.service_type = :service_type";
        $params[':service_type'] = $service_type;
    }

    $query .= " ORDER BY s.name";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $services = array();
    $services["data"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $service_item = array(
            "id" => $row['id'],
            "hotel_id" => $row['hotel_id'],
            "hotel_name" => $row['hotel_name'],
            "name" => $row['name'],
            "description" => $row['description'],
            "service_type" => $row['service_type'],
            "price" => (float)$row['price'],
            "is_available" => (bool)$row['is_available'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        );
        array_push($services["data"], $service_item);
    }

    http_response_code(200);
    echo json_encode($services);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error retrieving services: " . $e->getMessage()]);
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
