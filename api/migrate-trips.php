<?php
/**
 * Database Migration Script
 * Adds new fields to existing trips table
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';

function migrateTripsTable() {
    try {
        $db = Database::getInstance();
        
        if (!$db->isSQLite()) {
            return [
                'success' => true,
                'message' => 'Using JSON storage - no migration required'
            ];
        }
        
        $conn = $db->getConnection();
        
        // Get current table columns
        $result = $conn->query("PRAGMA table_info(trips)");
        $existing_columns = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $row['name'];
        }
        
        // New columns to add
        $migrations = [
            // Default image fields
            ['name' => 'default_image_url', 'sql' => 'ALTER TABLE trips ADD COLUMN default_image_url TEXT'],
            ['name' => 'default_image_alt', 'sql' => 'ALTER TABLE trips ADD COLUMN default_image_alt TEXT'],
            
            // Trail metrics
            ['name' => 'elevation_loss', 'sql' => 'ALTER TABLE trips ADD COLUMN elevation_loss REAL DEFAULT 0'],
            ['name' => 'max_elevation', 'sql' => 'ALTER TABLE trips ADD COLUMN max_elevation REAL DEFAULT 0'],
            ['name' => 'trail_rating', 'sql' => 'ALTER TABLE trips ADD COLUMN trail_rating INTEGER CHECK(trail_rating >= 1 AND trail_rating <= 5)'],
            
            // Logistics
            ['name' => 'permit_cost', 'sql' => 'ALTER TABLE trips ADD COLUMN permit_cost REAL DEFAULT 0'],
            ['name' => 'reservation_link', 'sql' => 'ALTER TABLE trips ADD COLUMN reservation_link TEXT'],
            ['name' => 'parking_cost', 'sql' => 'ALTER TABLE trips ADD COLUMN parking_cost REAL DEFAULT 0'],
            ['name' => 'shuttle_required', 'sql' => 'ALTER TABLE trips ADD COLUMN shuttle_required INTEGER DEFAULT 0'],
            ['name' => 'shuttle_info', 'sql' => 'ALTER TABLE trips ADD COLUMN shuttle_info TEXT'],
            
            // Trail conditions
            ['name' => 'water_quality_notes', 'sql' => 'ALTER TABLE trips ADD COLUMN water_quality_notes TEXT'],
            ['name' => 'recent_alerts', 'sql' => 'ALTER TABLE trips ADD COLUMN recent_alerts TEXT'],
            ['name' => 'bug_pressure', 'sql' => "ALTER TABLE trips ADD COLUMN bug_pressure TEXT CHECK(bug_pressure IN ('none', 'low', 'moderate', 'high', 'extreme'))"],
            
            // Weather and timing
            ['name' => 'actual_weather', 'sql' => 'ALTER TABLE trips ADD COLUMN actual_weather TEXT'],
            ['name' => 'best_season', 'sql' => 'ALTER TABLE trips ADD COLUMN best_season TEXT'],
            ['name' => 'crowd_level', 'sql' => "ALTER TABLE trips ADD COLUMN crowd_level TEXT CHECK(crowd_level IN ('empty', 'light', 'moderate', 'busy', 'packed'))"],
            
            // Safety
            ['name' => 'nearest_hospital', 'sql' => 'ALTER TABLE trips ADD COLUMN nearest_hospital TEXT'],
            ['name' => 'cell_coverage', 'sql' => "ALTER TABLE trips ADD COLUMN cell_coverage TEXT CHECK(cell_coverage IN ('none', 'poor', 'spotty', 'good', 'excellent'))"],
            ['name' => 'emergency_beacon', 'sql' => 'ALTER TABLE trips ADD COLUMN emergency_beacon INTEGER DEFAULT 0'],
            
            // Notes and reflections
            ['name' => 'pre_trip_notes', 'sql' => 'ALTER TABLE trips ADD COLUMN pre_trip_notes TEXT'],
            ['name' => 'post_trip_notes', 'sql' => 'ALTER TABLE trips ADD COLUMN post_trip_notes TEXT'],
            ['name' => 'lessons_learned', 'sql' => 'ALTER TABLE trips ADD COLUMN lessons_learned TEXT'],
            ['name' => 'gear_notes', 'sql' => 'ALTER TABLE trips ADD COLUMN gear_notes TEXT'],
            ['name' => 'food_notes', 'sql' => 'ALTER TABLE trips ADD COLUMN food_notes TEXT'],
            
            // Stats
            ['name' => 'completed', 'sql' => 'ALTER TABLE trips ADD COLUMN completed INTEGER DEFAULT 0'],
            ['name' => 'favorite', 'sql' => 'ALTER TABLE trips ADD COLUMN favorite INTEGER DEFAULT 0'],
        ];
        
        $added = 0;
        $skipped = 0;
        
        foreach ($migrations as $migration) {
            if (!in_array($migration['name'], $existing_columns)) {
                try {
                    $conn->exec($migration['sql']);
                    $added++;
                    echo "✅ Added column: {$migration['name']}\n";
                } catch (Exception $e) {
                    echo "⚠️ Failed to add column {$migration['name']}: {$e->getMessage()}\n";
                }
            } else {
                $skipped++;
                echo "⏭️ Column already exists: {$migration['name']}\n";
            }
        }
        
        return [
            'success' => true,
            'message' => "Migration completed. Added $added columns, skipped $skipped existing columns."
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ];
    }
}

// Run migration if called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'migrate-trips.php') {
    header('Content-Type: text/plain');
    echo "Starting trips table migration...\n\n";
    
    $result = migrateTripsTable();
    
    echo "\n" . $result['message'] . "\n";
}
