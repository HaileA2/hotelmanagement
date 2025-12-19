<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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

$data = json_decode(file_get_contents("php://input"));

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    if (!in_array($payload['role'], ['Admin', 'Manager'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }

    // Validate input
    $required_fields = ['hotel_id', 'name', 'description', 'service_type', 'price'];
    foreach ($required_fields as $field) {
        if (!isset($data->$field)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required field: " . $field]);
            exit();
        }
    }

    // Validate service type
    $valid_service_types = ['restaurant', 'spa', 'tour', 'transport', 'other'];
    if (!in_array($data->service_type, $valid_service_types)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid service type. Must be one of: " . implode(', ', $valid_service_types)]);
        exit();
    }

    // Check if hotel exists
    $stmt = $db->prepare("SELECT id FROM hotels WHERE id = :hotel_id");
    $stmt->execute([':hotel_id' => $data->hotel_id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Hotel not found."]);
        exit();
    }

    // Insert service
    $query = "INSERT INTO services 
              (hotel_id, name, description, service_type, price, is_available, created_at, updated_at)
              VALUES 
              (:hotel_id, :name, :description, :service_type, :price, :is_available, NOW(), NOW())";

    $stmt = $db->prepare($query);

    $params = [
        ':hotel_id' => $data->hotel_id,
        ':name' => $data->name,
        ':description' => $data->description,
        ':service_type' => $data->service_type,
        ':price' => $data->price,
        ':is_available' => isset($data->is_available) ? (int)$data->is_available : 1
    ];

    if ($stmt->execute($params)) {
        $service_id = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "message" => "Service created successfully.",
            "service_id" => $service_id
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to create service."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error creating service: " . $e->getMessage()]);
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
