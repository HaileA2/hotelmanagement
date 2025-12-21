// models/Amenity.php
<?php
class Amenity {
    private $conn;
    private $table_name = "amenities";

    public $id;
    public $name;
    public $icon;
    public $description;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new amenity
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (name, icon, description) 
                VALUES 
                (:name, :icon, :description)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->icon = htmlspecialchars(strip_tags($this->icon));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":icon", $this->icon);
        $stmt->bindParam(":description", $this->description);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Read all amenities
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read single amenity
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->name = $row['name'];
            $this->icon = $row['icon'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update amenity
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET name = :name, 
                     icon = :icon, 
                     description = :description,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->icon = htmlspecialchars(strip_tags($this->icon));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':icon', $this->icon);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete amenity
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

    // Search amenities
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE name LIKE ? OR description LIKE ? 
                 ORDER BY name ASC";
        
        $stmt = $this->conn->prepare($query);
        
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        
        $stmt->execute();
        return $stmt;
    }

    // Check if name exists
    public function nameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get amenities by hotel
    public function getByHotel($hotel_id) {
        $query = "SELECT a.* FROM " . $this->table_name . " a
                 INNER JOIN hotel_amenities ha ON a.id = ha.amenity_id
                 WHERE ha.hotel_id = ?
                 ORDER BY a.name ASC";

        $stmt = $this->conn->prepare($query);
        $hotel_id = htmlspecialchars(strip_tags($hotel_id));
        $stmt->bindParam(1, $hotel_id);

        $stmt->execute();
        return $stmt;
    }
}
?>