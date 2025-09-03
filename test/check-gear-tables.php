<?php
$db = new PDO('sqlite:' . dirname(__DIR__) . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== GEAR-RELATED TABLES ===\n\n";

// Get all tables with 'gear' in name
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%gear%'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Table: $table\n";
    echo str_repeat('-', 40) . "\n";
    
    // Get table structure
    $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['name']} ({$col['type']})\n";
    }
    
    // Count rows
    $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "\nRows: $count\n\n";
    
    // Show sample data
    if ($count > 0) {
        echo "Sample data:\n";
        $samples = $db->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($samples as $row) {
            print_r($row);
        }
    }
    echo "\n\n";
}
