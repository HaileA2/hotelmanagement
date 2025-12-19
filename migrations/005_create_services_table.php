<?php
// Migration: Create services table
class CreateServicesTable {
    public function up($db) {
        $query = "
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            service_type ENUM('restaurant', 'spa', 'tour', 'transport', 'other') NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            is_available TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
            INDEX idx_service_hotel (hotel_id),
            INDEX idx_service_type (service_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $db->exec($query);
            echo "Migration successful: Created services table\n";
        } catch (PDOException $e) {
            die("Migration failed: " . $e->getMessage());
        }
    }

    public function down($db) {
        $query = "DROP TABLE IF EXISTS services";
        
        try {
            $db->exec($query);
            echo "Migration reverted: Dropped services table\n";
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
    
    $migration = new CreateServicesTable();
    $migration->up($db);
}
