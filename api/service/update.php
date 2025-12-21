<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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

$data = json_decode(file_get_contents("php://input"));

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    if (!in_array($payload['role'], ['Admin', 'Manager'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }

    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["message" => "Service ID is required."]);
        exit();
    }

    // Check if service exists
    $stmt = $db->prepare("SELECT * FROM services WHERE id = :id");
    $stmt->execute([':id' => $data->id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Service not found."]);
        exit();
    }

    // Build update query
    $updates = [];
    $params = [':id' => $data->id];

    $updatable_fields = ['name', 'description', 'service_type', 'price', 'is_available'];
    
    foreach ($updatable_fields as $field) {
        if (isset($data->$field)) {
            if ($field === 'service_type') {
                // Validate service type
                $valid_service_types = ['restaurant', 'spa', 'tour', 'transport', 'other'];
                if (!in_array($data->$field, $valid_service_types)) {
                    http_response_code(400);
                    echo json_encode(["message" => "Invalid service type. Must be one of: " . implode(', ', $valid_service_types)]);
                    exit();
                }
            }
            $updates[] = "$field = :$field";
            $params[":$field"] = $field === 'is_available' ? (int)$data->$field : $data->$field;
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(["message" => "No fields to update."]);
        exit();
    }

    $query = "UPDATE services SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE id = :id";
    
    $stmt = $db->prepare($query);

    if ($stmt->execute($params)) {
        http_response_code(200);
        echo json_encode(["message" => "Service updated successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update service."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error updating service: " . $e->getMessage()]);
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
