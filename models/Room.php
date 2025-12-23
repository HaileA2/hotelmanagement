<?php
class Room {
    private $conn;
    private $table_name = "rooms";

    // Unified Properties
    public $id;
    public $hotel_id;
    public $room_number;
    public $type;
    public $price_per_night;
    public $capacity;
    public $description;
    public $status; // 'available', 'booked', 'maintenance'
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- READ METHODS ---

    // Get all rooms (with optional hotel filter)
    public function getAll($hotelId = null) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($hotelId) {
            $query .= " WHERE hotel_id = :hotel_id";
        }
        $query .= " ORDER BY room_number ASC";

        $stmt = $this->conn->prepare($query);
        if ($hotelId) {
            $stmt->bindParam(':hotel_id', $hotelId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single room details into object properties
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->hotel_id = $row['hotel_id'];
            $this->room_number = $row['room_number'];
            $this->type = $row['type'];
            $this->price_per_night = $row['price_per_night'];
            $this->capacity = $row['capacity'];
            $this->description = $row['description'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // The "Power" Method: Find rooms that aren't booked for specific dates
    public function getAvailableRooms($hotel_id, $check_in, $check_out, $guests = null) {
        $query = "SELECT r.* FROM " . $this->table_name . " r
                 WHERE r.hotel_id = :hotel_id 
                 AND r.status = 'available'";
        
        if ($guests) {
            $query .= " AND r.capacity >= :guests";
        }
        
        // Exclude rooms that have an overlapping booking
        $query .= " AND r.id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status != 'cancelled'
            AND (
                (check_in <= :check_in AND check_out > :check_in) OR
                (check_in < :check_out AND check_out >= :check_out) OR
                (check_in >= :check_in AND check_out <= :check_out)
            )
        )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hotel_id', $hotel_id);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        if ($guests) $stmt->bindParam(':guests', $guests);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- WRITE METHODS ---

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET hotel_id=:hotel_id, room_number=:room_number, type=:type, 
                      price_per_night=:price_per_night, capacity=:capacity, 
                      description=:description, status=:status, created_at=NOW()";

        $stmt = $this->conn->prepare($query);
        $this->sanitize();

        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET hotel_id=:hotel_id, room_number=:room_number, type=:type, 
                      price_per_night=:price_per_night, capacity=:capacity, 
                      description=:description, status=:status, updated_at=NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        $stmt->bindParam(":id", $this->id);
        // ... (remaining binds same as create)
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    private function sanitize() {
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = $this->status ?: 'available';
    }
}