<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';
require_once '../helpers/jwt_helper.php';
require_once '../../classes/services/TaxiService.php';

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

    // Only authenticated users can access taxi services
    if (!in_array($payload['role'], ['Admin', 'Manager', 'Customer'])) {
        http_response_code(403);
        echo json_encode(["message" => "Insufficient permissions."]);
        exit();
    }

    $taxiService = new TaxiService();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'GET':
            // Get taxi types and pricing
            if (isset($_GET['types'])) {
                $result = $taxiService->getTaxiTypes();
            }
            // Get fare estimate
            elseif (isset($_GET['estimate'])) {
                $result = $taxiService->estimateFare([
                    'pickup_location' => $_GET['pickup'] ?? null,
                    'dropoff_location' => $_GET['dropoff'] ?? null,
                    'taxi_type' => $_GET['type'] ?? 'standard',
                    'passengers' => $_GET['passengers'] ?? 1
                ]);
            }
            // Get customer's taxi bookings
            elseif (isset($_GET['customer_email'])) {
                if ($payload['role'] !== 'Admin' && $payload['email'] !== $_GET['customer_email']) {
                    throw new Exception('You can only view your own bookings');
                }
                $result = $taxiService->getCustomerBookings($_GET['customer_email']);
            }
            // Get available taxis
            else {
                $filters = [
                    'location' => $_GET['location'] ?? null,
                    'type' => $_GET['type'] ?? null,
                    'available' => $_GET['available'] ?? null,
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 10
                ];
                $result = $taxiService->getAvailableTaxis(array_filter($filters));
            }
            break;

        case 'POST':
            // Book a taxi
            if (!isset($input['customer_email'])) {
                $input['customer_email'] = $payload['email'];
                $input['customer_name'] = $payload['name'] ?? '';
            }
            $result = $taxiService->bookTaxi($input);
            http_response_code(201);
            break;

        case 'DELETE':
            // Cancel a taxi booking
            if (!isset($_GET['booking_id'])) {
                throw new Exception('Booking ID is required');
            }
            $result = $taxiService->cancelBooking($_GET['booking_id']);
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