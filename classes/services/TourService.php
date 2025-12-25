<?php
require_once __DIR__ . '/../ExternalApiClient.php';

class TourService extends ExternalApiClient {
    public function __construct() {
        // Allow overriding the remote API base URL and API key via environment variables
        // (useful for local dev or deploying real credentials).
        $baseUrl = getenv('TOUR_API_BASE_URL') ?: 'https://tour-management-web.onrender.com/api/v1';
        $apiKey = getenv('TOUR_API_KEY') ?: 'demo-api-key';

        parent::__construct($baseUrl, $apiKey);
    }

    protected function sendRequest($method, $endpoint, $data = null, $headers = []) {
        $headers['X-API-KEY'] = $this->apiKey;
        
        try {
            // Note: If ExternalApiClient uses curl, ensure it sets a decent timeout
            return parent::sendRequest($method, $endpoint, $data, $headers);
        } catch (Exception $e) {
            // Log the remote error and re-throw a cleaner message
            error_log("Remote API Error: " . $e->getMessage());
            throw new Exception("The tour service is currently unavailable. Please try again later.");
        }
    }

    public function getTours($filters = []) {
        try {
            // Log the external API call for debugging
            error_log("TourService: Calling external API for tours with filters: " . json_encode($filters));
            
            // Try removing '.php' if you get a 404 from the remote server
            $response = $this->sendRequest('GET', 'tours.php', $filters);
            
            // Log the raw response for debugging
            error_log("TourService: Raw external API response: " . json_encode($response));
            
            // Ensure we always return an array
            $tours = $this->normalizeToursResponse($response);
            
            // Log the normalized response
            error_log("TourService: Normalized tours response: " . json_encode($tours));
            
            return $tours;
            
        } catch (Exception $e) {
            // Log the error
            error_log("TourService: Error fetching tours: " . $e->getMessage());
            
            // Return fallback/mock data to ensure frontend doesn't break
            return $this->getFallbackTours();
        }
    }
    
    public function getTourDetails($tourId) {
        return $this->sendRequest('GET', 'tours.php', ['id' => $tourId]);
    }
    
    public function bookTour($bookingData) {
        $required = ['tour_id', 'customer_name', 'customer_email'];
        $this->validateRequiredFields($bookingData, $required);

        // Mapping your local field names to the remote API's expected field names
        $remoteData = [
            'tour_id' => $bookingData['tour_id'],
            'email'   => $bookingData['customer_email'],
            'name'    => $bookingData['customer_name']
        ];

        return $this->sendRequest('POST', 'tours.php', $remoteData);
    }

    // Keep the other methods as Exception throwers if not supported
    public function getCategories() {
        return $this->sendRequest('GET', 'places.php');
    }

    protected function validateRequiredFields($data, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing));
        }
    }

    public function getCustomerBookings($customerEmail) {
        return $this->sendRequest('GET', 'tours.php', ['customer_email' => $customerEmail]);
    }

    /**
     * Normalize the external API response to ensure we always return an array
     */
    protected function normalizeToursResponse($response) {
        // Handle different possible response structures
        if (is_array($response)) {
            // Case 1: Direct array of tours
            if (isset($response[0]) && is_array($response[0])) {
                return $response;
            }
            
            // Case 2: Response with 'data' property containing array
            if (isset($response['data']) && is_array($response['data'])) {
                return $response['data'];
            }
            
            // Case 3: Response with 'tours' property containing array
            if (isset($response['tours']) && is_array($response['tours'])) {
                return $response['tours'];
            }
            
            // Case 4: Response with 'results' property containing array
            if (isset($response['results']) && is_array($response['results'])) {
                return $response['results'];
            }
            
            // Case 5: Single tour object (wrap in array)
            if (isset($response['id']) || isset($response['title'])) {
                return [$response];
            }
        }
        
        // If we can't determine the structure, return empty array
        error_log("TourService: Unable to normalize response, returning empty array. Response: " . json_encode($response));
        return [];
    }

    /**
     * Get fallback/mock tours data when external API fails
     */
    protected function getFallbackTours() {
        return [
            [
                'id' => 1,
                'title' => 'Sample City Walking Tour',
                'location' => 'Downtown',
                'schedule_date' => '2024-02-15',
                'price' => 29.99,
                'description' => 'Explore the city with our expert guide'
            ],
            [
                'id' => 2,
                'title' => 'Museum Adventure',
                'location' => 'City Center',
                'schedule_date' => '2024-02-16',
                'price' => 45.00,
                'description' => 'Discover local history and culture'
            ],
            [
                'id' => 3,
                'title' => 'Food & Culture Tour',
                'location' => 'Old Town',
                'schedule_date' => '2024-02-17',
                'price' => 39.50,
                'description' => 'Taste local specialties and learn about traditions'
            ]
        ];
    }
}
?>
