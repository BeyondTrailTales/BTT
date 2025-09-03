<?php
/**
 * Test trip creation with detailed debugging
 */

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

echo "=== Testing Trip Creation ===\n\n";

// Test data
$test_data = [
    'title' => 'Test Trip - Debug FK Issue',
    'location' => 'Test Mountain',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+2 days')),
    'description' => 'Testing foreign key constraint issue',
    'backpack_id' => null, // Explicitly null
    'default_image_url' => null,
    'default_image_alt' => null,
    'distance' => 10.5,
    'distance_unit' => 'miles',
    'elevation_gain' => 1500,
    'difficulty' => 'moderate',
    'trip_type' => 'weekend',
    'permit_required' => 0,
    'favorite' => 0,
    'completed' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

try {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        echo "Using JSON storage - test not applicable\n";
        exit(0);
    }
    
    $conn = $db->getConnection();
    
    // First, verify the table structure
    echo "Checking trips table columns:\n";
    $result = $conn->query("PRAGMA table_info(trips)");
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['name']] = $row;
    }
    
    // Check foreign key status
    $fk_result = $conn->query("PRAGMA foreign_keys");
    $fk_status = $fk_result->fetch(PDO::FETCH_ASSOC);
    echo "Foreign keys enabled: " . ($fk_status['foreign_keys'] ? 'YES' : 'NO') . "\n\n";
    
    // Check foreign key constraints for trips table
    echo "Foreign key constraints:\n";
    $fk_list = $conn->query("PRAGMA foreign_key_list(trips)");
    while ($fk = $fk_list->fetch(PDO::FETCH_ASSOC)) {
        echo "  - Column '{$fk['from']}' references {$fk['table']}.{$fk['to']}\n";
    }
    echo "\n";
    
    // Test 1: Insert with null backpack_id
    echo "Test 1: Inserting trip with NULL backpack_id...\n";
    try {
        $trip1_id = $db->insert('trips', $test_data);
        echo "✅ SUCCESS: Trip created with ID $trip1_id\n\n";
        
        // Clean up
        $conn->exec("DELETE FROM trips WHERE id = $trip1_id");
    } catch (PDOException $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n\n";
    }
    
    // Test 2: Insert with valid backpack_id
    echo "Test 2: Inserting trip with valid backpack_id...\n";
    $backpacks = $db->fetchAll("SELECT id, name FROM backpacks LIMIT 1");
    if (!empty($backpacks)) {
        $test_data['backpack_id'] = $backpacks[0]['id'];
        echo "Using backpack ID {$backpacks[0]['id']} ({$backpacks[0]['name']})\n";
        
        try {
            $trip2_id = $db->insert('trips', $test_data);
            echo "✅ SUCCESS: Trip created with ID $trip2_id\n\n";
            
            // Clean up
            $conn->exec("DELETE FROM trips WHERE id = $trip2_id");
        } catch (PDOException $e) {
            echo "❌ FAILED: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "⚠️ SKIPPED: No backpacks found in database\n\n";
    }
    
    // Test 3: Try with backpack_id = 0 (should fail or convert to null)
    echo "Test 3: Testing with backpack_id = 0...\n";
    $test_data['backpack_id'] = 0;
    try {
        $trip3_id = $db->insert('trips', $test_data);
        echo "⚠️ WARNING: Trip created with backpack_id=0 (ID: $trip3_id) - this should have been converted to NULL\n\n";
        
        // Check what was actually stored
        $stored = $db->fetchOne("SELECT backpack_id FROM trips WHERE id = ?", [$trip3_id]);
        echo "Stored backpack_id value: " . var_export($stored['backpack_id'], true) . "\n\n";
        
        // Clean up
        $conn->exec("DELETE FROM trips WHERE id = $trip3_id");
    } catch (PDOException $e) {
        echo "✅ EXPECTED: Failed with FK constraint (correct behavior): " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Try with invalid backpack_id (should fail)
    echo "Test 4: Testing with invalid backpack_id = 99999...\n";
    $test_data['backpack_id'] = 99999;
    try {
        $trip4_id = $db->insert('trips', $test_data);
        echo "❌ ERROR: Trip created with invalid backpack_id (ID: $trip4_id) - FK constraint not working!\n\n";
        
        // Clean up
        $conn->exec("DELETE FROM trips WHERE id = $trip4_id");
    } catch (PDOException $e) {
        echo "✅ EXPECTED: Failed with FK constraint: " . substr($e->getMessage(), 0, 100) . "...\n\n";
    }
    
    echo "=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
