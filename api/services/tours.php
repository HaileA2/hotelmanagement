<?php
// Prevent any accidental error output from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Adjusting paths - Ensure these files exist at these locations
require_once '../../config/database.php';
// Load .env values (if you create a .env file at the project root)
require_once '../../config/env.php';
require_once '../../helpers/jwt_helper.php'; 
require_once '../../classes/services/TourService.php';

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

$database = new Database();
$db = $database->getConnection();
$jwt = getBearerToken();

if (!$jwt) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Access denied. Token missing."]);
    exit();
}

try {
    $jwtHandler = new JwtHandler($db);
    $payload = $jwtHandler->getTokenPayload($jwt);
    
    // Normalize role check (lowercase to match your DB/Register logic)
    $userRole = strtolower($payload['role']);
    $allowedRoles = ['admin', 'manager', 'customer'];
    
    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Insufficient permissions."]);
        exit();
    }
    
    $tourService = new TourService();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    $result = null;
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $result = $tourService->getTourDetails($_GET['id']);
                
                // Ensure single tour is wrapped in array for consistency
                if (!is_array($result)) {
                    $result = [];
                } elseif (isset($result['id']) || isset($result['title'])) {
                    $result = [$result];
                }
            } elseif (isset($_GET['customer_email'])) {
                // Security: Customers can only see their own bookings
                if ($userRole !== 'admin' && $payload['email'] !== $_GET['customer_email']) {
                    throw new Exception('Unauthorized access to these bookings.');
                }
                $result = $tourService->getCustomerBookings($_GET['customer_email']);
                
                // Ensure result is an array
                if (!is_array($result)) {
                    $result = [];
                }
            } else {
                $filters = [
                    'location' => $_GET['location'] ?? null,
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 10
                ];
                $result = $tourService->getTours(array_filter($filters));
                
                // Ensure result is always an array (TourService should handle this, but double-check)
                if (!is_array($result)) {
                    error_log("Tour API: TourService returned non-array result, defaulting to empty array");
                    $result = [];
                }
            }
            break;
            
        case 'POST':
            if (!isset($input['customer_email'])) {
                $input['customer_email'] = $payload['email'];
                $input['customer_name'] = $payload['first_name'] . ' ' . $payload['last_name'];
            }
            $result = $tourService->bookTour($input);
            http_response_code(201);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method not allowed."]);
            exit();
    }
    
    // Consistent with tours.js: using 'success' instead of 'status'
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    error_log("Tour API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}