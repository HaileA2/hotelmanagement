<?php
class Room {
    private $conn;
    private $table_name = "rooms";

    public $id;
    public $hotel_id;
    public $room_number;
    public $type;
    public $price_per_night;
    public $capacity;
    public $description;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create room
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET hotel_id = :hotel_id,
                      room_number = :room_number,
                      type = :type,
                      price_per_night = :price_per_night,
                      capacity = :capacity,
                      description = :description,
                      status = :status,
                      created_at = NOW(),
                      updated_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'available';

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

    // Get all rooms, optionally by hotel
    public function getAll($hotelId = null) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($hotelId) {
            $query .= " WHERE hotel_id = :hotel_id";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);

        if ($hotelId) {
            $stmt->bindParam(':hotel_id', $hotelId);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single room by id (for edit)
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // return as array inside array to match frontend expectation
            return [$row];
        }
        return [];
    }

public function exists() {
    $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
    $stmt = $this->conn->prepare($query);
    $this->id = htmlspecialchars(strip_tags($this->id));
    $stmt->bindParam(1, $this->id);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        return true;
    }
    return false;
}
    // Update room
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET hotel_id = :hotel_id,
                      room_number = :room_number,
                      type = :type,
                      price_per_night = :price_per_night,
                      capacity = :capacity,
                      description = :description,
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        return $stmt->execute();
    }

    // Delete room
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>
