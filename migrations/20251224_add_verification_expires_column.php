<?php
// Migration: Add verification_expires column to users
// Date: 2025-12-24

class Migration_20251224_AddVerificationExpiresColumn {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function up() {
        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS=0;");

            // Add verification_expires if it doesn't exist
            $this->db->exec("ALTER TABLE `users` 
                ADD COLUMN IF NOT EXISTS `verification_expires` DATETIME NULL AFTER `verification_token`;");

            $this->db->exec("SET FOREIGN_KEY_CHECKS=1;");
            return true;
        } catch (PDOException $e) {
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    public function down() {
        try {
            $this->db->exec("ALTER TABLE `users` DROP COLUMN IF EXISTS `verification_expires`;");
            return true;
        } catch (PDOException $e) {
            error_log("Migration rollback failed: " . $e->getMessage());
            return false;
        }
    }
}

// Run the migration if executed directly
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $migration = new Migration_20251224_AddVerificationExpiresColumn($db);
    echo "Running migration 20251224_add_verification_expires_column...\n";
    if ($migration->up()) {
        echo "Migration completed successfully!\n";
        exit(0);
    } else {
        echo "Migration failed.\n";
        exit(1);
    }
}
