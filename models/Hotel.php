<?php
class Hotel {
    // Database connection and table name
    private $conn;
    private $table_name = "hotels";

    // Object properties
    public $id;
    public $name;
    public $location;
    public $description;
    public $price_per_night;
    public $amenities;
    public $created_at;
    public $rating; // Added rating property

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read single hotel
// UPDATED: Read single hotel and return the data array
    // This allows $hotel_data = $hotel->readOne($id) to work in your API
    public function readOne($id = null) {
        // Use provided ID or the one already set in the object
        $target_id = $id ?? $this->id;

        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $target_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Populate object properties for convenience
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->location = $row['location'];
            $this->description = $row['description'];
            $this->amenities = $row['amenities'];
            $this->price_per_night = $row['price_per_night'] ?? 0;
            $this->created_at = $row['created_at'];
            
            return $row; // Return the array so the API can use it immediately
        }
        
        return null;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

public function create() {
        // UPDATED: Added rating to INSERT query
        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, location=:location, description=:description, 
                    amenities=:amenities, rating=:rating, created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->amenities = htmlspecialchars(strip_tags($this->amenities));
        $this->rating = (float)$this->rating; // Ensure numeric
        $this->created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":amenities", $this->amenities);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":created_at", $this->created_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        // UPDATED: Added rating to UPDATE query
        $query = "UPDATE " . $this->table_name . " 
                SET name=:name, location=:location, description=:description, 
                    amenities=:amenities, rating=:rating 
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->amenities = htmlspecialchars(strip_tags($this->amenities));
        $this->rating = (float)$this->rating;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':amenities', $this->amenities);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    // Delete the hotel
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
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Search hotels
    public function search($keywords) {
        // Select all query
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE name LIKE ? OR location LIKE ? OR description LIKE ? 
                 ORDER BY created_at DESC";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        // Execute query
        $stmt->execute();

        return $stmt;
    }
}
?>
