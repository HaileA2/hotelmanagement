<?php
class Room {
    // Database connection and table name
    private $conn;
    private $table_name = "rooms";

    // Object properties
    public $id;
    public $hotel_id;
    public $type;
    public $price;
    public $availability;
    public $max_occupancy;
    public $created_at;
    public $updated_at;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all rooms for a hotel
    public function readByHotel($hotel_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE hotel_id = :hotel_id 
                     ORDER BY id ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':hotel_id', $hotel_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in readByHotel: " . $e->getMessage());
            throw $e;
        }
    }

    // Get single room by ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE id = ? 
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'] ?? null;
            $this->hotel_id = $row['hotel_id'] ?? null;
            $this->type = $row['type'] ?? '';
            $this->price = $row['price'] ?? 0;
            $this->availability = $row['availability'] ?? 'Available';
            $this->max_occupancy = $row['max_occupancy'] ?? 1;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            
            return true;
        }
        
        return false;
    }

    // Get available rooms for a hotel within a date range
    public function getAvailableRooms($hotel_id, $check_in, $check_out, $capacity = null) {
        $query = "SELECT r.*, h.name as hotel_name 
                 FROM " . $this->table_name . " r
                 JOIN hotels h ON r.hotel_id = h.id
                 WHERE r.hotel_id = :hotel_id 
                 AND r.status = 'available'";
        
        if ($capacity) {
            $query .= " AND r.capacity >= :capacity";
        }
        
        $query .= " AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE hotel_id = :hotel_id2 
            AND status != 'cancelled'
            AND (
                (check_in <= :check_in AND check_out >= :check_in) OR
                (check_in <= :check_out AND check_out >= :check_out) OR
                (check_in >= :check_in AND check_out <= :check_out)
            )
        )";
        
        $query .= " ORDER BY r.price ASC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':hotel_id', $hotel_id);
        $stmt->bindParam(':hotel_id2', $hotel_id);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        
        if ($capacity) {
            $stmt->bindParam(':capacity', $capacity);
        }
        
        $stmt->execute();
        
        return $stmt;
    }

    // Create new room
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (hotel_id, type, price, max_occupancy, availability)
                 VALUES
                 (:hotel_id, :type, :price, :max_occupancy, :availability)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price = (float)htmlspecialchars(strip_tags($this->price));
        $this->max_occupancy = (int)htmlspecialchars(strip_tags($this->max_occupancy));
        $this->availability = in_array($this->availability, ['Available', 'Booked', 'Maintenance']) 
                            ? $this->availability 
                            : 'Available';
        
        // Bind values
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":max_occupancy", $this->max_occupancy);
        $stmt->bindParam(":availability", $this->availability);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Update room
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET type=:type, price=:price, capacity=:capacity, 
                     description=:description, amenities=:amenities, 
                     image_url=:image_url, status=:status 
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->amenities = htmlspecialchars(strip_tags($this->amenities));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":amenities", $this->amenities);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Delete room
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
