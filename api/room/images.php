<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
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
    
    // Only admin and managers can manage room images
    if (!in_array($payload['role'], ['Admin', 'Manager'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get images for a specific room
            if (!isset($_GET['room_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Room ID is required."]);
                exit();
            }
            
            $room_id = (int)$_GET['room_id'];
            
            // Verify room exists and user has access
            if (!userHasAccessToRoom($db, $payload, $room_id)) {
                http_response_code(403);
                echo json_encode(["message" => "Access to this room is denied."]);
                exit();
            }
            
            $stmt = $db->prepare("SELECT * FROM room_images WHERE room_id = :room_id ORDER BY is_primary DESC, id ASC");
            $stmt->execute([':room_id' => $room_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $images
            ]);
            break;
            
        case 'POST':
            // Add a new image to a room
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->room_id) || !isset($data->image_url)) {
                http_response_code(400);
                echo json_encode(["message" => "Room ID and image URL are required."]);
                exit();
            }
            
            $room_id = (int)$data->room_id;
            
            // Verify room exists and user has access
            if (!userHasAccessToRoom($db, $payload, $room_id)) {
                http_response_code(403);
                echo json_encode(["message" => "Access to this room is denied."]);
                exit();
            }
            
            // Check if this is the first image being added (will be set as primary)
            $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM room_images WHERE room_id = :room_id");
            $checkStmt->execute([':room_id' => $room_id]);
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
            $is_primary = ($count == 0) ? 1 : 0;
            
            // If setting as primary, unset any existing primary image
            if (isset($data->is_primary) && $data->is_primary) {
                $updateStmt = $db->prepare("UPDATE room_images SET is_primary = 0 WHERE room_id = :room_id");
                $updateStmt->execute([':room_id' => $room_id]);
                $is_primary = 1;
            }
            
            $query = "INSERT INTO room_images (room_id, image_url, caption, is_primary) 
                     VALUES (:room_id, :image_url, :caption, :is_primary)";
            
            $stmt = $db->prepare($query);
            $success = $stmt->execute([
                ':room_id' => $room_id,
                ':image_url' => $data->image_url,
                ':caption' => $data->caption ?? '',
                ':is_primary' => $is_primary
            ]);
            
            if ($success) {
                $image_id = $db->lastInsertId();
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Image added successfully.',
                    'data' => [
                        'id' => $image_id,
                        'room_id' => $room_id,
                        'image_url' => $data->image_url,
                        'caption' => $data->caption ?? '',
                        'is_primary' => $is_primary == 1
                    ]
                ]);
            } else {
                throw new Exception('Failed to add image.');
            }
            break;
            
        case 'DELETE':
            // Delete an image
            $data = json_decode(file_get_contents("php://input"));
            
            if (!isset($data->id)) {
                http_response_code(400);
                echo json_encode(["message" => "Image ID is required."]);
                exit();
            }
            
            // Get image details
            $stmt = $db->prepare("SELECT * FROM room_images WHERE id = :id");
            $stmt->execute([':id' => $data->id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                http_response_code(404);
                echo json_encode(["message" => "Image not found."]);
                exit();
            }
            
            // Verify room exists and user has access
            if (!userHasAccessToRoom($db, $payload, $image['room_id'])) {
                http_response_code(403);
                echo json_encode(["message" => "Access to this image is denied."]);
                exit();
            }
            
            // If this was the primary image, we need to set another one as primary
            if ($image['is_primary']) {
                // Find another image for this room to set as primary
                $updateStmt = $db->prepare(
                    "UPDATE room_images SET is_primary = 1 
                     WHERE room_id = :room_id AND id != :id 
                     ORDER BY id ASC LIMIT 1"
                );
                $updateStmt->execute([
                    ':room_id' => $image['room_id'],
                    ':id' => $image['id']
                ]);
            }
            
            // Delete the image
            $deleteStmt = $db->prepare("DELETE FROM room_images WHERE id = :id");
            
            if ($deleteStmt->execute([':id' => $data->id])) {
                // Here you would typically also delete the actual image file from storage
                // unlink($image['image_url']);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Image deleted successfully.'
                ]);
            } else {
                throw new Exception('Failed to delete image.');
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

/**
 * Check if the user has access to the specified room
 * Admins have access to all rooms, managers only to rooms in their hotel
 */
function userHasAccessToRoom($db, $user, $room_id) {
    if ($user['role'] === 'Admin') {
        return true;
    }
    
    if ($user['role'] === 'Manager') {
        $stmt = $db->prepare(
            "SELECT 1 FROM rooms r 
             JOIN hotels h ON r.hotel_id = h.id 
             WHERE r.id = :room_id AND h.manager_id = :manager_id"
        );
        $stmt->execute([
            ':room_id' => $room_id,
            ':manager_id' => $user['user_id']
        ]);
        return $stmt->rowCount() > 0;
    }
    
    return false;
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
