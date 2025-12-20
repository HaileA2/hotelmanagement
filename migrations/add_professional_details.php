<?php
// Migration: Add professional_details column to users table
// Date: 2025-12-20

class Migration_AddProfessionalDetails {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function up() {
        try {
            $this->db->exec("
                ALTER TABLE `users`
                ADD COLUMN `professional_details` TEXT NULL AFTER `role`
            ");
            return true;
        } catch (PDOException $e) {
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    public function down() {
        try {
            $this->db->exec("
                ALTER TABLE `users`
                DROP COLUMN `professional_details`
            ");
            return true;
        } catch (PDOException $e) {
            error_log("Migration rollback failed: " . $e->getMessage());
            return false;
        }
    }
}

// Run the migration if this file is executed directly
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    $migration = new Migration_AddProfessionalDetails($db);

    echo "Starting migration...\n";
    if ($migration->up()) {
        echo "Migration completed successfully!\n";
        exit(0);
    } else {
        echo "Migration failed. Check error logs for details.\n";
        exit(1);
    }
}
?>