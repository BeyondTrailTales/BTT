<?php
/**
 * Gear Library Database Migration
 * Creates user_gear_items and user_gear_prefs tables
 * 
 * @version 2.0.0
 * @date 2025-09-03
 */

class GearLibraryMigration {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->logFile = dirname(dirname(dirname(__DIR__))) . '/storage/logs/migration_' . date('Y-m-d_His') . '.log';
        $this->log("Starting Gear Library Migration...");
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dbPath = dirname(dirname(dirname(__DIR__))) . '/storage/sqlite/btt.db';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log("Connected to database successfully");
            return true;
        } catch (PDOException $e) {
            $this->log("Database connection failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Run the migration
     */
    public function up() {
        if (!$this->connect()) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Create user_gear_items table
            $this->createUserGearItemsTable();
            
            // Create user_gear_prefs table
            $this->createUserGearPrefsTable();
            
            // Add indices for performance
            $this->createIndices();
            
            // Backfill default preferences for existing users
            $this->backfillUserPreferences();
            
            $this->db->commit();
            $this->log("Migration completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log("Migration failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Rollback the migration
     */
    public function down() {
        if (!$this->connect()) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Drop indices
            $this->db->exec("DROP INDEX IF EXISTS idx_user_gear_items_user_id");
            $this->db->exec("DROP INDEX IF EXISTS idx_user_gear_items_category");
            $this->db->exec("DROP INDEX IF EXISTS idx_user_gear_items_archived");
            $this->db->exec("DROP INDEX IF EXISTS idx_user_gear_prefs_user_id");
            
            // Drop tables
            $this->db->exec("DROP TABLE IF EXISTS user_gear_items");
            $this->db->exec("DROP TABLE IF EXISTS user_gear_prefs");
            
            $this->db->commit();
            $this->log("Rollback completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log("Rollback failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Create user_gear_items table
     */
    private function createUserGearItemsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_gear_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(50) NOT NULL,
                subcategory VARCHAR(50),
                weight_g DECIMAL(10,2) NOT NULL,
                qty INTEGER DEFAULT 1,
                tags TEXT, -- JSON array
                notes TEXT,
                icon VARCHAR(10),
                is_archived BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        $this->db->exec($sql);
        $this->log("Created user_gear_items table");
    }
    
    /**
     * Create user_gear_prefs table
     */
    private function createUserGearPrefsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_gear_prefs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                view_mode VARCHAR(20) DEFAULT 'both' CHECK(view_mode IN ('default', 'custom', 'both')),
                hidden_default_ids TEXT, -- JSON array
                preferred_units VARCHAR(10) DEFAULT 'g' CHECK(preferred_units IN ('g', 'oz')),
                last_sort VARCHAR(50) DEFAULT 'name_asc',
                filters TEXT, -- JSON object
                list_view VARCHAR(20) DEFAULT 'cards' CHECK(list_view IN ('cards', 'list')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        $this->db->exec($sql);
        $this->log("Created user_gear_prefs table");
    }
    
    /**
     * Create indices for performance
     */
    private function createIndices() {
        $indices = [
            "CREATE INDEX IF NOT EXISTS idx_user_gear_items_user_id ON user_gear_items(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_user_gear_items_category ON user_gear_items(category)",
            "CREATE INDEX IF NOT EXISTS idx_user_gear_items_archived ON user_gear_items(is_archived)",
            "CREATE INDEX IF NOT EXISTS idx_user_gear_prefs_user_id ON user_gear_prefs(user_id)"
        ];
        
        foreach ($indices as $index) {
            $this->db->exec($index);
        }
        
        $this->log("Created performance indices");
    }
    
    /**
     * Backfill default preferences for existing users
     */
    private function backfillUserPreferences() {
        // Check if users table exists
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$stmt->fetch()) {
            $this->log("Users table not found, skipping backfill");
            return;
        }
        
        // Get all existing users
        $stmt = $this->db->query("SELECT id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            $this->log("No existing users found, skipping backfill");
            return;
        }
        
        // Insert default preferences for each user
        $insertStmt = $this->db->prepare("
            INSERT OR IGNORE INTO user_gear_prefs (user_id, view_mode, preferred_units, hidden_default_ids, filters)
            VALUES (:user_id, 'both', 'g', '[]', '{}')
        ");
        
        foreach ($users as $user) {
            $insertStmt->execute(['user_id' => $user['id']]);
        }
        
        $this->log("Backfilled preferences for " . count($users) . " existing users");
    }
    
    /**
     * Log messages to file
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // Also echo to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Run migration if called from CLI
if (php_sapi_name() === 'cli') {
    $migration = new GearLibraryMigration();
    
    // Check for command line argument
    $action = isset($argv[1]) ? $argv[1] : 'up';
    
    switch ($action) {
        case 'up':
            echo "Running migration UP...\n";
            $result = $migration->up();
            exit($result ? 0 : 1);
            
        case 'down':
            echo "Running migration DOWN (rollback)...\n";
            $result = $migration->down();
            exit($result ? 0 : 1);
            
        default:
            echo "Usage: php 2025_gear_library.php [up|down]\n";
            exit(1);
    }
}
