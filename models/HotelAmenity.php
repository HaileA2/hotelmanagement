// models/HotelAmenity.php
<?php
class HotelAmenity {
    private $conn;
    private $table_name = "hotel_amenities";

    public $id;
    public $hotel_id;
    public $amenity_id;
    public $is_available;
    public $additional_charge;
    public $details;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
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
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function exists() {
        $query = "SELECT id FROM " . $this->table_name . "
                  WHERE hotel_id = :hotel_id AND amenity_id = :amenity_id LIMIT 1";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->amenity_id = htmlspecialchars(strip_tags($this->amenity_id));

        // Bind values
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":amenity_id", $this->amenity_id);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function readOne() {
        $query = "SELECT id, hotel_id, amenity_id, created_at
                  FROM " . $this->table_name . "
                  WHERE hotel_id = :hotel_id AND amenity_id = :amenity_id LIMIT 1";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->amenity_id = htmlspecialchars(strip_tags($this->amenity_id));

        // Bind values
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":amenity_id", $this->amenity_id);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE hotel_id = :hotel_id AND amenity_id = :amenity_id";

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

    public function addAmenityToHotel() {
        return $this->create();
    }
}
?>