<?php
header('Content-Type: text/plain');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING GEAR TABLES ===\n";
    
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%gear%'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tables as $table) {
        echo "Table: {$table['name']}\n";
        
        // Get row count
        $countStmt = $db->query("SELECT COUNT(*) as count FROM `{$table['name']}`");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "  Rows: {$count['count']}\n";
        
        // Show schema
        $schemaStmt = $db->query("PRAGMA table_info(`{$table['name']}`)");
        $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns: " . implode(', ', array_column($columns, 'name')) . "\n\n";
    }
    
    // Check if there are any rows in gear_items
    if (in_array('gear_items', array_column($tables, 'name'))) {
        echo "=== SAMPLE GEAR_ITEMS ===\n";
        $stmt = $db->query("SELECT id, name FROM gear_items LIMIT 5");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            echo "ID {$item['id']}: {$item['name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>