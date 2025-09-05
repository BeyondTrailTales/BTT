<?php
header('Content-Type: text/plain');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== BACKPACK_GEAR TABLE SCHEMA ===\n";
    $stmt = $db->query("PRAGMA table_info(backpack_gear)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in backpack_gear table:\n";
    foreach ($columns as $column) {
        echo "- {$column['name']}: {$column['type']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>