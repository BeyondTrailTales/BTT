<?php
/**
 * Database Migration Runner
 * 
 * Executes all pending migrations in the migrations directory
 */

// Load configuration
require_once dirname(dirname(__DIR__)) . '/app/config.php';

class MigrationRunner {
    private $db;
    private $migrationsPath;
    
    public function __construct() {
        $this->migrationsPath = __DIR__ . '/migrations';
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            // Use SQLite database
            $dbFile = BTT_SQLITE_PATH;
            
            // Create directory if it doesn't exist
            $dir = dirname($dbFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $this->db = new PDO('sqlite:' . $dbFile);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign keys
            $this->db->exec('PRAGMA foreign_keys = ON');
            
            // Create migrations table if it doesn't exist
            $this->createMigrationsTable();
            
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name TEXT UNIQUE NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($sql);
    }
    
    public function run() {
        echo "Starting migrations...\n";
        
        // Get all migration files
        $migrationFiles = glob($this->migrationsPath . '/*.php');
        
        if (empty($migrationFiles)) {
            echo "No migration files found.\n";
            return;
        }
        
        $executed = 0;
        $skipped = 0;
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');
            
            // Check if migration has already been applied
            if ($this->isMigrationApplied($migrationName)) {
                echo "Skipping: $migrationName (already applied)\n";
                $skipped++;
                continue;
            }
            
            // Include and run the migration
            echo "Running: $migrationName...";
            
            try {
                require_once $file;
                
                // Migration function name follows pattern: migration_YYYY_name
                $functionName = 'migration_' . $migrationName;
                
                if (!function_exists($functionName)) {
                    echo " ERROR: Function $functionName not found\n";
                    continue;
                }
                
                // Begin transaction
                $this->db->beginTransaction();
                
                // Run the migration
                $result = $functionName($this->db);
                
                if ($result === true) {
                    // Record successful migration
                    $this->recordMigration($migrationName);
                    $this->db->commit();
                    echo " SUCCESS\n";
                    $executed++;
                } else {
                    $this->db->rollBack();
                    echo " FAILED\n";
                }
                
            } catch (Exception $e) {
                $this->db->rollBack();
                echo " ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nMigration complete: $executed executed, $skipped skipped\n";
        
        // Show current database status
        $this->showDatabaseStatus();
    }
    
    private function isMigrationApplied($migrationName) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migrations WHERE migration_name = :name");
        $stmt->bindValue(':name', $migrationName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    private function recordMigration($migrationName) {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration_name) VALUES (:name)");
        $stmt->bindValue(':name', $migrationName, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    private function showDatabaseStatus() {
        echo "\nDatabase Tables:\n";
        
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            if ($table === 'sqlite_sequence') continue;
            
            $count = $this->db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "  - $table ($count rows)\n";
        }
    }
    
    public function reset() {
        echo "Resetting all migrations...\n";
        
        // Get all tables except migrations
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name != 'migrations' AND name != 'sqlite_sequence'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Drop all tables
        foreach ($tables as $table) {
            echo "Dropping table: $table\n";
            $this->db->exec("DROP TABLE IF EXISTS $table");
        }
        
        // Clear migrations table
        $this->db->exec("DELETE FROM migrations");
        
        echo "Reset complete.\n\n";
        
        // Run all migrations fresh
        $this->run();
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    
    // Check for command line arguments
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'reset':
            $runner->reset();
            break;
        case 'run':
        default:
            $runner->run();
            break;
    }
} else {
    // Web execution - return status
    try {
        $runner = new MigrationRunner();
        $runner->run();
        echo "<pre>Migrations completed successfully.</pre>";
    } catch (Exception $e) {
        echo "<pre>Migration error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
