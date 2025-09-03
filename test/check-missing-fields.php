<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING TRIPS TABLE FIELDS ===\n\n";
    
    // Get current columns in trips table
    $stmt = $db->query("PRAGMA table_info(trips)");
    $currentColumns = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentColumns[] = $col['name'];
    }
    
    echo "Current columns in trips table:\n";
    foreach ($currentColumns as $col) {
        echo "  - $col\n";
    }
    
    // Expected fields based on the forms and API
    $expectedTripFields = [
        'id', 'title', 'location', 'start_date', 'end_date', 'description',
        'distance', 'distance_unit', 'elevation_gain', 'difficulty', 'trip_type',
        'permit_required', 'permit_info', 'water_sources', 'camping_type',
        'expected_weather', 'trail_conditions', 'emergency_contact', 'trailhead_parking',
        'backpack_id', 'completed', 'favorite', 
        'image_url', 'image_alt', 'photo_path', 'photo_alt_text',
        'user_id', 'created_at', 'updated_at'
    ];
    
    echo "\n❌ MISSING fields in trips table:\n";
    $missingFields = array_diff($expectedTripFields, $currentColumns);
    if (empty($missingFields)) {
        echo "  None - all expected fields exist!\n";
    } else {
        foreach ($missingFields as $field) {
            echo "  - $field\n";
        }
    }
    
    echo "\n⚠️  Extra fields in trips table (not expected):\n";
    $extraFields = array_diff($currentColumns, $expectedTripFields);
    if (empty($extraFields)) {
        echo "  None\n";
    } else {
        foreach ($extraFields as $field) {
            echo "  - $field\n";
        }
    }
    
    echo "\n=== CHECKING BACKPACKS TABLE FIELDS ===\n\n";
    
    // Get current columns in backpacks table
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    $currentBackpackColumns = [];
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentBackpackColumns[] = $col['name'];
    }
    
    echo "Current columns in backpacks table:\n";
    foreach ($currentBackpackColumns as $col) {
        echo "  - $col\n";
    }
    
    // Expected backpack fields
    $expectedBackpackFields = [
        'id', 'name', 'description', 
        'capacity', 'capacity_l', 'base_weight', 'weight_empty_g',
        'image_url', 'image_alt', 'type', 'tags',
        'user_id', 'created_at', 'updated_at'
    ];
    
    echo "\n❌ MISSING fields in backpacks table:\n";
    $missingBackpackFields = array_diff($expectedBackpackFields, $currentBackpackColumns);
    if (empty($missingBackpackFields)) {
        echo "  None - all expected fields exist!\n";
    } else {
        foreach ($missingBackpackFields as $field) {
            echo "  - $field\n";
        }
    }
    
    echo "\n⚠️  Extra fields in backpacks table (not expected):\n";
    $extraBackpackFields = array_diff($currentBackpackColumns, $expectedBackpackFields);
    if (empty($extraBackpackFields)) {
        echo "  None\n";
    } else {
        foreach ($extraBackpackFields as $field) {
            echo "  - $field\n";
        }
    }
    
    echo "\n=== SUGGESTED SQL TO ADD MISSING FIELDS ===\n\n";
    
    if (!empty($missingFields)) {
        echo "-- Add missing fields to trips table:\n";
        foreach ($missingFields as $field) {
            $type = 'TEXT';
            if (in_array($field, ['permit_required', 'completed', 'favorite'])) {
                $type = 'INTEGER DEFAULT 0';
            } elseif (in_array($field, ['distance', 'elevation_gain'])) {
                $type = 'REAL';
            } elseif ($field === 'photo_path' || $field === 'photo_alt_text') {
                $type = 'TEXT';
            }
            echo "ALTER TABLE trips ADD COLUMN $field $type;\n";
        }
        echo "\n";
    }
    
    if (!empty($missingBackpackFields)) {
        echo "-- Add missing fields to backpacks table:\n";
        foreach ($missingBackpackFields as $field) {
            $type = 'TEXT';
            if (in_array($field, ['capacity_l', 'weight_empty_g'])) {
                $type = 'REAL';
            } elseif ($field === 'tags') {
                $type = 'TEXT'; // Will store as JSON
            }
            echo "ALTER TABLE backpacks ADD COLUMN $field $type;\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
