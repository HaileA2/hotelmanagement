<?php
// Load configuration
require_once __DIR__ . '/config/database.php';

// Include the migration file
require_once __DIR__ . '/migrations/20231207_add_missing_functionality.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Run the migration
    $migration = new Migration_20231207_AddMissingFunctionality($db);
    
    echo "Starting database migration...\n";
    
    if ($migration->up()) {
        echo "Migration completed successfully!\n";
        exit(0);
    } else {
        throw new Exception("Migration failed. Check error logs for details.");
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
