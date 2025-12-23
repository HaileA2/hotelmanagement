<?php
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

public function readAll() {
    $query = "SELECT id, first_name, last_name, email, role, created_at 
              FROM " . $this->table_name . " 
              ORDER BY created_at DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    
    return $stmt;
}
}

