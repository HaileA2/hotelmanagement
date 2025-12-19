<?php
class Service {
    private $conn;
    private $table_name = "services";

    public $id;
    public $name;
    public $description;
    public $price;
    public $duration_minutes;
    public $is_available;
    public $created_by;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new service
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (name, description, price, duration_minutes, is_available, created_by) 
                 VALUES 
                 (:name, :description, :price, :duration_minutes, :is_available, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->duration_minutes = htmlspecialchars(strip_tags($this->duration_minutes));
        $this->is_available = htmlspecialchars(strip_tags($this->is_available));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":duration_minutes", $this->duration_minutes);
        $stmt->bindParam(":is_available", $this->is_available);
        $stmt->bindParam(":created_by", $this->created_by);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read all services
    public function read($only_available = false) {
        $query = "SELECT s.*, u.email as created_by_email 
                 FROM " . $this->table_name . " s
                 LEFT JOIN users u ON s.created_by = u.id";
        
        if ($only_available) {
            $query .= " WHERE s.is_available = 1";
        }
        
        $query .= " ORDER BY s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single service
    public function readOne() {
        $query = "SELECT s.*, u.email as created_by_email 
                 FROM " . $this->table_name . " s
                 LEFT JOIN users u ON s.created_by = u.id
                 WHERE s.id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->duration_minutes = $row['duration_minutes'];
            $this->is_available = $row['is_available'];
            $this->created_by = $row['created_by'];
            $this->created_by_email = $row['created_by_email'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update service
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET name = :name, 
                     description = :description, 
                     price = :price,
                     duration_minutes = :duration_minutes,
                     is_available = :is_available,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->duration_minutes = htmlspecialchars(strip_tags($this->duration_minutes));
        $this->is_available = htmlspecialchars(strip_tags($this->is_available));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':duration_minutes', $this->duration_minutes);
        $stmt->bindParam(':is_available', $this->is_available);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete service
    public function delete() {
        // First, check if the service is used in any bookings
        $check_query = "SELECT COUNT(*) as count FROM booking_services WHERE service_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->id);
        $check_stmt->execute();
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row['count'] > 0) {
            // If the service is used in bookings, don't delete it, just mark as not available
            $query = "UPDATE " . $this->table_name . " SET is_available = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            return $stmt->execute();
        } else {
            // If the service is not used in any bookings, delete it
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            return $stmt->execute();
        }
    }

    // Search services
    public function search($keywords, $only_available = false) {
        $query = "SELECT s.*, u.email as created_by_email 
                 FROM " . $this->table_name . " s
                 LEFT JOIN users u ON s.created_by = u.id
                 WHERE (s.name LIKE ? OR s.description LIKE ?)";
        
        if ($only_available) {
            $query .= " AND s.is_available = 1";
        }
        
        $query .= " ORDER BY s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        
        $stmt->execute();
        return $stmt;
    }

    // Get services by booking
    public function getByBooking($booking_id) {
        $query = "SELECT s.*, bs.quantity, bs.unit_price, bs.total_price, bs.status as booking_status
                 FROM " . $this->table_name . " s
                 INNER JOIN booking_services bs ON s.id = bs.service_id
                 WHERE bs.booking_id = ?
                 ORDER BY s.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $booking_id = htmlspecialchars(strip_tags($booking_id));
        $stmt->bindParam(1, $booking_id);
        
        $stmt->execute();
        return $stmt;
    }
}
?>
