<?php
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/app/Services/AuthService.php';

use App\Services\AuthService;

$db = Database::getInstance();

echo "=== Debugging Backpack Items ===\n\n";

// 1. Check backpacks
echo "1. Backpacks in database:\n";
$backpacks = $db->fetchAll("SELECT id, user_id, name, created_at FROM backpacks ORDER BY created_at DESC LIMIT 10");
foreach($backpacks as $bp) {
    echo "  ID: {$bp['id']}, User: {$bp['user_id']}, Name: {$bp['name']}, Created: {$bp['created_at']}\n";
}

// 2. Check backpack_gear table
echo "\n2. Items in backpack_gear table:\n";
$items = $db->fetchAll("
    SELECT bg.*, b.name as backpack_name, b.user_id 
    FROM backpack_gear bg 
    JOIN backpacks b ON bg.backpack_id = b.id 
    ORDER BY bg.backpack_id, bg.section
    LIMIT 20
");

if (empty($items)) {
    echo "  ⚠️ No items found in backpack_gear table!\n";
} else {
    foreach($items as $item) {
        echo "  Backpack [{$item['backpack_id']}] {$item['backpack_name']} (User {$item['user_id']}):\n";
        echo "    - Section: {$item['section']}\n";
        echo "    - Gear ID: " . ($item['gear_id'] ?: 'NULL (custom)') . "\n";
        echo "    - Custom Name: " . ($item['custom_name'] ?: 'NULL') . "\n";
        echo "    - Quantity: {$item['quantity']}\n";
        echo "    ---\n";
    }
}

// 3. Check gear_items table
echo "\n3. System gear items (first 10):\n";
$gearItems = $db->fetchAll("SELECT id, name, weight, category FROM gear_items WHERE user_id IS NULL LIMIT 10");
foreach($gearItems as $item) {
    echo "  ID: {$item['id']}, Name: {$item['name']}, Weight: {$item['weight']}g, Category: {$item['category']}\n";
}

// 4. Test the load function directly
echo "\n4. Testing loadBackpackSections function:\n";

// Include the functions
require_once dirname(__DIR__) . '/api/routes/backpacks_items.php';

// Get the most recent backpack
$latestBackpack = $db->fetchOne("SELECT id, user_id, name FROM backpacks ORDER BY created_at DESC LIMIT 1");
if ($latestBackpack) {
    echo "  Loading sections for backpack ID {$latestBackpack['id']} ({$latestBackpack['name']})...\n";
    
    $sections = loadBackpackSections($latestBackpack['id'], $latestBackpack['user_id']);
    
    if (empty($sections)) {
        echo "  ⚠️ No sections returned!\n";
    } else {
        foreach ($sections as $section) {
            echo "  Section: {$section['name']}\n";
            echo "    Items: " . count($section['items']) . "\n";
            foreach ($section['items'] as $item) {
                echo "      - {$item['name']} ({$item['weight_g']}g x {$item['quantity']})\n";
            }
        }
    }
}

// 5. Check if sections are being included in API response
echo "\n5. Simulating API getBackpackById:\n";

if ($latestBackpack) {
    // Simulate what the API does
    $backpack = $db->fetchOne("
        SELECT b.*, COUNT(t.id) as trip_count
        FROM backpacks b
        LEFT JOIN trips t ON b.id = t.backpack_id
        WHERE b.id = :id
        GROUP BY b.id
    ", ['id' => $latestBackpack['id']]);
    
    if ($backpack) {
        $backpack['sections'] = loadBackpackSections($backpack['id'], $backpack['user_id']);
        
        echo "  Backpack: {$backpack['name']}\n";
        echo "  Sections in response: " . count($backpack['sections']) . "\n";
        
        $totalItems = 0;
        foreach ($backpack['sections'] as $section) {
            $totalItems += count($section['items']);
        }
        echo "  Total items across all sections: $totalItems\n";
    }
}

echo "\n=== End Debug ===\n";
