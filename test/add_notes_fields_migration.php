<?php
/**
 * Migration to add missing note fields to trips table
 */

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

$db = Database::getInstance();

echo "=== Adding Note Fields to Trips Table ===\n\n";

// Check current schema
echo "1. Current trips table schema:\n";
$sql = "PRAGMA table_info(trips)";
$columns = $db->fetchAll($sql);
$existing_columns = array_column($columns, 'name');
echo "   Existing columns: " . implode(', ', $existing_columns) . "\n\n";

// Fields to add
$fields_to_add = [
    'pre_trip_notes' => 'TEXT',
    'post_trip_notes' => 'TEXT',
    'lessons_learned' => 'TEXT',
    'favorite' => 'INTEGER DEFAULT 0',
    'completed' => 'INTEGER DEFAULT 0',
    'permit_cost' => 'REAL',
    'parking_cost' => 'REAL',
    'cell_coverage' => 'VARCHAR(50)',
    'crowd_level' => 'VARCHAR(50)'
];

echo "2. Adding missing fields:\n";
foreach ($fields_to_add as $field => $type) {
    if (in_array($field, $existing_columns)) {
        echo "   - $field already exists, skipping\n";
        continue;
    }
    
    try {
        $sql = "ALTER TABLE trips ADD COLUMN $field $type";
        $db->query($sql);
        echo "   ✓ Added $field ($type)\n";
    } catch (Exception $e) {
        echo "   ✗ Failed to add $field: " . $e->getMessage() . "\n";
    }
}

// Verify the changes
echo "\n3. Verifying changes:\n";
$sql = "PRAGMA table_info(trips)";
$columns = $db->fetchAll($sql);
$note_fields = ['pre_trip_notes', 'post_trip_notes', 'lessons_learned'];

foreach ($columns as $col) {
    if (in_array($col['name'], $note_fields)) {
        echo "   ✓ Note field confirmed: " . $col['name'] . " (type: " . $col['type'] . ")\n";
    }
}

echo "\n=== Migration Complete ===\n";
