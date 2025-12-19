<?php
// Migration: Add missing functionality
// Date: 2023-12-07

class Migration_20231207_AddMissingFunctionality {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    public function up() {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // 1. Create amenities table
            $this->createAmenitiesTable();
            
            // 2. Create services table
            $this->createServicesTable();
            
            // 3. Create reviews table
            $this->createReviewsTable();
            
            // 4. Create audit_logs table
            $this->createAuditLogsTable();
            
            // 5. Insert default amenities
            $this->insertDefaultAmenities();
            
            // Commit transaction
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function createAmenitiesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `amenities` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `icon` VARCHAR(50) NULL,
            `description` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `name_UNIQUE` (`name` ASC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
        
        // Create hotel_amenities junction table
        $sql = "CREATE TABLE IF NOT EXISTS `hotel_amenities` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `hotel_id` INT UNSIGNED NOT NULL,
            `amenity_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `hotel_amenity_unique` (`hotel_id` ASC, `amenity_id` ASC),
            CONSTRAINT `fk_hotel_amenity_hotel`
                FOREIGN KEY (`hotel_id`)
                REFERENCES `hotels` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_hotel_amenity_amenity`
                FOREIGN KEY (`amenity_id`)
                REFERENCES `amenities` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
    }
    
    private function createServicesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `services` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `duration_minutes` INT UNSIGNED NULL COMMENT 'Service duration in minutes',
            `is_available` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_service_created_by` (`created_by` ASC),
            CONSTRAINT `fk_service_created_by`
                FOREIGN KEY (`created_by`)
                REFERENCES `users` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
        
        // Create booking_services junction table
        $sql = "CREATE TABLE IF NOT EXISTS `booking_services` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_id` INT UNSIGNED NOT NULL,
            `service_id` INT UNSIGNED NOT NULL,
            `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(10,2) NOT NULL,
            `total_price` DECIMAL(10,2) NOT NULL,
            `scheduled_time` DATETIME NULL,
            `notes` TEXT NULL,
            `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_booking_service_booking` (`booking_id` ASC),
            INDEX `idx_booking_service_service` (`service_id` ASC),
            CONSTRAINT `fk_booking_service_booking`
                FOREIGN KEY (`booking_id`)
                REFERENCES `bookings` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_booking_service_service`
                FOREIGN KEY (`service_id`)
                REFERENCES `services` (`id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
    }
    
    private function createReviewsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `reviews` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `hotel_id` INT UNSIGNED NOT NULL,
            `room_id` INT UNSIGNED NULL,
            `rating` TINYINT UNSIGNED NOT NULL COMMENT 'Rating from 1 to 5',
            `title` VARCHAR(255) NULL,
            `comment` TEXT NULL,
            `staff_rating` TINYINT UNSIGNED NULL,
            `cleanliness_rating` TINYINT UNSIGNED NULL,
            `comfort_rating` TINYINT UNSIGNED NULL,
            `location_rating` TINYINT UNSIGNED NULL,
            `facilities_rating` TINYINT UNSIGNED NULL,
            `value_for_money_rating` TINYINT UNSIGNED NULL,
            `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `unique_booking_review` (`booking_id` ASC),
            INDEX `idx_review_user` (`user_id` ASC),
            INDEX `idx_review_hotel` (`hotel_id` ASC),
            INDEX `idx_review_room` (`room_id` ASC),
            CONSTRAINT `fk_review_booking`
                FOREIGN KEY (`booking_id`)
                REFERENCES `bookings` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_review_user`
                FOREIGN KEY (`user_id`)
                REFERENCES `users` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_review_hotel`
                FOREIGN KEY (`hotel_id`)
                REFERENCES `hotels` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_review_room`
                FOREIGN KEY (`room_id`)
                REFERENCES `rooms` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
    }
    
    private function createAuditLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NULL,
            `action` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, login, etc.',
            `table_name` VARCHAR(50) NOT NULL,
            `record_id` VARCHAR(50) NOT NULL,
            `old_values` JSON NULL,
            `new_values` JSON NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_audit_user` (`user_id` ASC),
            INDEX `idx_audit_table` (`table_name` ASC, `record_id` ASC),
            INDEX `idx_audit_created` (`created_at` ASC),
            CONSTRAINT `fk_audit_user`
                FOREIGN KEY (`user_id`)
                REFERENCES `users` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->exec($sql);
    }
    
    private function insertDefaultAmenities() {
        $amenities = [
            ['Free WiFi', 'wifi', 'High-speed internet access'],
            ['Swimming Pool', 'pool', 'Outdoor swimming pool'],
            ['Restaurant', 'restaurant', 'On-site restaurant'],
            ['Air Conditioning', 'ac_unit', 'Air conditioned rooms'],
            ['Parking', 'local_parking', 'Free parking available'],
            ['Spa', 'spa', 'Full-service spa'],
            ['Fitness Center', 'fitness_center', '24/7 fitness center'],
            ['Room Service', 'room_service', '24-hour room service'],
            ['Airport Shuttle', 'airport_shuttle', 'Complimentary airport shuttle'],
            ['Business Center', 'business_center', 'Business facilities'],
            ['Laundry Service', 'local_laundry_service', 'Laundry and dry cleaning'],
            ['Pet Friendly', 'pets', 'Pets allowed'],
            ['Bar/Lounge', 'sports_bar', 'On-site bar and lounge'],
            ['Concierge', 'support_agent', '24-hour front desk'],
            ['Non-smoking Rooms', 'smoke_free', 'Non-smoking rooms available']
        ];
        
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO `amenities` (`name`, `icon`, `description`) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($amenities as $amenity) {
            $stmt->execute($amenity);
        }
    }
    
    public function down() {
        // This would contain the SQL to rollback the changes
        // Not implemented as it's complex to safely rollback all changes
        return true;
    }
}

// Run the migration if this file is executed directly
if (php_sapi_name() === 'cli' && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $migration = new Migration_20231207_AddMissingFunctionality($db);
    
    echo "Starting database migration...\n";
    if ($migration->up()) {
        echo "Migration completed successfully!\n";
        exit(0);
    } else {
        echo "Migration failed. Check error logs for details.\n";
        exit(1);
    }
}
?>
