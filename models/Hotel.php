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
    public $amenities;
    public $created_at;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all hotels
    public function read() {
        // Select all query
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        
        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }

    // Read single hotel
    public function readOne() {
        // Query to read single record
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);
        
        // Bind id of hotel to be read
        $stmt->bindParam(1, $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get retrieved row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set values to object properties
        if ($row) {
            $this->name = $row['name'];
            $this->location = $row['location'];
            $this->description = $row['description'];
            $this->amenities = $row['amenities'];
            $this->created_at = $row['created_at'];
            return true;
        }
        
        return false;
    }

    // Create hotel
    public function create() {
        // Query to insert record
        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, location=:location, description=:description, 
                    amenities=:amenities, created_at=:created_at";

        // Prepare query
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->amenities = htmlspecialchars(strip_tags($this->amenities));
        $this->created_at = date('Y-m-d H:i:s');

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":amenities", $this->amenities);
        $stmt->bindParam(":created_at", $this->created_at);

        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Update the hotel
    public function update() {
        // Update query
        $query = "UPDATE " . $this->table_name . " 
                SET name=:name, location=:location, description=:description, 
                    amenities=:amenities 
                WHERE id = :id";

        // Prepare query statement
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->amenities = htmlspecialchars(strip_tags($this->amenities));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind new values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':amenities', $this->amenities);
        $stmt->bindParam(':id', $this->id);

        // Execute the query
        if ($stmt->execute()) {
            return true;
        }

        return false;
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
