<?php
require_once __DIR__ . '/../ExternalApiClient.php';

class RestaurantService extends ExternalApiClient {
    public function __construct() {
        parent::__construct(
            getenv('RESTAURANT_API_BASE_URL') ?: 'https://api.restaurantservice.com/v1',
            getenv('RESTAURANT_API_KEY')
        );
    }
    
    /**
     * Get list of restaurants
     * 
     * @param array $filters Optional filters like cuisine, location, etc.
     * @return array List of restaurants
     */
    public function getRestaurants($filters = []) {
        return $this->sendRequest('GET', 'restaurants', $filters);
    }
    
    /**
     * Get restaurant details
     * 
     * @param string $restaurantId The restaurant ID
     * @return array Restaurant details
     */
    public function getRestaurantDetails($restaurantId) {
        return $this->sendRequest('GET', "restaurants/{$restaurantId}");
    }
    
    /**
     * Get restaurant menu
     * 
     * @param string $restaurantId The restaurant ID
     * @return array Menu items
     */
    public function getMenu($restaurantId) {
        return $this->sendRequest('GET', "restaurants/{$restaurantId}/menu");
    }
    
    /**
     * Make a restaurant reservation
     * 
     * @param array $reservationData Reservation details
     * @return array Reservation confirmation
     */
    public function makeReservation($reservationData) {
        $required = ['restaurant_id', 'date', 'time', 'party_size', 'customer_name', 'customer_phone'];
        $this->validateRequiredFields($reservationData, $required);
        
        return $this->sendRequest('POST', 'reservations', $reservationData);
    }
    
    /**
     * Cancel a reservation
     * 
     * @param string $reservationId The reservation ID
     * @return array Cancellation confirmation
     */
    public function cancelReservation($reservationId) {
        return $this->sendRequest('POST', "reservations/{$reservationId}/cancel");
    }
    
    /**
     * Get reservation details
     * 
     * @param string $reservationId The reservation ID
     * @return array Reservation details
     */
    public function getReservation($reservationId) {
        return $this->sendRequest('GET', "reservations/{$reservationId}");
    }
    
    /**
     * Get available time slots for a restaurant
     * 
     * @param string $restaurantId The restaurant ID
     * @param string $date Date (Y-m-d)
     * @param int $partySize Number of people
     * @return array Available time slots
     */
    public function getAvailableTimes($restaurantId, $date, $partySize = 2) {
        return $this->sendRequest('GET', "restaurants/{$restaurantId}/availability", [
            'date' => $date,
            'party_size' => $partySize
        ]);
    }
    
    /**
     * Get customer's reservations
     * 
     * @param string $email Customer's email
     * @return array List of reservations
     */
    public function getCustomerReservations($email) {
        return $this->sendRequest('GET', 'reservations', ['email' => $email]);
    }
    
    /**
     * Get restaurant reviews
     * 
     * @param string $restaurantId The restaurant ID
     * @return array List of reviews
     */
    public function getReviews($restaurantId) {
        return $this->sendRequest('GET', "restaurants/{$restaurantId}/reviews");
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
