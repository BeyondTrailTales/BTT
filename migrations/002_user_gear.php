<?php
/**
 * Migration: User Gear Tables
 * Creates tables for custom user gear and preferences
 */

// Get database connection
$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Create user_gear table
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_gear (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            weight_g DECIMAL(10,2) NOT NULL,
            notes TEXT,
            tags TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✓ Created user_gear table\n";
    
    // Create index on user_id
    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_user_gear_user_id 
        ON user_gear(user_id)
    ");
    
    echo "✓ Created user_gear indexes\n";
    
    // Create user_gear_preferences table
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_gear_preferences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE NOT NULL,
            view_mode VARCHAR(20) DEFAULT 'both',
            hidden_default_ids TEXT,
            preferred_units VARCHAR(10) DEFAULT 'g',
            last_sort VARCHAR(20) DEFAULT 'name',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "✓ Created user_gear_preferences table\n";
    
    // Insert default preferences for user 1 (for testing)
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM user_gear_preferences WHERE user_id = 1");
    $checkStmt->execute();
    if ($checkStmt->fetchColumn() == 0) {
        $db->exec("
            INSERT INTO user_gear_preferences (user_id, view_mode, hidden_default_ids, preferred_units, last_sort)
            VALUES (1, 'both', '[]', 'g', 'name')
        ");
        echo "✓ Created default preferences for user 1\n";
    }
    
    // Create trigger to update the updated_at timestamp
    $db->exec("
        CREATE TRIGGER IF NOT EXISTS update_user_gear_timestamp
        AFTER UPDATE ON user_gear
        FOR EACH ROW
        BEGIN
            UPDATE user_gear SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
        END
    ");
    
    $db->exec("
        CREATE TRIGGER IF NOT EXISTS update_user_gear_preferences_timestamp
        AFTER UPDATE ON user_gear_preferences
        FOR EACH ROW
        BEGIN
            UPDATE user_gear_preferences SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
        END
    ");
    
    echo "✓ Created update triggers\n";
    
    // Commit transaction
    $db->commit();
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback on error
    $db->rollback();
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
