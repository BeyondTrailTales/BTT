<?php
/**
 * Test that all fields are properly saved to database
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Login as admin first
$loginResult = AuthService::login('admin', 'Admin123!', false);
if (!$loginResult['success']) {
    die("Failed to login as admin: " . $loginResult['message'] . "\n");
}

$user = AuthService::getCurrentUser();
echo "Logged in as: " . $user['username'] . " (ID: " . $user['id'] . ")\n\n";

try {
    $db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TESTING TRIP FIELD SAVING ===\n\n";
    
    // Create a test trip with ALL fields
    $testTripData = [
        'user_id' => $user['id'],
        'title' => 'Test Trip - All Fields',
        'location' => 'Test Mountain Range',
        'start_date' => '2024-06-01',
        'end_date' => '2024-06-05',
        'distance' => 25.5,
        'distance_unit' => 'miles',
        'elevation_gain' => 3500,
        'difficulty' => 'moderate',
        'trip_type' => 'multi-day',
        'description' => 'This is a test trip to verify all fields save correctly.',
        'permit_required' => 1,
        'permit_info' => 'Permit required from ranger station',
        'water_sources' => 'Stream at mile 5, lake at mile 12',
        'camping_type' => 'backcountry',
        'expected_weather' => 'Sunny days, cold nights',
        'trail_conditions' => 'Rocky terrain, some snow patches',
        'emergency_contact' => 'Ranger Station: 555-0123',
        'trailhead_parking' => 'Large parking lot, arrive early',
        'completed' => 0,
        'favorite' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Build INSERT statement
    $fields = array_keys($testTripData);
    $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
    
    $sql = "INSERT INTO trips (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    foreach ($testTripData as $field => $value) {
        $stmt->bindValue(':' . $field, $value);
    }
    
    if ($stmt->execute()) {
        $tripId = $db->lastInsertId();
        echo "✅ Successfully created test trip with ID: $tripId\n";
        
        // Verify all fields were saved
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = :id");
        $stmt->execute(['id' => $tripId]);
        $savedTrip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nVerifying saved fields:\n";
        $allFieldsSaved = true;
        foreach ($testTripData as $field => $expectedValue) {
            $savedValue = $savedTrip[$field] ?? null;
            if ($savedValue == $expectedValue) {
                echo "  ✅ $field: OK\n";
            } else {
                echo "  ❌ $field: Expected '$expectedValue', got '$savedValue'\n";
                $allFieldsSaved = false;
            }
        }
        
        if ($allFieldsSaved) {
            echo "\n✅ All trip fields saved correctly!\n";
        } else {
            echo "\n❌ Some trip fields did not save correctly.\n";
        }
        
        // Clean up test data
        $db->exec("DELETE FROM trips WHERE id = $tripId");
        echo "\n🧹 Test trip deleted.\n";
        
    } else {
        echo "❌ Failed to create test trip\n";
    }
    
    echo "\n=== TESTING BACKPACK FIELD SAVING ===\n\n";
    
    // Create a test backpack with ALL fields
    $testBackpackData = [
        'user_id' => $user['id'],
        'name' => 'Test Backpack - All Fields',
        'description' => 'This is a test backpack to verify all fields save correctly.',
        'capacity' => 65,
        'capacity_l' => 65.0,
        'base_weight' => 10.5,
        'weight_empty_g' => 1500,
        'type' => 'multi-day',
        'tags' => json_encode(['ultralight', 'waterproof', 'test']),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Build INSERT statement
    $fields = array_keys($testBackpackData);
    $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
    
    $sql = "INSERT INTO backpacks (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    foreach ($testBackpackData as $field => $value) {
        $stmt->bindValue(':' . $field, $value);
    }
    
    if ($stmt->execute()) {
        $backpackId = $db->lastInsertId();
        echo "✅ Successfully created test backpack with ID: $backpackId\n";
        
        // Verify all fields were saved
        $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = :id");
        $stmt->execute(['id' => $backpackId]);
        $savedBackpack = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nVerifying saved fields:\n";
        $allFieldsSaved = true;
        foreach ($testBackpackData as $field => $expectedValue) {
            $savedValue = $savedBackpack[$field] ?? null;
            if ($savedValue == $expectedValue) {
                echo "  ✅ $field: OK\n";
            } else {
                echo "  ❌ $field: Expected '$expectedValue', got '$savedValue'\n";
                $allFieldsSaved = false;
            }
        }
        
        if ($allFieldsSaved) {
            echo "\n✅ All backpack fields saved correctly!\n";
        } else {
            echo "\n❌ Some backpack fields did not save correctly.\n";
        }
        
        // Clean up test data
        $db->exec("DELETE FROM backpacks WHERE id = $backpackId");
        echo "\n🧹 Test backpack deleted.\n";
        
    } else {
        echo "❌ Failed to create test backpack\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Logout
AuthService::logout();
echo "\nLogged out.\n";
