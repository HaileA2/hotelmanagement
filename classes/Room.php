<?php
class Room {
    // Database connection and table name
    private $conn;
    private $table_name = "rooms";

    // Object properties
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

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create room
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " 
                SET 
                    hotel_id = :hotel_id,
                    room_number = :room_number,
                    type = :type,
                    price_per_night = :price_per_night,
                    capacity = :capacity,
                    description = :description,
                    status = :status,
                    created_at = NOW(),
                    updated_at = NOW()";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = !empty($this->status) ? htmlspecialchars(strip_tags($this->status)) : 'available';

        // Bind values
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read all rooms
    public function read() {
        // Select all query
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }

    // Read one room
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Bind id of room to be updated
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            // Set values to object properties
            $this->hotel_id = $row['hotel_id'];
            $this->room_number = $row['room_number'];
            $this->type = $row['type'];
            $this->price_per_night = $row['price_per_night'];
            $this->capacity = $row['capacity'];
            $this->description = $row['description'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }

    // Update room
    public function update() {
        // Update query
        $query = "UPDATE " . $this->table_name . " 
                SET 
                    hotel_id = :hotel_id,
                    room_number = :room_number,
                    type = :type,
                    price_per_night = :price_per_night,
                    capacity = :capacity,
                    description = :description,
                    status = :status,
                    updated_at = NOW()
                WHERE 
                    id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Bind values
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        // Execute the query
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete room
    public function delete() {
        // Delete query
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind id of record to delete
        $stmt->bindParam(1, $this->id);

        // Execute query
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if room exists
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
}
?>
