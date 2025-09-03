<?php
/**
 * Migration: Gear Library with Default/User Separation
 * 
 * Creates tables for:
 * - gear_defaults: Immutable default gear items
 * - gear_user: User-created custom gear
 * - gear_preferences: User view mode preferences
 */

function migration_2025_gear_library($db) {
    try {
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
        
        // Create index on category for gear_defaults
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
        
        // Create unique index on user_id for gear_preferences
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_gear_preferences_user_id ON gear_preferences(user_id)");
        
        // Create migration tracking table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration_name TEXT UNIQUE NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        
        // Record this migration
        $stmt = $db->prepare("INSERT OR IGNORE INTO migrations (migration_name) VALUES (:name)");
        $stmt->bindValue(':name', '2025_gear_library', PDO::PARAM_STR);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Migration 2025_gear_library failed: " . $e->getMessage());
        return false;
    }
}
