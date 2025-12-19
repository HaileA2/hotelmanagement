<?php
// Migration: Create room_images table
class CreateRoomImagesTable {
    public function up($db) {
        $query = "
        CREATE TABLE IF NOT EXISTS room_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            caption VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
            INDEX idx_room_images_room (room_id),
            INDEX idx_room_primary_image (room_id, is_primary)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $db->exec($query);
            echo "Migration successful: Created room_images table\n";
        } catch (PDOException $e) {
            die("Migration failed: " . $e->getMessage());
        }
    }

    public function down($db) {
        $query = "DROP TABLE IF EXISTS room_images";
        
        try {
            $db->exec($query);
            echo "Migration reverted: Dropped room_images table\n";
        } catch (PDOException $e) {
            die("Migration rollback failed: " . $e->getMessage());
        }
    }
}

// Run migration if executed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    require_once '../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $migration = new CreateRoomImagesTable();
    $migration->up($db);
}
