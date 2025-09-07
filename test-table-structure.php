<?php
session_start();
$_SESSION['user_id'] = 1;

header('Content-Type: text/plain');
echo "=== TABLE STRUCTURE DEBUG ===\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Trips table structure:\n";
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   {$col['name']} ({$col['type']})\n";
    }
    
    echo "\n2. Sample trips:\n";
    $stmt = $db->query("SELECT id, title, backpack_id FROM trips LIMIT 5");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trips as $trip) {
        echo "   ID {$trip['id']}: {$trip['title']} (backpack: {$trip['backpack_id']})\n";
    }
    
    echo "\n3. Backpack_gear table structure:\n";
    $stmt = $db->query("PRAGMA table_info(backpack_gear)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   {$col['name']} ({$col['type']})\n";
    }
    
    echo "\n4. Sample backpack_gear entries:\n";
    $stmt = $db->query("SELECT backpack_id, gear_id, custom_name FROM backpack_gear LIMIT 5");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        echo "   Backpack {$item['backpack_id']}: gear_id={$item['gear_id']}, name={$item['custom_name']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>