<?php
/**
 * Migration: Add Trip Packing List Support
 * 
 * Creates the trip_packed_items table to track which items from a backpack
 * have been packed for a specific trip, plus custom items.
 * 
 * @version 1.0.0
 */

require_once dirname(__DIR__, 2) . '/api/config.php';
require_once dirname(__DIR__, 2) . '/api/classes/Database.php';

function runTripPackingMigration() {
    try {
        $db = Database::getInstance();
        
        if (!$db->isSQLite()) {
            return [
                'success' => true,
                'message' => 'Using JSON storage - no migration needed'
            ];
        }
        
        $conn = $db->getConnection();
        
        // Check if table already exists
        $tableCheck = $conn->query("
            SELECT name FROM sqlite_master 
            WHERE type='table' AND name='trip_packed_items'
        ");
        
        if ($tableCheck->fetch()) {
            return [
                'success' => true,
                'message' => 'Table trip_packed_items already exists'
            ];
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Create the trip_packed_items table
        $sql = "
            CREATE TABLE trip_packed_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                trip_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                gear_id INTEGER NULL,
                is_custom INTEGER NOT NULL DEFAULT 0,
                item_name TEXT NULL,
                category TEXT DEFAULT 'main' CHECK(category IN ('main', 'lid', 'pockets', 'external')),
                is_packed INTEGER NOT NULL DEFAULT 0,
                quantity INTEGER NOT NULL DEFAULT 1 CHECK(quantity > 0 AND quantity <= 999),
                notes TEXT NULL,
                sort_order INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                
                -- Constraints
                FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE,
                
                -- Ensure gear items are unique per trip
                UNIQUE(trip_id, gear_id),
                
                -- Validation constraints
                CHECK (
                    (is_custom = 0 AND gear_id IS NOT NULL AND item_name IS NULL) OR
                    (is_custom = 1 AND gear_id IS NULL AND item_name IS NOT NULL)
                ),
                CHECK (length(item_name) <= 120)
            )
        ";
        
        $conn->exec($sql);
        
        // Create indexes for better performance
        $conn->exec("
            CREATE INDEX idx_trip_packed_trip_id 
            ON trip_packed_items(trip_id)
        ");
        
        $conn->exec("
            CREATE INDEX idx_trip_packed_user_id 
            ON trip_packed_items(user_id)
        ");
        
        $conn->exec("
            CREATE INDEX idx_trip_packed_custom 
            ON trip_packed_items(is_custom)
        ");
        
        $conn->exec("
            CREATE INDEX idx_trip_packed_gear_id 
            ON trip_packed_items(gear_id) 
            WHERE gear_id IS NOT NULL
        ");
        
        // Create trigger to update updated_at on changes
        $conn->exec("
            CREATE TRIGGER update_trip_packed_items_updated_at
            AFTER UPDATE ON trip_packed_items
            FOR EACH ROW
            BEGIN
                UPDATE trip_packed_items 
                SET updated_at = CURRENT_TIMESTAMP 
                WHERE id = NEW.id;
            END
        ");
        
        // Commit transaction
        $conn->commit();
        
        // Log the migration
        $logFile = dirname(__DIR__, 2) . '/storage/logs/migrations.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = date('Y-m-d H:i:s') . " - Trip packing migration completed successfully\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return [
            'success' => true,
            'message' => 'Trip packing table created successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollback();
        }
        
        // Log error
        error_log('Trip packing migration failed: ' . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ];
    }
}

// Run migration if called directly
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'add_trip_packing.php') {
    header('Content-Type: application/json');
    echo json_encode(runTripPackingMigration(), JSON_PRETTY_PRINT);
}
