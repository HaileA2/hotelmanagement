<?php
// Migration: Create token_blacklist table
class CreateTokenBlacklistTable {
    public function up($db) {
        $query = "CREATE TABLE IF NOT EXISTS token_blacklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(500) NOT NULL,
            expires_at INT NOT NULL,
            blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token(191)),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $db->exec($query);
            echo "Migration successful: Created token_blacklist table\n";
        } catch (PDOException $e) {
            die("Migration failed: " . $e->getMessage());
        }
    }

    public function down($db) {
        $query = "DROP TABLE IF EXISTS token_blacklist";
        
        try {
            $db->exec($query);
            echo "Migration reverted: Dropped token_blacklist table\n";
        } catch (PDOException $e) {
            die("Migration rollback failed: " . $e->getMessage());
        }
    }
}

// Run migration if executed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $migration = new CreateTokenBlacklistTable();
    $migration->up($db);
}
