<?php
/**
 * Test script to verify note fields are working correctly
 * Run this to test saving and retrieving trip notes
 */

// Load API config which sets up database constants
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/app/Services/AuthService.php';

// Initialize
$db = Database::getInstance();

echo "=== Testing Trip Notes Fields ===\n\n";

// Check if fields exist in database
echo "1. Checking database schema:\n";
$sql = "PRAGMA table_info(trips)";
$columns = $db->fetchAll($sql);
$note_fields = ['pre_trip_notes', 'post_trip_notes', 'lessons_learned'];
$found_fields = [];

foreach ($columns as $col) {
    if (in_array($col['name'], $note_fields)) {
        $found_fields[] = $col['name'];
        echo "   ✓ Found field: " . $col['name'] . " (type: " . $col['type'] . ")\n";
    }
}

$missing = array_diff($note_fields, $found_fields);
if (!empty($missing)) {
    echo "   ✗ Missing fields: " . implode(', ', $missing) . "\n";
} else {
    echo "   ✓ All note fields present in schema\n";
}

// Test inserting a trip with notes
echo "\n2. Testing INSERT with note fields:\n";
try {
    $test_data = [
        'user_id' => 1,
        'title' => 'Test Trip with Notes - ' . date('Y-m-d H:i:s'),
        'location' => 'Test Location',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+2 days')),
        'description' => 'Testing note fields functionality',
        'pre_trip_notes' => 'This is a pre-trip note for testing',
        'post_trip_notes' => 'This is a post-trip note for testing',
        'lessons_learned' => 'This is a lesson learned for testing',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $trip_id = $db->insert('trips', $test_data);
    echo "   ✓ Trip created with ID: $trip_id\n";
    
    // Verify the data was saved
    $sql = "SELECT * FROM trips WHERE id = :id";
    $saved_trip = $db->fetchOne($sql, ['id' => $trip_id]);
    
    echo "\n3. Verifying saved note fields:\n";
    $all_good = true;
    foreach ($note_fields as $field) {
        if (isset($saved_trip[$field]) && $saved_trip[$field] === $test_data[$field]) {
            echo "   ✓ $field: '" . substr($saved_trip[$field], 0, 50) . "...'\n";
        } else {
            echo "   ✗ $field: NOT SAVED CORRECTLY!\n";
            echo "     Expected: '" . $test_data[$field] . "'\n";
            echo "     Got: '" . ($saved_trip[$field] ?? 'NULL') . "'\n";
            $all_good = false;
        }
    }
    
    if ($all_good) {
        echo "\n   ✓ All note fields saved and retrieved successfully!\n";
    }
    
    // Test UPDATE
    echo "\n4. Testing UPDATE of note fields:\n";
    $update_data = [
        'pre_trip_notes' => 'UPDATED: Pre-trip note',
        'post_trip_notes' => 'UPDATED: Post-trip note',
        'lessons_learned' => 'UPDATED: Lessons learned',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $db->update('trips', $update_data, 'id = :id', ['id' => $trip_id]);
    
    // Verify update
    $updated_trip = $db->fetchOne($sql, ['id' => $trip_id]);
    $update_good = true;
    foreach ($note_fields as $field) {
        if ($updated_trip[$field] === $update_data[$field]) {
            echo "   ✓ $field updated correctly\n";
        } else {
            echo "   ✗ $field update failed!\n";
            $update_good = false;
        }
    }
    
    if ($update_good) {
        echo "\n   ✓ All note fields updated successfully!\n";
    }
    
    // Clean up test data
    echo "\n5. Cleaning up test data:\n";
    $db->delete('trips', 'id = :id', ['id' => $trip_id]);
    echo "   ✓ Test trip deleted\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Check existing trips
echo "\n6. Checking existing trips for note data:\n";
$sql = "SELECT id, title, pre_trip_notes, post_trip_notes, lessons_learned FROM trips LIMIT 5";
$trips = $db->fetchAll($sql);

if (empty($trips)) {
    echo "   No trips found in database\n";
} else {
    foreach ($trips as $trip) {
        echo "   Trip #" . $trip['id'] . ": " . $trip['title'] . "\n";
        $has_notes = false;
        foreach ($note_fields as $field) {
            if (!empty($trip[$field])) {
                echo "     - $field: '" . substr($trip[$field], 0, 40) . "...'\n";
                $has_notes = true;
            }
        }
        if (!$has_notes) {
            echo "     (No notes)\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
