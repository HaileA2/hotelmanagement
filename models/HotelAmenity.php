// models/HotelAmenity.php
<?php
class HotelAmenity {
    private $conn;
    private $table_name = "hotel_amenities";

    public $id;
    public $hotel_id;
    public $amenity_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addAmenityToHotel() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (hotel_id, amenity_id) 
                  VALUES 
                 (:hotel_id, :amenity_id)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->amenity_id = htmlspecialchars(strip_tags($this->amenity_id));
        
        // Bind values
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":amenity_id", $this->amenity_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Other methods...
}
?>