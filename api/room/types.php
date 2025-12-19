<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
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
    
    // Get JWT token from Authorization header
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? '';
    $jwt = null;
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
    }

    if (!$jwt) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Access denied. No authentication token provided.'
        ]);
        exit();
    }
    
    // Initialize JWT handler
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    // Only admin can manage room types
    if ($payload['role'] !== 'Admin') {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // List all room types
            $stmt = $db->query("SELECT * FROM room_types ORDER BY name");
            $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $room_types
            ]);
            break;
            
        case 'POST':
            // Create new room type
            $data = json_decode(file_get_contents("php://input"));
            
            // Validate input
            if (!isset($data->name) || !isset($data->base_price) || !isset($data->max_occupancy)) {
                http_response_code(400);
                echo json_encode(["message" => "Name, base price, and max occupancy are required."]);
                exit();
            }
            
            $query = "INSERT INTO room_types (name, description, base_price, max_occupancy) 
                     VALUES (:name, :description, :base_price, :max_occupancy)";
            
            $stmt = $db->prepare($query);
            $success = $stmt->execute([
                ':name' => $data->name,
                ':description' => $data->description ?? '',
                ':base_price' => $data->base_price,
                ':max_occupancy' => $data->max_occupancy
            ]);
            
            if ($success) {
                $room_type_id = $db->lastInsertId();
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Room type created successfully.',
                    'data' => [
                        'id' => $room_type_id,
                        'name' => $data->name,
                        'description' => $data->description ?? '',
                        'base_price' => $data->base_price,
                        'max_occupancy' => $data->max_occupancy
                    ]
                ]);
            } else {
                throw new Exception('Failed to create room type.');
            }
            break;
            
        case 'PUT':
            // Update room type
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Room type ID is required."]);
                exit();
            }
            
            // Check if room type exists
            $checkStmt = $db->prepare("SELECT * FROM room_types WHERE id = :id");
            $checkStmt->execute([':id' => $data->id]);
            
            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["message" => "Room type not found."]);
                exit();
            }
            
            // Build update query
            $updates = [];
            $params = [':id' => $data->id];
            
            if (isset($data->name)) {
                $updates[] = "name = :name";
                $params[':name'] = $data->name;
            }
            
            if (isset($data->description)) {
                $updates[] = "description = :description";
                $params[':description'] = $data->description;
            }
            
            if (isset($data->base_price)) {
                $updates[] = "base_price = :base_price";
                $params[':base_price'] = $data->base_price;
            }
            
            if (isset($data->max_occupancy)) {
                $updates[] = "max_occupancy = :max_occupancy";
                $params[':max_occupancy'] = $data->max_occupancy;
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(["message" => "No fields to update."]);
                exit();
            }
            
            $query = "UPDATE room_types SET " . implode(", ", $updates) . " WHERE id = :id";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($params)) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Room type updated successfully.'
                ]);
            } else {
                throw new Exception('Failed to update room type.');
            }
            break;
            
        case 'DELETE':
            // Delete room type
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Room type ID is required."]);
                exit();
            }
            
            // Check if room type exists and has no associated rooms
            $checkStmt = $db->prepare(
                "SELECT rt.*, COUNT(r.id) as room_count 
                 FROM room_types rt 
                 LEFT JOIN rooms r ON r.room_type_id = rt.id 
                 WHERE rt.id = :id 
                 GROUP BY rt.id"
            );
            $checkStmt->execute([':id' => $data->id]);
            $roomType = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$roomType) {
                http_response_code(404);
                echo json_encode(["message" => "Room type not found."]);
                exit();
            }
            
            if ($roomType['room_count'] > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Cannot delete room type with associated rooms."]);
                exit();
            }
            
            $stmt = $db->prepare("DELETE FROM room_types WHERE id = :id");
            
            if ($stmt->execute([':id' => $data->id])) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Room type deleted successfully.'
                ]);
            } else {
                throw new Exception('Failed to delete room type.');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed."]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing request: ' . $e->getMessage()
    ]);
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
