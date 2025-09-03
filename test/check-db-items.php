<?php
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

$db = Database::getInstance();

// Get most recent backpacks
$backpacks = $db->fetchAll("SELECT id, name, user_id FROM backpacks ORDER BY updated_at DESC, created_at DESC LIMIT 5");

foreach($backpacks as $bp) {
    echo "Backpack: {$bp['name']} (ID: {$bp['id']}, User: {$bp['user_id']})\n";
    
    // Get items
    $items = $db->fetchAll("
        SELECT bg.*, 
               COALESCE(bg.custom_name, gi.name, 'Unknown') as item_name
        FROM backpack_gear bg
        LEFT JOIN gear_items gi ON bg.gear_id = gi.id
        WHERE bg.backpack_id = :id
        ORDER BY bg.section, bg.position
    ", ['id' => $bp['id']]);
    
    if (empty($items)) {
        echo "  No items\n";
    } else {
        echo "  Items (" . count($items) . "):\n";
        foreach($items as $item) {
            echo "    - {$item['item_name']} (Section: {$item['section']}, Qty: {$item['quantity']})\n";
        }
    }
    echo "\n";
}
