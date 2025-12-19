<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $role; // 'Admin', 'Manager', 'Customer'
    public $professional_details; // JSON string for professional information
    public $email_verified = false;
    public $verification_token;
    public $verification_expires;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Generate verification token
        $this->verification_token = bin2hex(random_bytes(32));
        $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO " . $this->table_name . " 
                 SET email = :email, 
                     password = :password, 
                     first_name = :first_name,
                     last_name = :last_name,
                     role = :role,
                     professional_details = :professional_details,
                     verification_token = :verification_token,
                     verification_expires = :verification_expires,
                     email_verified = :email_verified,
                     created_at = NOW(),
                     updated_at = NOW()";

        $stmt = $this->conn->prepare($query);

        // Validate role
        $valid_roles = ['Admin', 'Manager', 'Customer'];
        if (!in_array($this->role, $valid_roles)) {
            throw new Exception('Invalid role specified. Must be one of: ' . implode(', ', $valid_roles));
        }

        // Sanitize
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->first_name = $this->sanitizeName($this->first_name);
        $this->last_name = $this->sanitizeName($this->last_name);
        $this->role = ucfirst(strtolower($this->role)); // Ensure proper case
        
        // Validate professional details for managers and tour guides
        if (in_array($this->role, ['Manager', 'TourGuide'])) {
            if (empty($this->professional_details)) {
                throw new Exception('Professional details are required for ' . $this->role . ' role');
            }
            if (!is_string($this->professional_details)) {
                $this->professional_details = json_encode($this->professional_details, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $this->professional_details = null;
        }

        // Validate password
        if (empty($this->password)) {
            throw new Exception('Password cannot be empty');
        }

        // Hash the password with a strong algorithm
        $this->password = password_hash(
            $this->password, 
            PASSWORD_BCRYPT, 
            ['cost' => 12]
        );

        if ($this->password === false) {
            throw new Exception('Password hashing failed');
        }

        // Bind values
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":professional_details", $this->professional_details);
        $stmt->bindParam(":verification_token", $this->verification_token);
        $stmt->bindParam(":verification_expires", $verification_expires);
        $email_verified_int = $this->email_verified ? 1 : 0;
        $stmt->bindParam(":email_verified", $email_verified_int, PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        $error = $stmt->errorInfo();
        throw new Exception('Database error: ' . ($error[2] ?? 'Unknown error'));
    }

    public function emailExists() {
        $query = "SELECT id, email, password, first_name, last_name, role, created_at 
                 FROM " . $this->table_name . " 
                 WHERE email = ? 
                 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $stmt->bindParam(1, $this->email);
        
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception('Database error: ' . ($error[2] ?? 'Unknown error'));
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            
            // Verify the password hash format
            if (!empty($this->password) && 
                !preg_match('/^\$2[ayb]\$.{56}$/', $this->password)) {
                error_log("Warning: User {$this->id} has an invalid password hash format");
            }
            
            return true;
        }
        
        return false;
    }

    // Helper method to sanitize names
    private function sanitizeName($name) {
        $name = trim(htmlspecialchars(strip_tags($name)));
        // Remove any non-letter characters except spaces, hyphens, and apostrophes
        return preg_replace("/[^\p{L} '-]/", '', $name);
    }

    // Verify user's email using token
    public function verifyEmail($token) {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE verification_token = :token 
                 AND verification_expires > NOW()
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $query = "UPDATE " . $this->table_name . " 
                     SET email_verified = 1, 
                         verification_token = NULL,
                         verification_expires = NULL,
                         updated_at = NOW()
                     WHERE verification_token = :token";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            
            return $stmt->execute();
        }
        
        return false;
    }

    public function read() {
        $query = "SELECT id, email, first_name, last_name, role, professional_details, 
                         email_verified, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT id, email, first_name, last_name, role, professional_details, 
                        email_verified, created_at, updated_at 
                 FROM " . $this->table_name . " 
                 WHERE id = ? 
                 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->role = $row['role'];
            $this->professional_details = $row['professional_details'];
            $this->email_verified = (bool)$row['email_verified'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
}
?>
