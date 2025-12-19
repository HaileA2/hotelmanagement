<?php
// Migration: Add missing tables and columns
// Date: 2023-12-06

class Migration_20231206_AddMissingTablesAndColumns {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        try {
            // Enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS=0;");
            
            // 1. Update users table
            $this->db->exec("
                ALTER TABLE `users` 
                ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `password`,
                ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `first_name`,
                ADD COLUMN `phone` VARCHAR(20) NULL AFTER `last_name`,
                ADD COLUMN `status` ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active' AFTER `role`,
                ADD COLUMN `profile_image` VARCHAR(255) NULL AFTER `phone`,
                ADD COLUMN `last_login` DATETIME NULL AFTER `status`,
                MODIFY COLUMN `role` ENUM('admin', 'manager', 'customer') NOT NULL DEFAULT 'customer';
            ");
            
            // 2. Create amenities table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `amenities` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `icon` VARCHAR(50) NULL,
                    `description` TEXT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `name_UNIQUE` (`name` ASC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
                CREATE TABLE IF NOT EXISTS `hotel_amenities` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 3. Update hotels table
            $this->db->exec("
                ALTER TABLE `hotels` 
                ADD COLUMN `contact_email` VARCHAR(255) NULL AFTER `location`,
                ADD COLUMN `contact_phone` VARCHAR(20) NULL AFTER `contact_email`,
                ADD COLUMN `check_in_time` TIME NULL DEFAULT '14:00:00' AFTER `contact_phone`,
                ADD COLUMN `check_out_time` TIME NULL DEFAULT '12:00:00' AFTER `check_in_time`,
                ADD COLUMN `status` ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active' AFTER `check_out_time`,
                ADD COLUMN `rating` DECIMAL(3,2) NULL DEFAULT 0 AFTER `status`,
                ADD COLUMN `total_reviews` INT UNSIGNED NULL DEFAULT 0 AFTER `rating`,
                ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `total_reviews`,
                ADD INDEX `idx_hotel_status` (`status` ASC);
                
                ALTER TABLE `hotels`
                ADD CONSTRAINT `fk_hotel_created_by`
                FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE;
            ");
            
            // 4. Update rooms table
            $this->db->exec("
                ALTER TABLE `rooms` 
                ADD COLUMN `room_number` VARCHAR(20) NOT NULL AFTER `hotel_id`,
                ADD COLUMN `floor` INT NULL AFTER `room_number`,
                ADD COLUMN `view_type` ENUM('city', 'garden', 'pool', 'sea', 'mountain') NULL AFTER `floor`,
                ADD COLUMN `bed_type` ENUM('single', 'double', 'queen', 'king', 'twin', 'suite') NULL AFTER `view_type`,
                ADD COLUMN `bathroom_type` ENUM('shared', 'private') NULL DEFAULT 'private' AFTER `bed_type`,
                ADD COLUMN `size` DECIMAL(6,2) NULL COMMENT 'Room size in square meters' AFTER `bathroom_type`,
                ADD COLUMN `max_adults` TINYINT UNSIGNED NULL DEFAULT 2 AFTER `size`,
                ADD COLUMN `max_children` TINYINT UNSIGNED NULL DEFAULT 1 AFTER `max_adults`,
                ADD COLUMN `is_smoking` TINYINT(1) NULL DEFAULT 0 AFTER `max_children`,
                ADD COLUMN `status` ENUM('available', 'booked', 'maintenance', 'cleaning') NOT NULL DEFAULT 'available' AFTER `is_smoking`,
                ADD COLUMN `description` TEXT NULL AFTER `status`,
                CHANGE COLUMN `price` `price_per_night` DECIMAL(10,2) NOT NULL,
                MODIFY COLUMN `type` ENUM('single', 'double', 'twin', 'deluxe', 'suite', 'family', 'executive') NOT NULL,
                ADD INDEX `idx_room_hotel` (`hotel_id` ASC, `status` ASC);
            ");
            
            // 5. Create room_images table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `room_images` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `room_id` INT UNSIGNED NOT NULL,
                    `image_url` VARCHAR(255) NOT NULL,
                    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                    `caption` VARCHAR(255) NULL,
                    `sort_order` INT UNSIGNED NULL DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_room_image_room` (`room_id` ASC),
                    CONSTRAINT `fk_room_image_room`
                        FOREIGN KEY (`room_id`)
                        REFERENCES `rooms` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 6. Update bookings table
            $this->db->exec("
                ALTER TABLE `bookings` 
                ADD COLUMN `guest_count` INT NULL AFTER `room_id`,
                ADD COLUMN `special_requests` TEXT NULL AFTER `guest_count`,
                ADD COLUMN `total_price` DECIMAL(10,2) NOT NULL AFTER `special_requests`,
                ADD COLUMN `payment_status` ENUM('pending', 'paid', 'refunded', 'partially_refunded', 'failed') NOT NULL DEFAULT 'pending' AFTER `total_price`,
                ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `payment_status`,
                ADD COLUMN `payment_reference` VARCHAR(100) NULL AFTER `payment_method`,
                ADD COLUMN `cancellation_reason` TEXT NULL AFTER `payment_reference`,
                ADD COLUMN `cancelled_by` INT UNSIGNED NULL AFTER `cancellation_reason`,
                ADD COLUMN `cancelled_at` DATETIME NULL AFTER `cancelled_by`,
                ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `cancelled_at`;
                
                ALTER TABLE `bookings`
                MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending',
                ADD INDEX `idx_booking_user` (`user_id` ASC, `status` ASC),
                ADD INDEX `idx_booking_hotel` (`hotel_id` ASC, `status` ASC),
                ADD INDEX `idx_booking_dates` (`check_in` ASC, `check_out` ASC);
                
                ALTER TABLE `bookings`
                ADD CONSTRAINT `fk_booking_created_by`
                FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_booking_cancelled_by`
                FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE;
            ");
            
            // 7. Create services table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `services` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 8. Create booking_services table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `booking_services` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 9. Create reviews table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `reviews` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 10. Create audit_logs table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `audit_logs` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // 11. Insert default amenities if not exists
            $this->db->exec("
                INSERT IGNORE INTO `amenities` (`name`, `icon`, `description`) VALUES
                ('Free WiFi', 'wifi', 'High-speed internet access'),
                ('Swimming Pool', 'pool', 'Outdoor swimming pool'),
                ('Restaurant', 'restaurant', 'On-site restaurant'),
                ('Air Conditioning', 'ac_unit', 'Air conditioned rooms'),
                ('Parking', 'local_parking', 'Free parking available'),
                ('Spa', 'spa', 'Full-service spa'),
                ('Fitness Center', 'fitness_center', '24/7 fitness center'),
                ('Room Service', 'room_service', '24-hour room service'),
                ('Airport Shuttle', 'airport_shuttle', 'Complimentary airport shuttle'),
                ('Business Center', 'business_center', 'Business facilities'),
                ('Laundry Service', 'local_laundry_service', 'Laundry and dry cleaning'),
                ('Pet Friendly', 'pets', 'Pets allowed'),
                ('Bar/Lounge', 'sports_bar', 'On-site bar and lounge'),
                ('Concierge', 'support_agent', '24-hour front desk'),
                ('Non-smoking Rooms', 'smoke_free', 'Non-smoking rooms available');
            
                -- Update admin user details if exists
                UPDATE `users` SET 
                    `first_name` = 'System',
                    `last_name` = 'Administrator',
                    `status` = 'active',
                    `role` = 'admin'
                WHERE `email` = 'admin@example.com';
            ");
            
            // 12. Enable foreign key checks
            $this->db->exec("SET FOREIGN_KEY_CHECKS=1;");
            
            return true;
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function down() {
        // This would contain the SQL to rollback the changes
        // Not implemented as it's complex to safely rollback all changes
        return true;
    }
}

// Run the migration if this file is executed directly
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $migration = new Migration_20231206_AddMissingTablesAndColumns($db);
    
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
