<?php
require_once __DIR__ . '/../ExternalApiClient.php';

class TaxiService extends ExternalApiClient {
    public function __construct() {
        parent::__construct(
            getenv('TAXI_API_BASE_URL') ?: 'https://api.taxiservice.com/v1',
            getenv('TAXI_API_KEY')
        );
    }
    
    /**
     * Estimate fare for a ride
     * 
     * @param array $params Pickup and dropoff locations, vehicle type, etc.
     * @return array Fare estimate
     */
    public function estimateFare($params) {
        $required = ['pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng'];
        $this->validateRequiredFields($params, $required);
        
        return $this->sendRequest('GET', 'estimate', $params);
    }
    
    /**
     * Book a taxi
     * 
     * @param array $bookingData Booking details
     * @return array Booking confirmation
     */
    public function bookTaxi($bookingData) {
        $required = [
            'pickup_lat', 'pickup_lng', 'pickup_address',
            'dropoff_lat', 'dropoff_lng', 'dropoff_address',
            'customer_name', 'customer_phone'
        ];
        $this->validateRequiredFields($bookingData, $required);
        
        return $this->sendRequest('POST', 'bookings', $bookingData);
    }
    
    /**
     * Cancel a taxi booking
     * 
     * @param string $bookingId The booking ID
     * @return array Cancellation confirmation
     */
    public function cancelBooking($bookingId) {
        return $this->sendRequest('POST', "bookings/{$bookingId}/cancel");
    }
    
    /**
     * Get booking status
     * 
     * @param string $bookingId The booking ID
     * @return array Booking status
     */
    public function getBookingStatus($bookingId) {
        return $this->sendRequest('GET', "bookings/{$bookingId}");
    }
    
    /**
     * Track a taxi
     * 
     * @param string $bookingId The booking ID
     * @return array Taxi location and status
     */
    public function trackTaxi($bookingId) {
        return $this->sendRequest('GET', "bookings/{$bookingId}/track");
    }
    
    /**
     * Get available vehicle types
     * 
     * @param array $location Optional location for availability
     * @return array List of available vehicle types
     */
    public function getVehicleTypes($location = null) {
        $params = [];
        if ($location && isset($location['lat']) && isset($location['lng'])) {
            $params['lat'] = $location['lat'];
            $params['lng'] = $location['lng'];
        }
        
        return $this->sendRequest('GET', 'vehicles', $params);
    }
    
    /**
     * Get fare breakdown
     * 
     * @param string $bookingId The booking ID
     * @return array Detailed fare breakdown
     */
    public function getFareBreakdown($bookingId) {
        return $this->sendRequest('GET', "bookings/{$bookingId}/fare");
    }
    
    /**
     * Get driver details
     * 
     * @param string $bookingId The booking ID
     * @return array Driver information
     */
    public function getDriverDetails($bookingId) {
        return $this->sendRequest('GET', "bookings/{$bookingId}/driver");
    }
    
    /**
     * Get customer's ride history
     * 
     * @param string $customerId Customer ID or phone
     * @param int $limit Number of rides to return
     * @return array Ride history
     */
    public function getRideHistory($customerId, $limit = 10) {
        return $this->sendRequest('GET', 'rides', [
            'customer_id' => $customerId,
            'limit' => $limit
        ]);
    }
    
    /**
     * Validate required fields in the input data
     * 
     * @param array $data Input data
     * @param array $required Required field names
     * @throws Exception If any required field is missing
     */
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
}
?>
