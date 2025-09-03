<?php
/**
 * Database Setup Script
 * 
 * Creates database tables and initial structure
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';

function setupDatabase() {
    try {
        $db = Database::getInstance();
        
        if (!$db->isSQLite()) {
            return [
                'success' => true,
                'message' => 'Using JSON storage - no database setup required'
            ];
        }
        
        $conn = $db->getConnection();
        
        // Create backpacks table
        $sql_backpacks = "
            CREATE TABLE IF NOT EXISTS backpacks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                base_weight REAL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ";
        
        $conn->exec($sql_backpacks);
        
        // Create trips table with backpacker fields
        $sql_trips = "
            CREATE TABLE IF NOT EXISTS trips (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                location TEXT,
                start_date TEXT,
                end_date TEXT,
                description TEXT,
                photo_path TEXT,
                photo_alt_text TEXT,
                default_image_url TEXT,
                default_image_alt TEXT,
                backpack_id INTEGER,
                
                -- Trail metrics
                distance REAL DEFAULT 0,
                distance_unit TEXT DEFAULT 'miles',
                elevation_gain REAL DEFAULT 0,
                elevation_loss REAL DEFAULT 0,
                max_elevation REAL DEFAULT 0,
                difficulty TEXT CHECK(difficulty IN ('easy', 'moderate', 'hard', 'expert')),
                trip_type TEXT CHECK(trip_type IN ('day_hike', 'overnight', 'weekend', 'section_hike', 'thru_hike')),
                trail_rating INTEGER CHECK(trail_rating >= 1 AND trail_rating <= 5),
                
                -- Logistics
                permit_required INTEGER DEFAULT 0,
                permit_info TEXT,
                permit_cost REAL DEFAULT 0,
                reservation_link TEXT,
                trailhead_parking TEXT,
                parking_cost REAL DEFAULT 0,
                shuttle_required INTEGER DEFAULT 0,
                shuttle_info TEXT,
                
                -- Trail conditions
                water_sources TEXT,
                water_quality_notes TEXT,
                camping_type TEXT CHECK(camping_type IN ('backcountry', 'established', 'dispersed', 'shelter', 'mixed')),
                trail_conditions TEXT,
                recent_alerts TEXT,
                bug_pressure TEXT CHECK(bug_pressure IN ('none', 'low', 'moderate', 'high', 'extreme')),
                
                -- Weather and timing
                expected_weather TEXT,
                actual_weather TEXT,
                best_season TEXT,
                crowd_level TEXT CHECK(crowd_level IN ('empty', 'light', 'moderate', 'busy', 'packed')),
                
                -- Safety
                emergency_contact TEXT,
                nearest_hospital TEXT,
                cell_coverage TEXT CHECK(cell_coverage IN ('none', 'poor', 'spotty', 'good', 'excellent')),
                emergency_beacon INTEGER DEFAULT 0,
                
                -- Notes and reflections
                pre_trip_notes TEXT,
                post_trip_notes TEXT,
                lessons_learned TEXT,
                gear_notes TEXT,
                food_notes TEXT,
                
                -- Stats
                completed INTEGER DEFAULT 0,
                favorite INTEGER DEFAULT 0,
                
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE SET NULL
            )
        ";
        
        $conn->exec($sql_trips);
        
        // Create indexes for better performance
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_trips_backpack ON trips(backpack_id)");
        $conn->exec("CREATE INDEX IF NOT EXISTS idx_trips_dates ON trips(start_date, end_date)");
        
        return [
            'success' => true,
            'message' => 'Database setup completed successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database setup failed: ' . $e->getMessage()
        ];
    }
}

// Run setup if called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'setup.php') {
    $result = setupDatabase();
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}
