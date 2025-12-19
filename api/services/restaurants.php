<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../helpers/jwt_helper.php';
require_once '../../classes/services/RestaurantService.php';

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
    
    // Only authenticated users can access restaurant services
    if (!in_array($payload['role'], ['Admin', 'Manager', 'Customer'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }
    
    $restaurantService = new RestaurantService();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            // Get restaurant details
            if (isset($_GET['id'])) {
                $result = $restaurantService->getRestaurantDetails($_GET['id']);
            } 
            // Get restaurant menu
            elseif (isset($_GET['restaurant_id']) && isset($_GET['menu'])) {
                $result = $restaurantService->getMenu($_GET['restaurant_id']);
            }
            // Get available time slots
            elseif (isset($_GET['restaurant_id']) && isset($_GET['date'])) {
                $result = $restaurantService->getAvailableTimes(
                    $_GET['restaurant_id'],
                    $_GET['date'],
                    $_GET['party_size'] ?? 2
                );
            }
            // Get restaurant reviews
            elseif (isset($_GET['restaurant_id']) && isset($_GET['reviews'])) {
                $result = $restaurantService->getReviews($_GET['restaurant_id']);
            }
            // Get customer's reservations
            elseif (isset($_GET['customer_email'])) {
                if ($payload['role'] !== 'Admin' && $payload['email'] !== $_GET['customer_email']) {
                    throw new Exception('You can only view your own reservations');
                }
                $result = $restaurantService->getCustomerReservations($_GET['customer_email']);
            }
            // Get all restaurants with filters
            else {
                $filters = [
                    'cuisine' => $_GET['cuisine'] ?? null,
                    'location' => $_GET['location'] ?? null,
                    'price_range' => $_GET['price_range'] ?? null,
                    'rating' => $_GET['rating'] ?? null,
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 10
                ];
                $result = $restaurantService->getRestaurants(array_filter($filters));
            }
            break;
            
        case 'POST':
            // Make a reservation
            if (!isset($input['customer_email'])) {
                $input['customer_email'] = $payload['email'];
                $input['customer_name'] = $payload['name'] ?? '';
            }
            $result = $restaurantService->makeReservation($input);
            http_response_code(201);
            break;
            
        case 'DELETE':
            // Cancel a reservation
            if (!isset($_GET['reservation_id'])) {
                throw new Exception('Reservation ID is required');
            }
            $result = $restaurantService->cancelReservation($_GET['reservation_id']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed."]);
            exit();
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
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
