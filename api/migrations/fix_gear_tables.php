<?php
/**
 * Fix gear tables structure
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';

$db = Database::getInstance();

if (!$db->isSQLite()) {
    echo "This migration is for SQLite only\n";
    exit(1);
}

try {
    $conn = $db->getConnection();
    
    echo "Fixing gear tables...\n\n";
    
    // Drop all dependent views first
    echo "Dropping dependent views...\n";
    $conn->exec("DROP VIEW IF EXISTS v_backpack_stats");
    $conn->exec("DROP VIEW IF EXISTS v_gear_usage");
    $conn->exec("DROP VIEW IF EXISTS v_trip_gear");
    echo "✓ Views dropped\n\n";
    
    // 1. Rename gear_items_new to gear_items if it exists
    $newTableExists = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='gear_items_new'")->fetchColumn();
    $oldTableExists = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='gear_items'")->fetchColumn();
    
    if ($newTableExists && !$oldTableExists) {
        echo "Renaming gear_items_new to gear_items...\n";
        $conn->exec("ALTER TABLE gear_items_new RENAME TO gear_items");
        echo "✓ Table renamed\n";
    } else if ($oldTableExists) {
        echo "✓ gear_items table already exists\n";
    } else {
        echo "Creating gear_items table...\n";
        $conn->exec("
            CREATE TABLE gear_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER DEFAULT NULL,
                name TEXT NOT NULL,
                weight REAL NOT NULL DEFAULT 0,
                category TEXT,
                brand TEXT,
                notes TEXT,
                price REAL DEFAULT 0,
                is_custom BOOLEAN DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Create indexes
        $conn->exec("CREATE INDEX idx_gear_items_user ON gear_items(user_id)");
        $conn->exec("CREATE INDEX idx_gear_items_category ON gear_items(category)");
        echo "✓ gear_items table created\n";
    }
    
    // 2. Fix backpack_gear table
    echo "\nFixing backpack_gear table...\n";
    
    // Check columns
    $columns = $conn->query("PRAGMA table_info(backpack_gear)")->fetchAll(PDO::FETCH_ASSOC);
    $hasAllColumns = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'custom_name') {
            $hasAllColumns = true;
            break;
        }
    }
    
    if (!$hasAllColumns) {
        echo "Recreating backpack_gear table with full structure...\n";
        
        // Backup existing data
        $conn->exec("DROP TABLE IF EXISTS backpack_gear_backup");
        $conn->exec("CREATE TABLE backpack_gear_backup AS SELECT * FROM backpack_gear");
        
        // Drop and recreate
        $conn->exec("DROP TABLE backpack_gear");
        $conn->exec("
            CREATE TABLE backpack_gear (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                backpack_id INTEGER NOT NULL,
                gear_id INTEGER,
                custom_name TEXT,
                custom_weight REAL,
                custom_category TEXT,
                custom_brand TEXT,
                custom_notes TEXT,
                custom_price REAL,
                quantity INTEGER NOT NULL DEFAULT 1,
                section TEXT DEFAULT 'main',
                worn BOOLEAN DEFAULT 0,
                consumable BOOLEAN DEFAULT 0,
                position INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE CASCADE,
                FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE SET NULL
            )
        ");
        
        // Restore data
        $conn->exec("
            INSERT INTO backpack_gear (backpack_id, gear_id, quantity, section)
            SELECT backpack_id, gear_id, quantity, section
            FROM backpack_gear_backup
            WHERE EXISTS (SELECT 1 FROM backpacks WHERE backpacks.id = backpack_gear_backup.backpack_id)
        ");
        
        $conn->exec("DROP TABLE backpack_gear_backup");
        
        // Create indexes
        $conn->exec("CREATE INDEX idx_backpack_gear_backpack ON backpack_gear(backpack_id)");
        $conn->exec("CREATE INDEX idx_backpack_gear_section ON backpack_gear(backpack_id, section)");
        
        echo "✓ backpack_gear table recreated\n";
    } else {
        echo "✓ backpack_gear table already has proper structure\n";
    }
    
    // 3. Add system gear items if needed
    $systemCount = $conn->query("SELECT COUNT(*) FROM gear_items WHERE user_id IS NULL")->fetchColumn();
    
    if ($systemCount == 0) {
        echo "\nAdding system gear items...\n";
        
        $systemGear = [
            // Basic items for testing
            ['name' => 'Sleeping Bag', 'weight' => 900, 'category' => 'sleep', 'brand' => 'Generic', 'price' => 200],
            ['name' => 'Tent', 'weight' => 1500, 'category' => 'shelter', 'brand' => 'Generic', 'price' => 300],
            ['name' => 'Sleeping Pad', 'weight' => 450, 'category' => 'sleep', 'brand' => 'Generic', 'price' => 100],
            ['name' => 'Backpack', 'weight' => 1800, 'category' => 'other', 'brand' => 'Generic', 'price' => 250],
            ['name' => 'Stove', 'weight' => 100, 'category' => 'cooking', 'brand' => 'Generic', 'price' => 50],
            ['name' => 'Pot', 'weight' => 150, 'category' => 'cooking', 'brand' => 'Generic', 'price' => 30],
            ['name' => 'Water Filter', 'weight' => 75, 'category' => 'water', 'brand' => 'Generic', 'price' => 40],
            ['name' => 'First Aid Kit', 'weight' => 250, 'category' => 'first-aid', 'brand' => 'Generic', 'price' => 35],
            ['name' => 'Headlamp', 'weight' => 85, 'category' => 'navigation', 'brand' => 'Generic', 'price' => 45],
            ['name' => 'Rain Jacket', 'weight' => 300, 'category' => 'clothing', 'brand' => 'Generic', 'price' => 150],
        ];
        
        $stmt = $conn->prepare("
            INSERT INTO gear_items (user_id, name, weight, category, brand, price, is_custom)
            VALUES (NULL, :name, :weight, :category, :brand, :price, 0)
        ");
        
        foreach ($systemGear as $item) {
            $stmt->execute($item);
        }
        
        echo "✓ Added " . count($systemGear) . " system gear items\n";
    } else {
        echo "\n✓ System gear items already exist ($systemCount items)\n";
    }
    
    echo "\n✅ All tables fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
