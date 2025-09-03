<?php
/**
 * Run Database Migrations
 * Creates SQLite database and runs all pending migrations
 */

// Load configuration
require_once dirname(__DIR__) . '/app/config.php';

// Database path
$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$dbDir = dirname($dbPath);

// Create directory if it doesn't exist
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0777, true);
    echo "Created directory: $dbDir\n";
}

try {
    // Connect to SQLite database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: $dbPath\n";
    
    // Enable foreign keys
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Check if migrations table exists
    $tableExists = false;
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM migrations");
        $tableExists = true;
    } catch (PDOException $e) {
        // Table doesn't exist
        $tableExists = false;
    }
    
    $executed = [];
    
    if (!$tableExists) {
        echo "Creating migrations table...\n";
        // Run the initial migration directly
        $initMigration = dirname(__DIR__) . '/app/migrations/001_init.sql';
        if (file_exists($initMigration)) {
            $sql = file_get_contents($initMigration);
            // Remove the INSERT statement since the table doesn't exist yet
            $sql = preg_replace('/INSERT INTO migrations.*$/m', '', $sql);
            $pdo->exec($sql);
            
            // Now insert the migration record
            $pdo->exec("INSERT INTO migrations (filename) VALUES ('001_init.sql')");
            echo "Executed: 001_init.sql\n";
            $executed[] = '001_init.sql';  // Mark as executed
        }
    } else {
        // Get executed migrations (only if table exists)
        $stmt = $pdo->query('SELECT filename FROM migrations');
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get all migration files
    $migrationsPath = dirname(__DIR__) . '/app/migrations';
    $migrations = glob($migrationsPath . '/*.sql');
    sort($migrations);
    
    $count = 0;
    foreach ($migrations as $migration) {
        $filename = basename($migration);
        
        if (!in_array($filename, $executed)) {
            echo "Running migration: $filename\n";
            
            $sql = file_get_contents($migration);
            // Remove the INSERT statement as we'll do it separately
            $sql = preg_replace('/INSERT INTO migrations.*$/m', '', $sql);
            
            $pdo->exec($sql);
            
            // Record migration
            $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $stmt->execute([$filename]);
            
            $count++;
            echo "Completed: $filename\n";
        }
    }
    
    if ($count === 0) {
        echo "No pending migrations.\n";
    } else {
        echo "\nExecuted $count migration(s) successfully.\n";
    }
    
    // Display table information
    echo "\nDatabase tables:\n";
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  - " . $row['name'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
