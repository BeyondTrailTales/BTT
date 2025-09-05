<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== PACKING LIST API DIRECT TEST ===\n";

require_once __DIR__ . '/app/config.php';

$user_id = $_SESSION['user_id'];
$trip_id = 26; // From your test result

echo "Testing packing list for trip ID: $trip_id\n";
echo "User ID: $user_id\n\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Verify the trip and backpack association
    echo "=== STEP 1: VERIFY TRIP-BACKPACK LINK ===\n";
    $stmt = $db->prepare("SELECT t.id, t.title, t.backpack_id, b.name as backpack_name 
                         FROM trips t 
                         LEFT JOIN backpacks b ON t.backpack_id = b.id 
                         WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$trip_id, $user_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        echo "❌ Trip not found or access denied\n";
        exit;
    }
    
    echo "Trip: {$trip['title']}\n";
    echo "Backpack ID: {$trip['backpack_id']}\n";
    echo "Backpack Name: {$trip['backpack_name']}\n";
    
    if (!$trip['backpack_id']) {
        echo "❌ No backpack associated with this trip\n";
        exit;
    }
    
    // 2. Check if backpack has items
    echo "\n=== STEP 2: CHECK BACKPACK ITEMS ===\n";
    $stmt = $db->prepare("SELECT bg.gear_id, bg.quantity, bg.section, 
                                 COALESCE(bg.custom_name, 'Unknown') as name,
                                 COALESCE(bg.custom_category, 'other') as gear_category
                         FROM backpack_gear bg 
                         WHERE bg.backpack_id = ?
                         ORDER BY bg.section, bg.position");
    $stmt->execute([$trip['backpack_id']]);
    $backpackItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($backpackItems) . " items in backpack:\n";
    
    if (empty($backpackItems)) {
        echo "❌ No items found in backpack! Add some items to the backpack first.\n";
        exit;
    }
    
    foreach ($backpackItems as $item) {
        echo "- {$item['name']} (qty: {$item['quantity']}, section: {$item['section']})\n";
    }
    
    // 3. Test the packing API function directly
    echo "\n=== STEP 3: TEST PACKING API FUNCTION ===\n";
    
    // Load the function
    require_once __DIR__ . '/api/routes/trip_packing.php';
    
    // Capture output
    ob_start();
    
    try {
        getPackingList($trip_id);
        $apiOutput = ob_get_clean();
        
        echo "Raw API output:\n";
        echo $apiOutput . "\n";
        
        // Parse JSON
        $apiResult = json_decode($apiOutput, true);
        if ($apiResult) {
            echo "\n✅ Valid JSON returned\n";
            
            if (isset($apiResult['categories'])) {
                echo "Categories found: " . implode(', ', array_keys($apiResult['categories'])) . "\n";
                
                $totalItems = 0;
                foreach ($apiResult['categories'] as $category => $items) {
                    $count = count($items);
                    $totalItems += $count;
                    echo "  $category: $count items\n";
                    
                    // Show first few items
                    foreach (array_slice($items, 0, 3) as $item) {
                        $packed = $item['is_packed'] ? 'PACKED' : 'NOT PACKED';
                        echo "    - {$item['name']} (qty: {$item['quantity']}) [$packed]\n";
                    }
                }
                
                echo "\nTotal items: $totalItems\n";
                
                if ($totalItems > 0) {
                    echo "✅ SUCCESS: Packing list working correctly!\n";
                    echo "The JavaScript should receive this data and display it.\n";
                } else {
                    echo "❌ No items returned in packing list\n";
                }
            } else {
                echo "❌ No 'categories' in API response\n";
                print_r($apiResult);
            }
        } else {
            echo "❌ Invalid JSON in API response\n";
            echo "First 200 chars: " . substr($apiOutput, 0, 200) . "\n";
        }
        
    } catch (Exception $e) {
        $errorOutput = ob_get_clean();
        echo "❌ API function threw exception: " . $e->getMessage() . "\n";
        echo "Output before error: $errorOutput\n";
    }
    
    // 4. Test via ajax-handler route
    echo "\n=== STEP 4: TEST VIA AJAX-HANDLER ===\n";
    
    $_GET = [
        'route' => 'trips',
        'id' => $trip_id,
        'action' => 'packing-list'
    ];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include __DIR__ . '/ajax-handler.php';
    $ajaxOutput = ob_get_clean();
    
    echo "Ajax-handler output:\n";
    echo $ajaxOutput . "\n";
    
    $ajaxResult = json_decode($ajaxOutput, true);
    if ($ajaxResult && isset($ajaxResult['categories'])) {
        echo "✅ Ajax-handler also working correctly\n";
    } else {
        echo "❌ Ajax-handler not working\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>