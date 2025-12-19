<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Include all migration files
$migrationFiles = [
    '20231207_add_missing_functionality',
    '004_create_token_blacklist_table',
    '005_create_services_table',
    '006_create_room_types_table',
    '007_create_room_images_table',
    '20231206_add_missing_tables_and_columns',
    '20231216_create_audit_logs_table'
];

// Create database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Create migrations table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `batch` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Get already run migrations
    $stmt = $db->query("SELECT migration FROM migrations");
    $runMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get the next batch number
    $batch = $db->query("SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations")->fetchColumn();

    // Run new migrations
    foreach ($migrationFiles as $migration) {
        if (!in_array($migration, $runMigrations)) {
            $migrationClass = 'Migration_' . str_replace('-', '_', $migration);
            $migrationFile = __DIR__ . "/migrations/{$migration}.php";
            
            if (file_exists($migrationFile)) {
                include_once $migrationFile;
                $migrationInstance = new $migrationClass($db);
                
                echo "Running migration: {$migration}\n";
                if ($migrationInstance->up()) {
                    // Record the migration
                    $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                    $stmt->execute([$migration, $batch]);
                    echo "✅ Successfully ran migration: {$migration}\n";
                } else {
                    throw new Exception("Failed to run migration: {$migration}");
                }
            }
        }
    }

    echo "\nAll migrations completed successfully!\n";

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    exit(1);
}
