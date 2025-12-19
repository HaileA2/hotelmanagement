<?php
class TokenBlacklist {
    private $conn;
    private $table_name = "token_blacklist";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add token to blacklist
    public function add($token, $expires_at) {
        $query = "INSERT INTO " . $this->table_name . " (token, expires_at) VALUES (:token, :expires_at)";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $token = htmlspecialchars(strip_tags($token));
        
        // Bind values
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires_at);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if token is blacklisted
    public function isBlacklisted($token) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE token = :token AND expires_at > UNIX_TIMESTAMP() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $token = htmlspecialchars(strip_tags($token));
        
        // Bind value
        $stmt->bindParam(":token", $token);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Clean up expired tokens
    public function cleanup() {
        $query = "DELETE FROM " . $this->table_name . " WHERE expires_at <= UNIX_TIMESTAMP()";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
