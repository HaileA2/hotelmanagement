<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Migration 1: Create audit_logs table
    $auditLogsSql = file_get_contents(__DIR__ . '/migrations/20231216_add_audit_logs_table.sql');
    $db->exec($auditLogsSql);
    
    // Migration 2: Create amenities and hotel_amenities tables
    $amenitiesSql = file_get_contents(__DIR__ . '/migrations/20231216_add_amenities_table.sql');
    $db->exec($amenitiesSql);
    
    // Commit transaction
    $db->commit();
    
    echo "Migrations executed successfully!\n";
    echo "- Created audit_logs table\n";
    echo "- Created amenities table\n";
    echo "- Created hotel_amenities table\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error running migrations: " . $e->getMessage() . "\n";
}
?>
