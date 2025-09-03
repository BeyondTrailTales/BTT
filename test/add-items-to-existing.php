<?php
/**
 * Add items to existing backpacks for testing
 */

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/api/routes/backpacks_items.php';

$db = Database::getInstance();

echo "=== Adding Items to Existing Backpacks ===\n\n";

// Get all backpacks
$backpacks = $db->fetchAll("SELECT id, user_id, name FROM backpacks ORDER BY created_at DESC");

echo "Found " . count($backpacks) . " backpacks:\n";
foreach($backpacks as $bp) {
    echo "  - ID {$bp['id']}: {$bp['name']} (User {$bp['user_id']})\n";
}
echo "\n";

// Check which backpacks already have items
foreach($backpacks as $backpack) {
    $itemCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM backpack_gear WHERE backpack_id = :id",
        ['id' => $backpack['id']]
    );
    
    if ($itemCount['count'] == 0) {
        echo "Backpack '{$backpack['name']}' (ID {$backpack['id']}) has no items. Adding some...\n";
        
        // Add some test items
        $sections = [
            [
                'id' => 'main',
                'name' => 'Main Compartment',
                'order' => 0,
                'items' => [
                    ['name' => 'Sleeping Bag', 'weight_g' => 900, 'quantity' => 1, 'category' => 'sleep'],
                    ['name' => 'Tent', 'weight_g' => 1500, 'quantity' => 1, 'category' => 'shelter'],
                    ['name' => 'Sleeping Pad', 'weight_g' => 450, 'quantity' => 1, 'category' => 'sleep']
                ]
            ],
            [
                'id' => 'lid',
                'name' => 'Top Lid',
                'order' => 1,
                'items' => [
                    ['name' => 'First Aid Kit', 'weight_g' => 250, 'quantity' => 1, 'category' => 'first-aid'],
                    ['name' => 'Headlamp', 'weight_g' => 85, 'quantity' => 1, 'category' => 'navigation']
                ]
            ],
            [
                'id' => 'pockets',
                'name' => 'Side Pockets',
                'order' => 2,
                'items' => [
                    ['name' => 'Water Bottle', 'weight_g' => 35, 'quantity' => 2, 'category' => 'water'],
                    ['name' => 'Snacks', 'weight_g' => 200, 'quantity' => 1, 'category' => 'food']
                ]
            ]
        ];
        
        $result = saveBackpackSections($backpack['id'], $backpack['user_id'], $sections);
        
        if ($result) {
            // Verify items were added
            $newCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM backpack_gear WHERE backpack_id = :id",
                ['id' => $backpack['id']]
            );
            echo "  ✅ Added items. New count: {$newCount['count']}\n";
        } else {
            echo "  ❌ Failed to add items\n";
        }
    } else {
        echo "Backpack '{$backpack['name']}' already has {$itemCount['count']} items\n";
    }
}

echo "\n=== Complete ===\n";
