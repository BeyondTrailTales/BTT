<?php
/**
 * Migration to update gear_items table for per-user support
 * and ensure backpack_gear table is properly structured
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';

function migrateGearItemsPerUser() {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        echo "This migration is for SQLite only\n";
        return false;
    }
    
    try {
        $conn = $db->getConnection();
        
        echo "Starting migration for per-user gear items...\n";
        
        // 1. Check if gear_items table needs user_id column
        $columns = $conn->query("PRAGMA table_info(gear_items)")->fetchAll(PDO::FETCH_ASSOC);
        $hasUserId = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'user_id') {
                $hasUserId = true;
                break;
            }
        }
        
        if (!$hasUserId) {
            echo "Adding user_id column to gear_items table...\n";
            
            // Drop dependent view first
            $conn->exec("DROP VIEW IF EXISTS v_backpack_stats");
            
            // Clean up any leftover tables
            $conn->exec("DROP TABLE IF EXISTS gear_items_new");
            
            // Create new table with user_id
            $conn->exec("
                CREATE TABLE gear_items_new (
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
            
            // Copy existing data (mark as system items with user_id = NULL)
            $conn->exec("
                INSERT INTO gear_items_new (id, user_id, name, weight, category, brand, notes, price, is_custom, created_at)
                SELECT id, NULL, name, weight, category, brand, notes, price, 0, created_at
                FROM gear_items
            ");
            
            // Drop old table and rename new one
            $conn->exec("DROP TABLE gear_items");
            $conn->exec("ALTER TABLE gear_items_new RENAME TO gear_items");
            
            // Create index for better performance
            $conn->exec("CREATE INDEX idx_gear_items_user ON gear_items(user_id)");
            $conn->exec("CREATE INDEX idx_gear_items_category ON gear_items(category)");
            
            echo "✓ gear_items table updated with user_id support\n";
        } else {
            echo "✓ gear_items table already has user_id column\n";
        }
        
        // 2. Check and recreate backpack_gear table with proper structure
        echo "Checking backpack_gear table structure...\n";
        
        // Drop and recreate backpack_gear for proper foreign keys
        $conn->exec("DROP TABLE IF EXISTS backpack_gear_old");
        $conn->exec("ALTER TABLE backpack_gear RENAME TO backpack_gear_old");
        
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
        
        // Copy existing data if any
        $conn->exec("
            INSERT INTO backpack_gear (backpack_id, gear_id, quantity, section)
            SELECT backpack_id, gear_id, quantity, section
            FROM backpack_gear_old
            WHERE EXISTS (SELECT 1 FROM backpacks WHERE backpacks.id = backpack_gear_old.backpack_id)
        ");
        
        $conn->exec("DROP TABLE IF EXISTS backpack_gear_old");
        
        // Create indexes
        $conn->exec("CREATE INDEX idx_backpack_gear_backpack ON backpack_gear(backpack_id)");
        $conn->exec("CREATE INDEX idx_backpack_gear_section ON backpack_gear(backpack_id, section)");
        
        echo "✓ backpack_gear table restructured\n";
        
        // 3. Create default system gear items (available to all users)
        echo "Creating default system gear items...\n";
        
        $systemGear = [
            // Shelter
            ['name' => 'Zpacks Duplex Tent', 'weight' => 538, 'category' => 'shelter', 'brand' => 'Zpacks', 'price' => 699],
            ['name' => 'Big Agnes Copper Spur UL2', 'weight' => 1190, 'category' => 'shelter', 'brand' => 'Big Agnes', 'price' => 450],
            ['name' => 'Hyperlite DCF Tarp', 'weight' => 240, 'category' => 'shelter', 'brand' => 'HMG', 'price' => 335],
            
            // Sleep System
            ['name' => 'EE Revelation 20°F Quilt', 'weight' => 570, 'category' => 'sleep', 'brand' => 'Enlightened Equipment', 'price' => 340],
            ['name' => 'NeoAir XLite Sleeping Pad', 'weight' => 340, 'category' => 'sleep', 'brand' => 'Thermarest', 'price' => 210],
            ['name' => 'Sea to Summit Aeros Pillow', 'weight' => 60, 'category' => 'sleep', 'brand' => 'Sea to Summit', 'price' => 39],
            
            // Cooking
            ['name' => 'BRS-3000T Stove', 'weight' => 25, 'category' => 'cooking', 'brand' => 'BRS', 'price' => 17],
            ['name' => 'MSR PocketRocket 2', 'weight' => 73, 'category' => 'cooking', 'brand' => 'MSR', 'price' => 48],
            ['name' => 'TOAKS Ti 550ml Pot', 'weight' => 65, 'category' => 'cooking', 'brand' => 'TOAKS', 'price' => 34],
            ['name' => 'Titanium Spork', 'weight' => 17, 'category' => 'cooking', 'brand' => 'TOAKS', 'price' => 12],
            
            // Water
            ['name' => 'Sawyer Mini Filter', 'weight' => 57, 'category' => 'water', 'brand' => 'Sawyer', 'price' => 24],
            ['name' => 'Sawyer Squeeze Filter', 'weight' => 85, 'category' => 'water', 'brand' => 'Sawyer', 'price' => 37],
            ['name' => 'Smart Water 1L Bottle', 'weight' => 35, 'category' => 'water', 'brand' => 'Smart Water', 'price' => 2],
            
            // Navigation
            ['name' => 'Nitecore NU25 Headlamp', 'weight' => 57, 'category' => 'navigation', 'brand' => 'Nitecore', 'price' => 39],
            ['name' => 'Petzl Bindi Headlamp', 'weight' => 35, 'category' => 'navigation', 'brand' => 'Petzl', 'price' => 60],
            ['name' => 'Garmin inReach Mini', 'weight' => 100, 'category' => 'navigation', 'brand' => 'Garmin', 'price' => 350],
            
            // Clothing
            ['name' => 'Frogg Toggs UL Rain Jacket', 'weight' => 155, 'category' => 'clothing', 'brand' => 'Frogg Toggs', 'price' => 25],
            ['name' => 'Ghost Whisperer Jacket', 'weight' => 230, 'category' => 'clothing', 'brand' => 'Mountain Hardwear', 'price' => 325],
            ['name' => 'Merino Wool Base Layer', 'weight' => 140, 'category' => 'clothing', 'brand' => 'Smartwool', 'price' => 85],
            
            // First Aid
            ['name' => 'First Aid Kit', 'weight' => 120, 'category' => 'first-aid', 'brand' => 'Adventure Medical', 'price' => 25],
            ['name' => 'Medications', 'weight' => 30, 'category' => 'first-aid', 'brand' => 'Various', 'price' => 15],
            ['name' => 'Leukotape', 'weight' => 20, 'category' => 'first-aid', 'brand' => 'BSN', 'price' => 8],
            
            // Electronics
            ['name' => 'Anker 10000mAh Battery', 'weight' => 180, 'category' => 'electronics', 'brand' => 'Anker', 'price' => 25],
            ['name' => 'USB-C Cable', 'weight' => 20, 'category' => 'electronics', 'brand' => 'Generic', 'price' => 10],
            
            // Other
            ['name' => 'Trekking Poles (pair)', 'weight' => 476, 'category' => 'other', 'brand' => 'CMT', 'price' => 30],
            ['name' => 'Swiss Army Knife', 'weight' => 58, 'category' => 'other', 'brand' => 'Victorinox', 'price' => 35],
            ['name' => 'Stuff Sack', 'weight' => 20, 'category' => 'other', 'brand' => 'Sea to Summit', 'price' => 12],
        ];
        
        // Check if system gear already exists
        $existingCount = $conn->query("SELECT COUNT(*) FROM gear_items WHERE user_id IS NULL")->fetchColumn();
        
        if ($existingCount == 0) {
            $stmt = $conn->prepare("
                INSERT INTO gear_items (user_id, name, weight, category, brand, price, is_custom)
                VALUES (NULL, :name, :weight, :category, :brand, :price, 0)
            ");
            
            foreach ($systemGear as $item) {
                $stmt->execute($item);
            }
            
            echo "✓ Added " . count($systemGear) . " system gear items\n";
        } else {
            echo "✓ System gear items already exist ($existingCount items)\n";
        }
        
        echo "\n✅ Migration completed successfully!\n";
        return true;
        
    } catch (Exception $e) {
        echo "❌ Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the migration
migrateGearItemsPerUser();
