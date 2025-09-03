<?php
/**
 * Setup Gear Database Tables
 * Direct execution to create gear tables
 */

$dbFile = 'C:/xampp2/htdocs/BTT/storage/sqlite/btt.db';

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating gear tables...\n";
    
    // Create gear_defaults table
    $sql = "CREATE TABLE IF NOT EXISTS gear_defaults (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        weight_g INTEGER,
        unit TEXT DEFAULT 'g',
        icon TEXT,
        description TEXT,
        brand TEXT,
        price REAL,
        is_active BOOLEAN DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✓ gear_defaults table created\n";
    
    // Create indexes for gear_defaults
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gear_defaults_category ON gear_defaults(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gear_defaults_active ON gear_defaults(is_active)");
    
    // Create gear_user table
    $sql = "CREATE TABLE IF NOT EXISTS gear_user (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER DEFAULT 0,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        weight_g INTEGER,
        unit TEXT DEFAULT 'g',
        icon TEXT,
        description TEXT,
        brand TEXT,
        price REAL,
        quantity_owned INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✓ gear_user table created\n";
    
    // Create indexes for gear_user
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gear_user_user_id ON gear_user(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gear_user_category ON gear_user(user_id, category)");
    
    // Create gear_preferences table
    $sql = "CREATE TABLE IF NOT EXISTS gear_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        view_mode TEXT DEFAULT 'all' CHECK(view_mode IN ('all', 'custom_only', 'default_only')),
        show_weights_in TEXT DEFAULT 'g' CHECK(show_weights_in IN ('g', 'oz', 'lb', 'kg')),
        sort_by TEXT DEFAULT 'category' CHECK(sort_by IN ('name', 'category', 'weight', 'price')),
        sort_order TEXT DEFAULT 'asc' CHECK(sort_order IN ('asc', 'desc')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "✓ gear_preferences table created\n";
    
    // Create unique index on user_id for gear_preferences
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_gear_preferences_user_id ON gear_preferences(user_id)");
    
    echo "\nAll gear tables created successfully!\n";
    
    // Show table info
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'gear_%' ORDER BY name");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nGear tables in database:\n";
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  - $table ($count rows)\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
