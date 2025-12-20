<?php
class Booking {
    // Database connection and table name
    private $conn;
    private $table_name = "bookings";

    // Object properties
    public $id;
    public $user_id;
    public $hotel_id;
    public $room_id;
    public $check_in;
    public $check_out;
    public $status;
    public $created_at;
    public $price_per_night;
    public $total_price;
    public $guest_count;
    public $special_requests;

    // Constructor with $db as database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new booking
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_id=:user_id, hotel_id=:hotel_id, room_id=:room_id, 
                    check_in=:check_in, check_out=:check_out, status=:status,
                    total_price=:total_price, guest_count=:guest_count,
                    special_requests=:special_requests, created_at=:created_at";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->hotel_id = htmlspecialchars(strip_tags($this->hotel_id));
        $this->room_id = htmlspecialchars(strip_tags($this->room_id));
        $this->check_in = htmlspecialchars(strip_tags($this->check_in));
        $this->check_out = htmlspecialchars(strip_tags($this->check_out));
        $this->status = 'pending';
        $this->total_price = htmlspecialchars(strip_tags($this->total_price));
        $this->guest_count = htmlspecialchars(strip_tags($this->guest_count));
        $this->special_requests = htmlspecialchars(strip_tags($this->special_requests));
        $this->created_at = date('Y-m-d H:i:s');

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":hotel_id", $this->hotel_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in", $this->check_in);
        $stmt->bindParam(":check_out", $this->check_out);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":guest_count", $this->guest_count);
        $stmt->bindParam(":special_requests", $this->special_requests);
        $stmt->bindParam(":created_at", $this->created_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Get all bookings for a user
    public function getByUserId($user_id) {
        try {
            $query = "SELECT 
                        b.*, 
                        h.name as hotel_name, 
                        r.type as room_type, 
                        r.price_per_night as room_price,
                        r.id as room_id,
                        h.id as hotel_id
                     FROM " . $this->table_name . " b
                     INNER JOIN hotels h ON b.hotel_id = h.id
                     INNER JOIN rooms r ON b.room_id = r.id
                     WHERE b.user_id = :user_id
                     ORDER BY b.check_in DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getByUserId: " . $e->getMessage());
            error_log("Query: " . $query);
            throw $e;
        }
    }

    // Get all bookings (for admin)
    public function getAllBookings() {
        try {
            $query = "SELECT
                        b.*,
                        h.name as hotel_name,
                        r.type as room_type,
                        r.price_per_night as room_price,
                        r.id as room_id,
                        h.id as hotel_id,
                        u.email as guest_email,
                        u.first_name as first_name,
                        u.last_name as last_name
                     FROM " . $this->table_name . " b
                     INNER JOIN hotels h ON b.hotel_id = h.id
                     INNER JOIN rooms r ON b.room_id = r.id
                     INNER JOIN users u ON b.user_id = u.id
                     ORDER BY b.check_in DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getAllBookings: " . $e->getMessage());
            error_log("Query: " . $query);
            throw $e;
        }
    }

    // Get booking by ID
    public function getById($id) {
        $query = "SELECT b.*, h.name as hotel_name, r.type as room_type, r.price_per_night as room_price
                 FROM " . $this->table_name . " b
                 JOIN hotels h ON b.hotel_id = h.id
                 JOIN rooms r ON b.room_id = r.id
                 WHERE b.id = ?
                 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->hotel_id = $row['hotel_id'];
            $this->room_id = $row['room_id'];
            $this->check_in = $row['check_in'];
            $this->check_out = $row['check_out'];
            $this->status = $row['status'];
            $this->price_per_night = $row['room_price'];
            $this->total_price = $row['total_price'];
            $this->guest_count = $row['guest_count'];
            $this->special_requests = $row['special_requests'];
            $this->created_at = $row['created_at'];

            return $row; // Return the row data
        }

        return false;
    }

    // Update booking status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                  SET status = :status
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Update booking details
    public function update() {
        // First check if the new room/dates are available
        if (!$this->checkAvailability($this->room_id, $this->check_in, $this->check_out, $this->id)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                 SET room_id=:room_id, check_in=:check_in, check_out=:check_out,
                     guest_count=:guest_count, special_requests=:special_requests
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->room_id = htmlspecialchars(strip_tags($this->room_id));
        $this->check_in = htmlspecialchars(strip_tags($this->check_in));
        $this->check_out = htmlspecialchars(strip_tags($this->check_out));
        $this->guest_count = htmlspecialchars(strip_tags($this->guest_count));
        $this->special_requests = htmlspecialchars(strip_tags($this->special_requests));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind values
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in", $this->check_in);
        $stmt->bindParam(":check_out", $this->check_out);
        $stmt->bindParam(":guest_count", $this->guest_count);
        $stmt->bindParam(":special_requests", $this->special_requests);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check room availability
    public function checkAvailability($room_id, $check_in, $check_out, $exclude_booking_id = null) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                 WHERE room_id = :room_id 
                 AND status != 'cancelled'
                 AND (
                    (check_in <= :check_in AND check_out >= :check_in) OR
                    (check_in <= :check_out AND check_out >= :check_out) OR
                    (check_in >= :check_in AND check_out <= :check_out)
                 )";
        
        if ($exclude_booking_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':room_id', $room_id);
        $stmt->bindParam(':check_in', $check_in);
        $stmt->bindParam(':check_out', $check_out);
        
        if ($exclude_booking_id) {
            $stmt->bindParam(':exclude_id', $exclude_booking_id);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] == 0;
    }
}
?>
