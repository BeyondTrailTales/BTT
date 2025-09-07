<?php
session_start();

// Login as admin for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

header('Content-Type: text/plain');
echo "=== PACKING LIST DEBUG ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    // Get a trip with a backpack
    $stmt = $db->prepare("SELECT id, title, backpack_id FROM trips WHERE user_id = ? AND backpack_id IS NOT NULL LIMIT 1");
    $stmt->execute([$user_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        echo "❌ No trips with backpacks found\n";
        exit;
    }
    
    echo "Testing trip: {$trip['id']} - {$trip['title']} (backpack: {$trip['backpack_id']})\n\n";
    
    // Get the backpack items directly
    echo "=== BACKPACK ITEMS SQL ===\n";
    $stmt = $db->prepare(
        "SELECT 
            bg.gear_id,
            bg.quantity,
            bg.section,
            COALESCE(bg.custom_name, 'Unknown') as name,
            COALESCE(bg.custom_weight, 0) as weight,
            COALESCE(bg.custom_category, 'other') as gear_category,
            COALESCE(bg.custom_notes, '') as gear_notes
        FROM backpack_gear bg
        WHERE bg.backpack_id = ?
        ORDER BY bg.section, bg.position"
    );
    $stmt->execute([$trip['backpack_id']]);
    $backpackItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($backpackItems)) {
        echo "❌ No items found in backpack {$trip['backpack_id']}\n";
        exit;
    }
    
    echo "Found " . count($backpackItems) . " items:\n";
    foreach ($backpackItems as $item) {
        echo "- gear_id: {$item['gear_id']} (type: " . gettype($item['gear_id']) . "), name: {$item['name']}\n";
        
        if ($item['gear_id']) {
            // Check if this gear_id exists in gear_items
            $checkStmt = $db->prepare("SELECT id, name FROM gear_items WHERE id = ?");
            $checkStmt->execute([$item['gear_id']]);
            $gearItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($gearItem) {
                echo "  ✅ gear_id {$item['gear_id']} exists: {$gearItem['name']}\n";
            } else {
                echo "  ❌ gear_id {$item['gear_id']} NOT FOUND in gear_items\n";
            }
        }
    }
    
    echo "\n=== SIMULATED API CALL ===\n";
    
    // Set up the environment like ajax-handler.php
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['route'] = 'trips';
    $_GET['id'] = $trip['id'];
    $_GET['action'] = 'packing-list';
    
    // Include and test the packing API
    require_once __DIR__ . '/api/routes/trip_packing.php';
    
    ob_start();
    handleTripPackingRoute('GET', [$trip['id']]);
    $apiOutput = ob_get_clean();
    
    echo "API Output:\n";
    echo $apiOutput . "\n";
    
    // Try to parse it as JSON
    $data = json_decode($apiOutput, true);
    if ($data && isset($data['data']['items'])) {
        echo "\n=== PARSED API ITEMS ===\n";
        foreach ($data['data']['items'] as $item) {
            echo "Item: {$item['name']}\n";
            echo "  - type: {$item['type']}\n";
            echo "  - gear_id: " . ($item['gear_id'] ?? 'null') . " (" . gettype($item['gear_id'] ?? null) . ")\n";
            echo "  - custom_id: " . ($item['custom_id'] ?? 'null') . "\n";
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>