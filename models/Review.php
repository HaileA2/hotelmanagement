<?php
class Review {
    private $conn;
    private $table_name = "reviews";

    public $id;
    public $booking_id;
    public $user_id;
    public $hotel_id;
    public $room_id;
    public $rating;
    public $title;
    public $comment;
    public $staff_rating;
    public $cleanliness_rating;
    public $comfort_rating;
    public $location_rating;
    public $facilities_rating;
    public $value_for_money_rating;
    public $is_approved;
    public $created_at;
    public $updated_at;
    
    // Additional properties for joins
    public $user_name;
    public $hotel_name;
    public $room_number;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new review
    public function create() {
        // Check if user has already reviewed this booking
        $check_query = "SELECT id FROM " . $this->table_name . " 
                       WHERE booking_id = :booking_id AND user_id = :user_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':booking_id', $this->booking_id);
        $check_stmt->bindParam(':user_id', $this->user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            return "already_exists";
        }
        
        // Get booking details
        $booking_query = "SELECT hotel_id, room_id FROM bookings 
                         WHERE id = :booking_id AND user_id = :user_id";
        $booking_stmt = $this->conn->prepare($booking_query);
        $booking_stmt->bindParam(':booking_id', $this->booking_id);
        $booking_stmt->bindParam(':user_id', $this->user_id);
        $booking_stmt->execute();
        
        if ($booking_stmt->rowCount() == 0) {
            return "invalid_booking";
        }
        
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Insert review
        $query = "INSERT INTO " . $this->table_name . " 
                 (booking_id, user_id, hotel_id, room_id, rating, title, comment, 
                  staff_rating, cleanliness_rating, comfort_rating, location_rating, 
                  facilities_rating, value_for_money_rating, is_approved)
                 VALUES 
                 (:booking_id, :user_id, :hotel_id, :room_id, :rating, :title, :comment,
                  :staff_rating, :cleanliness_rating, :comfort_rating, :location_rating,
                  :facilities_rating, :value_for_money_rating, :is_approved)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->hotel_id = $booking['hotel_id'];
        $this->room_id = $booking['room_id'];
        $this->rating = min(5, max(1, (int)$this->rating)); // Ensure rating is between 1-5
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->comment = htmlspecialchars(strip_tags($this->comment));
        $this->staff_rating = isset($this->staff_rating) ? min(5, max(1, (int)$this->staff_rating)) : null;
        $this->cleanliness_rating = isset($this->cleanliness_rating) ? min(5, max(1, (int)$this->cleanliness_rating)) : null;
        $this->comfort_rating = isset($this->comfort_rating) ? min(5, max(1, (int)$this->comfort_rating)) : null;
        $this->location_rating = isset($this->location_rating) ? min(5, max(1, (int)$this->location_rating)) : null;
        $this->facilities_rating = isset($this->facilities_rating) ? min(5, max(1, (int)$this->facilities_rating)) : null;
        $this->value_for_money_rating = isset($this->value_for_money_rating) ? min(5, max(1, (int)$this->value_for_money_rating)) : null;
        $this->is_approved = $this->is_approved ? 1 : 0;
        
