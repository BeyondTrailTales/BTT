<?php
$db = new PDO('sqlite:C:/xampp2/htdocs/BTT/storage/sqlite/btt.db');
$result = $db->query('SELECT name FROM sqlite_master WHERE type="table" ORDER BY name');
$tables = $result->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables: ' . implode(', ', $tables) . PHP_EOL;

// Check if gear tables exist
foreach(['gear_defaults', 'gear_user', 'gear_preferences'] as $table) {
    if (in_array($table, $tables)) {
        $count = $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        echo $table . ': ' . $count . ' rows' . PHP_EOL;
    } else {
        echo $table . ': NOT FOUND' . PHP_EOL;
    }
}
