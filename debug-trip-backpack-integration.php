<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== TRIP-BACKPACK INTEGRATION DEBUG ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    echo "User ID: $user_id\n\n";
    
    // 1. Check trips table schema
    echo "=== STEP 1: TRIPS TABLE SCHEMA ===\n";
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasBackpackId = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'backpack_id') {
            echo "✅ backpack_id column exists: {$col['type']}\n";
            $hasBackpackId = true;
        }
    }
    
    if (!$hasBackpackId) {
        echo "❌ backpack_id column missing from trips table!\n";
        exit;
    }
    
    // 2. Check existing data
    echo "\n=== STEP 2: EXISTING TRIP DATA ===\n";
    $stmt = $db->prepare("SELECT id, title, backpack_id FROM trips WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trips as $trip) {
        $backpackStatus = $trip['backpack_id'] ? "Backpack: {$trip['backpack_id']}" : "NO BACKPACK";
        echo "Trip {$trip['id']}: {$trip['title']} - $backpackStatus\n";
    }
    
    // 3. Check available backpacks
    echo "\n=== STEP 3: AVAILABLE BACKPACKS ===\n";
    $stmt = $db->prepare("SELECT id, name FROM backpacks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($backpacks as $bp) {
        echo "Backpack {$bp['id']}: {$bp['name']}\n";
    }
    
    if (empty($backpacks)) {
        echo "❌ No backpacks found! Create a backpack first.\n";
        exit;
    }
    
    // 4. Test creating a trip with backpack via API
    echo "\n=== STEP 4: TEST API TRIP CREATION ===\n";
    
    $testBackpack = $backpacks[0]; // Use first backpack
    echo "Using backpack: {$testBackpack['name']} (ID: {$testBackpack['id']})\n";
    
    // Simulate POST request to create trip
    $_POST = [
        'title' => 'API Test Trip ' . date('H:i:s'),
        'location' => 'Test Location',
        'backpack_id' => $testBackpack['id'],
        'description' => 'Test trip created via API'
    ];
    
    echo "POST data to send:\n";
    foreach ($_POST as $key => $value) {
        echo "  $key: $value\n";
    }
    
    // Test the ajax-handler
    echo "\nTesting ajax-handler.php...\n";
    
    ob_start();
    
    // Set up the request
    $_GET['route'] = 'trips';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Include ajax-handler
    include __DIR__ . '/ajax-handler.php';
    
    $response = ob_get_clean();
    
    echo "Raw response:\n";
    echo substr($response, 0, 500) . (strlen($response) > 500 ? '...' : '') . "\n";
    
    // Try to parse JSON
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse) {
        echo "\n✅ Valid JSON response received\n";
        if (isset($jsonResponse['id'])) {
            $newTripId = $jsonResponse['id'];
            echo "New trip ID: $newTripId\n";
            
            // Verify in database
            $stmt = $db->prepare("SELECT id, title, backpack_id FROM trips WHERE id = ?");
            $stmt->execute([$newTripId]);
            $savedTrip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($savedTrip) {
                echo "Database verification:\n";
                echo "  ID: {$savedTrip['id']}\n";
                echo "  Title: {$savedTrip['title']}\n";
                echo "  Backpack ID: " . ($savedTrip['backpack_id'] ?: 'NULL') . "\n";
                
                if ($savedTrip['backpack_id'] == $testBackpack['id']) {
                    echo "✅ SUCCESS: Backpack association saved correctly!\n";
                    
                    // Test packing list API
                    echo "\n=== STEP 5: TEST PACKING LIST API ===\n";
                    
                    ob_start();
                    $_GET = [
                        'route' => 'trips',
                        'id' => $newTripId,
                        'action' => 'packing-list'
                    ];
                    $_SERVER['REQUEST_METHOD'] = 'GET';
                    
                    include __DIR__ . '/ajax-handler.php';
                    $packingResponse = ob_get_clean();
                    
                    echo "Packing list response:\n";
                    echo substr($packingResponse, 0, 500) . "\n";
                    
                    $packingJson = json_decode($packingResponse, true);
                    if ($packingJson && isset($packingJson['categories'])) {
                        $totalItems = 0;
                        foreach ($packingJson['categories'] as $cat => $items) {
                            $totalItems += count($items);
                        }
                        echo "✅ Packing list loaded with $totalItems items\n";
                    } else {
                        echo "❌ Packing list failed to load\n";
                        echo "Raw response: $packingResponse\n";
                    }
                    
                } else {
                    echo "❌ FAILURE: Backpack ID not saved correctly!\n";
                    echo "Expected: {$testBackpack['id']}, Got: {$savedTrip['backpack_id']}\n";
                }
            } else {
                echo "❌ Trip not found in database after creation!\n";
            }
        } else {
            echo "❌ No trip ID in response\n";
            print_r($jsonResponse);
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "First 200 chars: " . substr($response, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>