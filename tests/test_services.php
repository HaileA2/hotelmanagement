<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class ServiceTester {
    private $baseUrl;
    private $jwtToken;
    
    public function __construct() {
        $this->baseUrl = 'http://localhost/hotel-management-system/api';
        $this->loginAndGetToken();
    }
    
    private function loginAndGetToken() {
        // You need to replace these with valid test credentials
        $loginData = [
            'email' => 'admin@example.com', // Replace with test admin email
            'password' => 'admin123'       // Replace with test admin password
        ];
        
        $response = $this->makeRequest('POST', '/auth/login.php', $loginData);
        
        if (isset($response['token'])) {
            $this->jwtToken = $response['token'];
            echo "Successfully logged in. Token obtained.\n\n";
        } else {
            throw new Exception("Failed to login: " . json_encode($response));
        }
    }
    
    public function testTourServices() {
        echo "=== Testing Tour Services ===\n";
        
        // 1. Get available tours
        echo "\n1. Getting available tours...\n";
        $tours = $this->makeRequest('GET', '/services/tours.php');
        echo "Found " . count($tours['data'] ?? []) . " tours\n";
        
        if (empty($tours['data'])) {
            echo "No tours found. Cannot proceed with further tests.\n";
            return;
        }
        
        $tourId = $tours['data'][0]['id'];
        
        // 2. Get tour details
        echo "\n2. Getting tour details for ID: $tourId\n";
        $tourDetails = $this->makeRequest('GET', "/services/tours.php?id=$tourId");
        echo "Tour name: " . ($tourDetails['data']['name'] ?? 'N/A') . "\n";
        
        // 3. Test booking a tour (simulated, would need valid data)
        echo "\n3. Testing tour booking (simulated)...\n";
        $bookingData = [
            'tour_id' => $tourId,
            'date' => date('Y-m-d', strtotime('+1 week')),
            'adults' => 2,
            'children' => 1,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'special_requests' => 'Test booking'
        ];
        
        try {
            $booking = $this->makeRequest('POST', '/services/tours.php', $bookingData);
            $bookingId = $booking['data']['booking_id'] ?? null;
            echo "Booking created with ID: " . ($bookingId ?: 'N/A') . "\n";
            
            // 4. Test getting booking details
            if ($bookingId) {
                echo "\n4. Getting booking details...\n";
                $bookingDetails = $this->makeRequest('GET', "/services/tours.php?customer_email=test@example.com");
                echo "Found " . count($bookingDetails['data'] ?? []) . " bookings for test@example.com\n";
                
                // 5. Test cancelling booking (uncomment to test)
                // echo "\n5. Cancelling booking...\n";
                // $cancelResponse = $this->makeRequest('DELETE', "/services/tours.php?booking_id=$bookingId");
                // echo "Booking cancelled: " . ($cancelResponse['success'] ? 'Yes' : 'No') . "\n";
            }
        } catch (Exception $e) {
            echo "Booking test skipped: " . $e->getMessage() . "\n";
        }
    }
    
    public function testRestaurantServices() {
        echo "\n\n=== Testing Restaurant Services ===\n";
        
        // 1. Get list of restaurants
        echo "\n1. Getting list of restaurants...\n";
        $restaurants = $this->makeRequest('GET', '/services/restaurants.php');
        echo "Found " . count($restaurants['data'] ?? []) . " restaurants\n";
        
        if (empty($restaurants['data'])) {
            echo "No restaurants found. Cannot proceed with further tests.\n";
            return;
        }
        
        $restaurantId = $restaurants['data'][0]['id'];
        
        // 2. Get restaurant details
        echo "\n2. Getting restaurant details for ID: $restaurantId\n";
        $restaurantDetails = $this->makeRequest('GET', "/services/restaurants.php?id=$restaurantId");
        echo "Restaurant name: " . ($restaurantDetails['data']['name'] ?? 'N/A') . "\n";
        
        // 3. Get available time slots
        $date = date('Y-m-d', strtotime('+1 day'));
        echo "\n3. Checking available time slots for $date...\n";
        $times = $this->makeRequest('GET', "/services/restaurants.php?restaurant_id=$restaurantId&date=$date&party_size=2");
        echo "Available time slots: " . (empty($times['data']) ? 'None' : implode(', ', $times['data'])) . "\n";
    }
    
    public function testTaxiServices() {
        echo "\n\n=== Testing Taxi Services ===\n";
        
        // 1. Estimate fare
        echo "\n1. Estimating taxi fare...\n";
        $fareEstimate = $this->makeRequest('GET', '/services/taxi.php', [
            'action' => 'estimate',
            'pickup_lat' => 48.8566,
            'pickup_lng' => 2.3522,
            'dropoff_lat' => 48.8606,
            'dropoff_lng' => 2.3376
        ]);
        
        if (isset($fareEstimate['data']['estimated_fare'])) {
            echo "Estimated fare: " . $fareEstimate['data']['estimated_fare'] . ' ' . 
                 ($fareEstimate['data']['currency'] ?? 'USD') . "\n";
        } else {
            echo "Could not get fare estimate. Response: " . json_encode($fareEstimate) . "\n";
        }
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->jwtToken) {
            $headers[] = 'Authorization: Bearer ' . $this->jwtToken;
        }
        
        $ch = curl_init();
        
        // Add query parameters for GET requests
        if ($method === 'GET' && $data) {
            $url .= '?' . http_build_query($data);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $decodedResponse['message'] ?? 'Unknown error';
            throw new Exception("API Error ($httpCode): $errorMsg");
        }
        
        return $decodedResponse;
    }
}

// Run tests
try {
    $tester = new ServiceTester();
    
    // Run all tests
    $tester->testTourServices();
    
    echo "\n\n=== All tests completed ===\n";
} catch (Exception $e) {
    echo "\n\n=== Test Failed ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Helper function to pretty print arrays
function prettyPrint($data) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
?>
