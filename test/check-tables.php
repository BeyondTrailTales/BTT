<?php
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

$db = Database::getInstance();

echo "Current tables in database:\n";
echo "===========================\n\n";

$tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
foreach($tables as $table) {
    echo "- " . $table['name'] . "\n";
}

// Check gear-related tables specifically
echo "\nGear-related table structures:\n";
echo "==============================\n\n";

$gearTables = ['gear_items', 'gear_items_new', 'backpack_gear', 'backpack_gear_old'];
foreach($gearTables as $tableName) {
    $exists = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name=:name", ['name' => $tableName]);
    if ($exists) {
        echo "$tableName:\n";
        $columns = $db->fetchAll("PRAGMA table_info($tableName)");
        foreach($columns as $col) {
            echo "  - {$col['name']} ({$col['type']})\n";
        }
        echo "\n";
    }
}