        // Bind values
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":comment", $this->comment);
        $stmt->bindParam(":staff_rating", $this->staff_rating);
        $stmt->bindParam(":cleanliness_rating", $this->cleanliness_rating);
        $stmt->bindParam(":comfort_rating", $this->comfort_rating);
        $stmt->bindParam(":location_rating", $this->location_rating);
        $stmt->bindParam(":facilities_rating", $this->facilities_rating);
        $stmt->bindParam(":value_for_money_rating", $this->value_for_money_rating);
        $stmt->bindParam(":is_approved", $this->is_approved);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->updateHotelRating();
            return "success";
        }
        return "error";
    }

    // Update hotel rating based on reviews
    private function updateHotelRating() {
        // Calculate average rating
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                 FROM " . $this->table_name . " 
                 WHERE hotel_id = ? AND is_approved = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->hotel_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update hotel rating
        $update_query = "UPDATE hotels 
                        SET rating = :rating, 
                            total_reviews = :total_reviews,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = :hotel_id";
        
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(':rating', $result['avg_rating']);
        $update_stmt->bindParam(':total_reviews', $result['total_reviews']);
        $update_stmt->bindParam(':hotel_id', $this->hotel_id);
        $update_stmt->execute();
    }

    // Read all reviews (with filters)
    public function readAll($filters = []) {
        $query = "SELECT r.*, 
                         u.first_name, u.last_name, u.email as user_email,
                         h.name as hotel_name,
                         rm.room_number
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN hotels h ON r.hotel_id = h.id
                  LEFT JOIN rooms rm ON r.room_id = rm.id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['hotel_id'])) {
            $query .= " AND r.hotel_id = ?";
            $params[] = $filters['hotel_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['user_id'])) {
            $query .= " AND r.user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (isset($filters['is_approved'])) {
            $query .= " AND r.is_approved = ?";
            $params[] = $filters['is_approved'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (!empty($filters['min_rating'])) {
            $query .= " AND r.rating >= ?";
            $params[] = $filters['min_rating'];
            $types .= 'i';
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        // Add pagination
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            $types .= 'i';
            
            if (!empty($filters['offset'])) {
                $query .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
                $types .= 'i';
            }
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters dynamically if any
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt;
    }

    // Read single review
    public function readOne() {
        $query = "SELECT r.*, 
                         u.first_name, u.last_name, u.email as user_email,
                         h.name as hotel_name,
                         rm.room_number
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN hotels h ON r.hotel_id = h.id
                  LEFT JOIN rooms rm ON r.room_id = rm.id
                  WHERE r.id = ?
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->booking_id = $row['booking_id'];
            $this->user_id = $row['user_id'];
            $this->hotel_id = $row['hotel_id'];
            $this->room_id = $row['room_id'];
            $this->rating = $row['rating'];
            $this->title = $row['title'];
            $this->comment = $row['comment'];
            $this->staff_rating = $row['staff_rating'];
            $this->cleanliness_rating = $row['cleanliness_rating'];
            $this->comfort_rating = $row['comfort_rating'];
            $this->location_rating = $row['location_rating'];
            $this->facilities_rating = $row['facilities_rating'];
            $this->value_for_money_rating = $row['value_for_money_rating'];
            $this->is_approved = $row['is_approved'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            // Additional joined fields
            $this->user_name = $row['first_name'] . ' ' . $row['last_name'];
            $this->user_email = $row['user_email'];
            $this->hotel_name = $row['hotel_name'];
            $this->room_number = $row['room_number'];
            
            return true;
        }
        return false;
    }

    // Update review
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET rating = :rating,
                     title = :title,
                     comment = :comment,
                     staff_rating = :staff_rating,
                     cleanliness_rating = :cleanliness_rating,
                     comfort_rating = :comfort_rating,
                     location_rating = :location_rating,
                     facilities_rating = :facilities_rating,
                     value_for_money_rating = :value_for_money_rating,
                     is_approved = :is_approved,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->rating = min(5, max(1, (int)$this->rating));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->comment = htmlspecialchars(strip_tags($this->comment));
        $this->staff_rating = isset($this->staff_rating) ? min(5, max(1, (int)$this->staff_rating)) : null;
        $this->cleanliness_rating = isset($this->cleanliness_rating) ? min(5, max(1, (int)$this->cleanliness_rating)) : null;
        $this->comfort_rating = isset($this->comfort_rating) ? min(5, max(1, (int)$this->comfort_rating)) : null;
        $this->location_rating = isset($this->location_rating) ? min(5, max(1, (int)$this->location_rating)) : null;
        $this->facilities_rating = isset($this->facilities_rating) ? min(5, max(1, (int)$this->facilities_rating)) : null;
        $this->value_for_money_rating = isset($this->value_for_money_rating) ? min(5, max(1, (int)$this->value_for_money_rating)) : null;
        $this->is_approved = $this->is_approved ? 1 : 0;
        
        // Bind values
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':comment', $this->comment);
        $stmt->bindParam(':staff_rating', $this->staff_rating);
        $stmt->bindParam(':cleanliness_rating', $this->cleanliness_rating);
        $stmt->bindParam(':comfort_rating', $this->comfort_rating);
        $stmt->bindParam(':location_rating', $this->location_rating);
        $stmt->bindParam(':facilities_rating', $this->facilities_rating);
        $stmt->bindParam(':value_for_money_rating', $this->value_for_money_rating);
        $stmt->bindParam(':is_approved', $this->is_approved);
        
        if ($stmt->execute()) {
            $this->updateHotelRating();
            return true;
        }
        return false;
    }

    // Delete review
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if ($stmt->execute()) {
            $this->updateHotelRating();
            return true;
        }
        return false;
    }

    // Approve review
    public function approve() {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_approved = 1,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            $this->is_approved = 1;
            $this->updateHotelRating();
            return true;
        }
        return false;
    }

    // Get average ratings for a hotel
    public function getHotelRatings($hotel_id) {
        $query = "SELECT 
                    AVG(rating) as overall_rating,
                    AVG(staff_rating) as avg_staff_rating,
                    AVG(cleanliness_rating) as avg_cleanliness_rating,
                    AVG(comfort_rating) as avg_comfort_rating,
                    AVG(location_rating) as avg_location_rating,
                    AVG(facilities_rating) as avg_facilities_rating,
                    AVG(value_for_money_rating) as avg_value_rating,
                    COUNT(*) as total_reviews
                  FROM " . $this->table_name . " 
                  WHERE hotel_id = ? AND is_approved = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$hotel_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
