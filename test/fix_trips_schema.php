<?php
/**
 * One-time script to fix the trips table schema
 * Adds missing columns needed by the trips form
 */

// Bootstrap the application
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

echo "=== Fixing trips table schema ===\n\n";

try {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        echo "Using JSON storage - no schema changes needed\n";
        exit(0);
    }
    
    $conn = $db->getConnection();
    
    // Get current columns
    echo "Checking current trips table columns...\n";
    $result = $conn->query("PRAGMA table_info(trips)");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['name'];
    }
    echo "Current columns: " . implode(', ', $existing_columns) . "\n\n";
    
    // Define all columns that should exist
    $required_columns = [
        'default_image_url' => 'TEXT',
        'default_image_alt' => 'TEXT',
        'permit_required' => 'INTEGER DEFAULT 0',
        'permit_cost' => 'REAL',
        'permit_info' => 'TEXT',
        'water_sources' => 'TEXT',
        'camping_type' => 'TEXT',
        'expected_weather' => 'TEXT',
        'trail_conditions' => 'TEXT',
        'emergency_contact' => 'TEXT',
        'trailhead_parking' => 'TEXT',
        'parking_cost' => 'REAL',
        'cell_coverage' => 'TEXT',
        'crowd_level' => 'TEXT',
        'pre_trip_notes' => 'TEXT',
        'post_trip_notes' => 'TEXT',
        'lessons_learned' => 'TEXT',
        'photo_path' => 'TEXT',
        'photo_alt_text' => 'TEXT'
    ];
    
    // Add missing columns
    $columns_added = 0;
    foreach ($required_columns as $column_name => $column_type) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $sql = "ALTER TABLE trips ADD COLUMN $column_name $column_type";
                $conn->exec($sql);
                echo "✓ Added column: $column_name ($column_type)\n";
                $columns_added++;
            } catch (PDOException $e) {
                // Column might already exist or other error
                echo "✗ Could not add column $column_name: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Column already exists: $column_name\n";
        }
    }
    
    echo "\n";
    
    if ($columns_added > 0) {
        echo "✅ SUCCESS: Added $columns_added missing columns to trips table\n";
        
        // Update the migrations table to track this fix
        try {
            $conn->exec("INSERT OR IGNORE INTO migrations (filename) VALUES ('fix_trips_schema_manual')");
            echo "✓ Migration tracked in migrations table\n";
        } catch (PDOException $e) {
            // Migrations table might not exist or other error
            echo "Note: Could not update migrations table: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ All required columns already exist - no changes needed\n";
    }
    
    // Verify final schema
    echo "\nVerifying final schema...\n";
    $result = $conn->query("PRAGMA table_info(trips)");
    $final_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $final_columns[] = $row['name'];
    }
    echo "Final columns (" . count($final_columns) . "): " . implode(', ', $final_columns) . "\n";
    
    // Check if the critical columns exist
    $critical_columns = ['default_image_url', 'default_image_alt'];
    $all_critical_present = true;
    foreach ($critical_columns as $col) {
        if (!in_array($col, $final_columns)) {
            echo "❌ CRITICAL: Missing column $col\n";
            $all_critical_present = false;
        }
    }
    
    if ($all_critical_present) {
        echo "\n✅ All critical columns are present. The trips page should now work!\n";
    } else {
        echo "\n❌ Some critical columns are still missing. Please check the database manually.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Schema fix complete ===\n";
