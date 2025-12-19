<?php
require_once __DIR__ . '/../ExternalApiClient.php';

class TourService extends ExternalApiClient {
    public function __construct() {
        parent::__construct(
            getenv('TOUR_API_BASE_URL') ?: 'https://api.tourservice.com/v1',
            getenv('TOUR_API_KEY')
        );
    }
    
    /**
     * Get available tours
     * 
     * @param array $filters Optional filters like location, date, price range, etc.
     * @return array List of available tours
     */
    public function getTours($filters = []) {
        return $this->sendRequest('GET', 'tours', $filters);
    }
    
    /**
     * Get tour details by ID
     * 
     * @param string $tourId The tour ID
     * @return array Tour details
     */
    public function getTourDetails($tourId) {
        return $this->sendRequest('GET', "tours/{$tourId}");
    }
    
    /**
     * Book a tour
     * 
     * @param array $bookingData Booking details
     * @return array Booking confirmation
     */
    public function bookTour($bookingData) {
        $required = ['tour_id', 'date', 'adults', 'children', 'customer_name', 'customer_email'];
        $this->validateRequiredFields($bookingData, $required);
        
        return $this->sendRequest('POST', 'bookings', $bookingData);
    }
    
    /**
     * Cancel a tour booking
     * 
     * @param string $bookingId The booking ID
     * @return array Cancellation confirmation
     */
    public function cancelBooking($bookingId) {
        return $this->sendRequest('POST', "bookings/{$bookingId}/cancel");
    }
    
    /**
     * Get booking details
     * 
     * @param string $bookingId The booking ID
     * @return array Booking details
     */
    public function getBooking($bookingId) {
        return $this->sendRequest('GET', "bookings/{$bookingId}");
    }
    
    /**
     * Get available tour categories
     * 
     * @return array List of tour categories
     */
    public function getCategories() {
        return $this->sendRequest('GET', 'categories');
    }
    
    /**
     * Get tours by category
     * 
     * @param string $categoryId The category ID
     * @return array List of tours in the category
     */
    public function getToursByCategory($categoryId) {
        return $this->sendRequest('GET', "categories/{$categoryId}/tours");
    }
    
    /**
     * Get tour availability
     * 
     * @param string $tourId The tour ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Availability information
     */
    public function getTourAvailability($tourId, $startDate, $endDate) {
        return $this->sendRequest('GET', "tours/{$tourId}/availability", [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    /**
     * Get customer's tour bookings
     * 
     * @param string $email Customer's email
     * @return array List of customer's bookings
     */
    public function getCustomerBookings($email) {
        return $this->sendRequest('GET', 'bookings', ['email' => $email]);
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
