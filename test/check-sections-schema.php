<?php
/**
 * Check if we have tables for storing backpack sections and items
 */

$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Current Database Tables ===\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(', ', $tables) . "\n\n";

// Check for sections/items related tables
$sectionTables = ['backpack_sections', 'backpack_items', 'backpack_section_items'];
foreach ($sectionTables as $table) {
    $stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = '$table'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "Found table $table:\n";
        echo $result['sql'] . "\n\n";
    } else {
        echo "Table $table does NOT exist\n";
    }
}

// Check if backpacks table has a sections column
echo "\n=== Backpacks Table Structure ===\n";
$stmt = $db->query("PRAGMA table_info(backpacks)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['name'] === 'sections' || $col['name'] === 'items') {
        echo "Found column: {$col['name']} ({$col['type']})\n";
    }
}

// Check for any JSON or TEXT columns that might store sections
echo "\n=== Looking for JSON storage columns ===\n";
foreach ($columns as $col) {
    if (stripos($col['type'], 'TEXT') !== false || stripos($col['type'], 'JSON') !== false) {
        echo "Text/JSON column: {$col['name']} ({$col['type']})\n";
    }
}
