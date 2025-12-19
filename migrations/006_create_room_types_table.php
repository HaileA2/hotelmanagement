<?php
// Migration: Create room_types table
class CreateRoomTypesTable {
    public function up($db) {
        $query = "
        CREATE TABLE IF NOT EXISTS room_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            base_price DECIMAL(10,2) NOT NULL,
            max_occupancy INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_room_type_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $db->exec($query);
            echo "Migration successful: Created room_types table\n";
            
            // Insert default room types if table is empty
            $this->seedDefaultRoomTypes($db);
            
        } catch (PDOException $e) {
            die("Migration failed: " . $e->getMessage());
        }
    }

    private function seedDefaultRoomTypes($db) {
        $check = $db->query("SELECT COUNT(*) as count FROM room_types")->fetch();
        if ($check['count'] > 0) return;

        $defaultTypes = [
            ['name' => 'Standard', 'description' => 'Standard room with basic amenities', 'base_price' => 100.00, 'max_occupancy' => 2],
            ['name' => 'Deluxe', 'description' => 'Spacious room with premium amenities', 'base_price' => 180.00, 'max_occupancy' => 3],
            ['name' => 'Suite', 'description' => 'Luxurious suite with separate living area', 'base_price' => 300.00, 'max_occupancy' => 4],
            ['name' => 'Family', 'description' => 'Large room suitable for families', 'base_price' => 250.00, 'max_occupancy' => 5],
            ['name' => 'Executive', 'description' => 'Business class room with work desk', 'base_price' => 220.00, 'max_occupancy' => 2]
        ];

        $stmt = $db->prepare("INSERT INTO room_types (name, description, base_price, max_occupancy) VALUES (:name, :description, :base_price, :max_occupancy)");
        
        foreach ($defaultTypes as $type) {
            $stmt->execute($type);
        }
        
        echo "Added default room types\n";
    }

    public function down($db) {
        // First drop foreign key constraints from rooms table
        try {
            $db->exec("ALTER TABLE rooms DROP FOREIGN KEY IF EXISTS fk_room_type");
            echo "Dropped foreign key constraint from rooms table\n";
        } catch (PDOException $e) {
            echo "Warning: Could not drop foreign key: " . $e->getMessage() . "\n";
        }
        
        // Then drop the room_types table
        $query = "DROP TABLE IF EXISTS room_types";
        
        try {
            $db->exec($query);
            echo "Migration reverted: Dropped room_types table\n";
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
    
    $migration = new CreateRoomTypesTable();
    $migration->up($db);
}
