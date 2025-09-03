<?php
$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$db = new PDO('sqlite:' . $dbPath);

// Check backpack_gear table
$stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = 'backpack_gear'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "backpack_gear table:\n";
echo $result['sql'] . "\n\n";

// Check some sample data
echo "Sample data from backpack_gear:\n";
$stmt = $db->query("SELECT * FROM backpack_gear LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($data)) {
    echo "No data in backpack_gear table\n";
} else {
    print_r($data);
}

// Check gear_items table too
$stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = 'gear_items'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\n\ngear_items table:\n";
echo $result['sql'] . "\n";
