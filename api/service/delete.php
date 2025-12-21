<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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
    $stmt = $db->prepare("SELECT id FROM services WHERE id = :id");
    $stmt->execute([':id' => $data->id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Service not found."]);
        exit();
    }

    // Delete service
    $query = "DELETE FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Service deleted successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete service."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error deleting service: " . $e->getMessage()]);
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
