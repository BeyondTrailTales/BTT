<?php
/**
 * BeyondTrailTales Database Migration Runner
 * 
 * This script executes SQL migration files in order to update the database schema.
 * It tracks which migrations have been run to ensure idempotency.
 * 
 * Usage: php app/migrations/runner.php
 */

// Load application configuration
require_once dirname(dirname(__DIR__)) . '/app/config.php';

class MigrationRunner {
    private $db;
    private $migrationsPath;
    private $logFile;
    
    public function __construct() {
        $this->migrationsPath = __DIR__;
        $this->logFile = BTT_LOGS_PATH . '/migrations.log';
        
        try {
            // Connect to SQLite database
            $this->db = new PDO('sqlite:' . BTT_SQLITE_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign keys
            $this->db->exec('PRAGMA foreign_keys = ON');
            
            $this->log('Migration runner initialized');
        } catch (PDOException $e) {
            $this->log('Failed to connect to database: ' . $e->getMessage(), 'ERROR');
            die("Database connection failed. Check logs for details.\n");
        }
    }
    
    /**
     * Run all pending migrations
     */
    public function run() {
        $this->log('Starting migration run');
        
        // Ensure schema_migrations table exists
        $this->createMigrationsTable();
        
        // Get list of migration files
        $migrationFiles = $this->getMigrationFiles();
        
        if (empty($migrationFiles)) {
            echo "No migration files found.\n";
            $this->log('No migration files found');
            return;
        }
        
        // Get list of applied migrations
        $appliedMigrations = $this->getAppliedMigrations();
        
        // Run pending migrations
        $pendingCount = 0;
        foreach ($migrationFiles as $filename) {
            if (!in_array($filename, $appliedMigrations)) {
                $pendingCount++;
                $this->runMigration($filename);
            }
        }
        
        if ($pendingCount === 0) {
            echo "All migrations are up to date.\n";
            $this->log('All migrations are up to date');
        } else {
            echo "Successfully ran $pendingCount migration(s).\n";
            $this->log("Successfully ran $pendingCount migration(s)");
        }
    }
    
    /**
     * Create the schema_migrations table if it doesn't exist
     */
    private function createMigrationsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        try {
            $this->db->exec($sql);
            $this->log('Schema migrations table ready');
        } catch (PDOException $e) {
            $this->log('Failed to create migrations table: ' . $e->getMessage(), 'ERROR');
            die("Failed to create migrations table. Check logs for details.\n");
        }
    }
    
    /**
     * Get list of migration SQL files
     */
    private function getMigrationFiles() {
        $files = glob($this->migrationsPath . '/*.sql');
        $migrationFiles = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            // Only include numbered migration files (e.g., 0001_*.sql)
            if (preg_match('/^\d{4}_.*\.sql$/', $filename)) {
                $migrationFiles[] = $filename;
            }
        }
        
        // Sort files to ensure they run in order
        sort($migrationFiles);
        
        return $migrationFiles;
    }
    
    /**
     * Get list of already applied migrations
     */
    private function getAppliedMigrations() {
        try {
            $stmt = $this->db->query("SELECT filename FROM schema_migrations ORDER BY filename");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->log('Failed to get applied migrations: ' . $e->getMessage(), 'ERROR');
            return [];
        }
    }
    
    /**
     * Run a single migration file
     */
    private function runMigration($filename) {
        $filepath = $this->migrationsPath . '/' . $filename;
        
        echo "Running migration: $filename...\n";
        $this->log("Running migration: $filename");
        
        // Read the SQL file
        $sql = file_get_contents($filepath);
        if ($sql === false) {
            $this->log("Failed to read migration file: $filename", 'ERROR');
            echo "ERROR: Failed to read migration file: $filename\n";
            return;
        }
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Split SQL into individual statements (SQLite doesn't support multiple statements in one exec)
            // This is a simplified approach - for complex SQL, consider a more robust parser
            $statements = $this->splitSqlStatements($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            // Record that this migration has been applied
            $stmt = $this->db->prepare("INSERT INTO schema_migrations (filename) VALUES (:filename)");
            $stmt->execute(['filename' => $filename]);
            
            // Commit transaction
            $this->db->commit();
            
            echo "  ✓ Successfully applied $filename\n";
            $this->log("Successfully applied migration: $filename");
            
        } catch (PDOException $e) {
            // Rollback on error
            $this->db->rollback();
            
            $errorMsg = "Failed to apply migration $filename: " . $e->getMessage();
            $this->log($errorMsg, 'ERROR');
            echo "  ✗ ERROR: $errorMsg\n";
            
            // Stop on first error
            die("\nMigration failed. Database has been rolled back.\n");
        }
    }
    
    /**
     * Split SQL file into individual statements
     * This handles common SQL statement separators and comments
     */
    private function splitSqlStatements($sql) {
        // Remove SQL comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolons, but not within CREATE TRIGGER statements
        $statements = [];
        $current = '';
        $inTrigger = false;
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check for trigger start
            if (stripos($line, 'CREATE TRIGGER') !== false) {
                $inTrigger = true;
            }
            
            $current .= $line . "\n";
            
            // Check for statement end
            if (substr($line, -1) === ';') {
                if ($inTrigger) {
                    // Look for END; to close trigger
                    if (stripos($line, 'END;') !== false) {
                        $statements[] = trim($current);
                        $current = '';
                        $inTrigger = false;
                    }
                } else {
                    $statements[] = trim($current);
                    $current = '';
                }
            }
        }
        
        // Add any remaining statement
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return $statements;
    }
    
    /**
     * Log a message
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] [MIGRATION] $message" . PHP_EOL;
        @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Show status of migrations
     */
    public function status() {
        echo "\n=== Migration Status ===\n\n";
        
        $this->createMigrationsTable();
        
        $migrationFiles = $this->getMigrationFiles();
        $appliedMigrations = $this->getAppliedMigrations();
        
        if (empty($migrationFiles)) {
            echo "No migration files found.\n";
            return;
        }
        
        foreach ($migrationFiles as $filename) {
            if (in_array($filename, $appliedMigrations)) {
                echo "  ✓ $filename (applied)\n";
            } else {
                echo "  ○ $filename (pending)\n";
            }
        }
        
        $pending = count($migrationFiles) - count($appliedMigrations);
        echo "\nTotal: " . count($migrationFiles) . " migrations, $pending pending\n";
    }
    
    /**
     * Rollback the last migration (if supported)
     */
    public function rollback() {
        echo "Rollback functionality not yet implemented.\n";
        echo "Please restore from backup if needed.\n";
        $this->log('Rollback requested but not implemented');
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    
    // Check command line arguments
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'status':
            $runner->status();
            break;
            
        case 'rollback':
            $runner->rollback();
            break;
            
        case 'run':
        default:
            $runner->run();
            break;
    }
} else {
    die("This script must be run from the command line.\n");
}
