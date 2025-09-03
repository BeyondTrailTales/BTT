<?php
/**
 * Test saving items to a backpack
 */

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/api/routes/backpacks_items.php';

$db = Database::getInstance();

echo "=== Test Saving Backpack Items ===\n\n";

// 1. Get a backpack
$backpack = $db->fetchOne("SELECT id, user_id, name FROM backpacks ORDER BY created_at DESC LIMIT 1");
if (!$backpack) {
    echo "No backpacks found. Creating one...\n";
    $backpackId = $db->insert('backpacks', [
        'user_id' => 1,
        'name' => 'Test Pack for Items',
        'description' => 'Testing item save',
        'capacity_l' => 65,
        'weight_empty_g' => 1500,
        'type' => 'custom',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    $backpack = $db->fetchOne("SELECT id, user_id, name FROM backpacks WHERE id = :id", ['id' => $backpackId]);
}

echo "Using backpack: ID {$backpack['id']}, Name: {$backpack['name']}, User: {$backpack['user_id']}\n\n";

// 2. Create test sections with items
$sections = [
    [
        'id' => 'main',
        'name' => 'Main Compartment',
        'order' => 0,
        'items' => [
            [
                'name' => 'Test Sleeping Bag',
                'weight_g' => 900,
                'quantity' => 1,
                'category' => 'sleep',
                'brand' => 'Test Brand',
                'price' => 200,
                'notes' => 'Test item 1'
            ],
            [
                'gear_id' => 1, // Reference to system item (should be Sleeping Bag)
                'quantity' => 1
            ]
        ]
    ],
    [
        'id' => 'lid',
        'name' => 'Top Lid',
        'order' => 1,
        'items' => [
            [
                'name' => 'Custom First Aid Kit',
                'weight_g' => 250,
                'quantity' => 1,
                'category' => 'first-aid',
                'notes' => 'Custom kit'
            ]
        ]
    ]
];

echo "3. Saving sections to backpack...\n";

// Call the save function
$result = saveBackpackSections($backpack['id'], $backpack['user_id'], $sections);

if ($result) {
    echo "✅ Sections saved successfully!\n\n";
} else {
    echo "❌ Failed to save sections!\n\n";
}

// 4. Verify by checking database
echo "4. Verifying saved items...\n";

$savedItems = $db->fetchAll("
    SELECT * FROM backpack_gear 
    WHERE backpack_id = :backpack_id 
    ORDER BY section, position",
    ['backpack_id' => $backpack['id']]
);

if (empty($savedItems)) {
    echo "❌ No items found in database!\n";
} else {
    echo "✅ Found " . count($savedItems) . " items:\n";
    foreach ($savedItems as $item) {
        echo "  - Section: {$item['section']}\n";
        echo "    Gear ID: " . ($item['gear_id'] ?: 'NULL (custom)') . "\n";
        echo "    Custom Name: " . ($item['custom_name'] ?: 'NULL') . "\n";
        echo "    Quantity: {$item['quantity']}\n";
        echo "    ---\n";
    }
}

// 5. Test loading the sections back
echo "\n5. Testing load function...\n";
$loadedSections = loadBackpackSections($backpack['id'], $backpack['user_id']);

foreach ($loadedSections as $section) {
    echo "Section: {$section['name']}\n";
    echo "  Items: " . count($section['items']) . "\n";
    foreach ($section['items'] as $item) {
        echo "    - {$item['name']} ({$item['weight_g']}g x {$item['quantity']})\n";
    }
}

echo "\n=== Test Complete ===\n";
