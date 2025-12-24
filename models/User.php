// models/User.php (to be created)
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password;
    public $role;
    public $first_name;
    public $last_name;
    public $phone;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Other existing methods...

    public function isAdmin() {
        return $this->role === 'admin';
    }

    public function isManager() {
        return $this->role === 'manager';
    }

    public function isCustomer() {
        return $this->role === 'customer';
    }

    public function updateProfile() {
        // Implementation for updating user profile
    }

    public static function getAllUsers($db, $role = null) {
        // Implementation for getting all users with optional role filter
    }
}
```__