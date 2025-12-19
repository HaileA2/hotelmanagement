<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../helpers/jwt_helper.php';
require_once '../../classes/services/TourService.php';

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
    
    // Only authenticated users can access tour services
    if (!in_array($payload['role'], ['Admin', 'Manager', 'Customer'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }
    
    $tourService = new TourService();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            // Get tour details
            if (isset($_GET['id'])) {
                $result = $tourService->getTourDetails($_GET['id']);
            } 
            // Get tour availability
            elseif (isset($_GET['tour_id']) && isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $result = $tourService->getTourAvailability(
                    $_GET['tour_id'],
                    $_GET['start_date'],
                    $_GET['end_date']
                );
            }
            // Get customer's bookings
            elseif (isset($_GET['customer_email'])) {
                if ($payload['role'] !== 'Admin' && $payload['email'] !== $_GET['customer_email']) {
                    throw new Exception('You can only view your own bookings');
                }
                $result = $tourService->getCustomerBookings($_GET['customer_email']);
            }
            // Get tours by category
            elseif (isset($_GET['category_id'])) {
                $result = $tourService->getToursByCategory($_GET['category_id']);
            }
            // Get all tours with filters
            else {
                $filters = [
                    'location' => $_GET['location'] ?? null,
                    'date' => $_GET['date'] ?? null,
                    'price_min' => $_GET['price_min'] ?? null,
                    'price_max' => $_GET['price_max'] ?? null,
                    'duration' => $_GET['duration'] ?? null,
                    'category' => $_GET['category'] ?? null,
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 10
                ];
                $result = $tourService->getTours(array_filter($filters));
            }
            break;
            
        case 'POST':
            // Book a tour
            if (!isset($input['customer_email'])) {
                $input['customer_email'] = $payload['email'];
                $input['customer_name'] = $payload['name'] ?? '';
            }
            $result = $tourService->bookTour($input);
            http_response_code(201);
            break;
            
        case 'DELETE':
            // Cancel a booking
            if (!isset($_GET['booking_id'])) {
                throw new Exception('Booking ID is required');
            }
            $result = $tourService->cancelBooking($_GET['booking_id']);
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
