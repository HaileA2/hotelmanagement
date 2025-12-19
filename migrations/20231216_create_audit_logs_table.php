<?php
class Migration_20231216_create_audit_logs_table {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        try {
            // Create audit_logs table
            $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `user_id` INT DEFAULT NULL,
                `action` VARCHAR(50) NOT NULL,
                `table_name_affected` VARCHAR(100) DEFAULT NULL,
                `record_id` VARCHAR(50) DEFAULT NULL,
                `old_values` JSON DEFAULT NULL,
                `new_values` JSON DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `user_agent` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_user_id` (`user_id`),
                KEY `idx_action` (`action`),
                KEY `idx_table_record` (`table_name_affected`, `record_id`),
                KEY `idx_created_at` (`created_at`),
                CONSTRAINT `fk_audit_logs_user`
                    FOREIGN KEY (`user_id`) 
                    REFERENCES `users` (`id`)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $this->db->exec($sql);
            
            // Create amenities and hotel_amenities tables in the same migration
            $amenitiesSql = file_get_contents(__DIR__ . '/20231216_add_amenities_table.sql');
            $this->db->exec($amenitiesSql);
            
            return true;
        } catch (PDOException $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function down() {
        // This would be used to rollback the migration if needed
        $this->db->exec("DROP TABLE IF EXISTS `audit_logs`");
        $this->db->exec("DROP TABLE IF EXISTS `hotel_amenities`");
        $this->db->exec("DROP TABLE IF EXISTS `amenities`");
        return true;
    }
}
