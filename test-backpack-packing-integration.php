<?php
session_start();

// Login check (use the manual login first)
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== BACKPACK PACKING INTEGRATION TEST ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    echo "Testing for user ID: $user_id\n\n";
    
    // 1. Check if user has any backpacks with items
    echo "=== STEP 1: CHECK BACKPACKS ===\n";
    $stmt = $db->prepare("SELECT b.id, b.name, COUNT(bg.gear_id) as gear_count 
                         FROM backpacks b 
                         LEFT JOIN backpack_gear bg ON b.id = bg.backpack_id 
                         WHERE b.user_id = ? 
                         GROUP BY b.id, b.name");
    $stmt->execute([$user_id]);
    $backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($backpacks)) {
        echo "❌ No backpacks found for user\n";
        exit;
    }
    
    foreach ($backpacks as $bp) {
        echo "Backpack: {$bp['name']} (ID: {$bp['id']}) - {$bp['gear_count']} items\n";
    }
    
    // 2. Check if user has any trips
    echo "\n=== STEP 2: CHECK TRIPS ===\n";
    $stmt = $db->prepare("SELECT id, title, backpack_id FROM trips WHERE user_id = ? LIMIT 3");
    $stmt->execute([$user_id]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($trips)) {
        echo "❌ No trips found for user\n";
        exit;
    }
    
    foreach ($trips as $trip) {
        $backpackInfo = $trip['backpack_id'] ? "Backpack: {$trip['backpack_id']}" : "No backpack";
        echo "Trip: {$trip['title']} (ID: {$trip['id']}) - $backpackInfo\n";
    }
    
    // 3. Test the packing list API for a trip with a backpack
    echo "\n=== STEP 3: TEST PACKING API ===\n";
    $testTrip = null;
    foreach ($trips as $trip) {
        if ($trip['backpack_id']) {
            $testTrip = $trip;
            break;
        }
    }
    
    if (!$testTrip) {
        echo "❌ No trips with backpacks found. Assign a backpack to a trip first.\n";
        exit;
    }
    
    echo "Testing trip: {$testTrip['title']} with backpack ID: {$testTrip['backpack_id']}\n";
    
    // Simulate the API call
    $_GET['route'] = 'trips';
    $_GET['id'] = $testTrip['id'];
    $_GET['action'] = 'packing-list';
    
    ob_start();
    require __DIR__ . '/api/routes/trip_packing.php';
    // Call the function directly
    getPackingList($testTrip['id']);
    $output = ob_get_clean();
    
    echo "\nAPI Response:\n";
    $response = json_decode($output, true);
    if ($response) {
        echo "✅ API returned valid JSON\n";
        echo "Categories: " . implode(', ', array_keys($response['categories'] ?? [])) . "\n";
        
        $totalItems = 0;
        foreach ($response['categories'] ?? [] as $category => $items) {
            $itemCount = count($items);
            $totalItems += $itemCount;
            echo "  $category: $itemCount items\n";
            
            // Show first item details
            if (!empty($items)) {
                $firstItem = $items[0];
                echo "    Example: {$firstItem['name']} (qty: {$firstItem['quantity']}) - " . 
                     ($firstItem['is_packed'] ? 'PACKED' : 'NOT PACKED') . "\n";
            }
        }
        
        echo "Total items from backpack: $totalItems\n";
        
        if ($totalItems > 0) {
            echo "✅ SUCCESS: Packing list populated from backpack!\n";
        } else {
            echo "⚠️ WARNING: No items loaded from backpack\n";
        }
    } else {
        echo "❌ API returned invalid response:\n";
        echo $output . "\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "If everything works, selecting a backpack in the trip form should populate the packing list.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>