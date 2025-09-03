<?php
/**
 * Fix any remaining missing columns in trips table
 */

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

echo "=== Checking for missing columns ===\n\n";

try {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        echo "Using JSON storage - no schema changes needed\n";
        exit(0);
    }
    
    $conn = $db->getConnection();
    
    // Get current columns
    $result = $conn->query("PRAGMA table_info(trips)");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['name'];
    }
    
    // Check for completed and favorite columns specifically
    $critical_columns = [
        'completed' => 'INTEGER DEFAULT 0',
        'favorite' => 'INTEGER DEFAULT 0'
    ];
    
    $added = 0;
    foreach ($critical_columns as $column_name => $column_type) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $sql = "ALTER TABLE trips ADD COLUMN $column_name $column_type";
                $conn->exec($sql);
                echo "✓ Added column: $column_name ($column_type)\n";
                $added++;
            } catch (PDOException $e) {
                echo "✗ Could not add column $column_name: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Column already exists: $column_name\n";
        }
    }
    
    if ($added > 0) {
        echo "\n✅ Added $added missing columns\n";
    } else {
        echo "\n✅ All critical columns already exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
