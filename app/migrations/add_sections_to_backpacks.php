<?php
// Add sections column to backpacks table to store section metadata
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../api/classes/Database.php';

$db = Database::getInstance()->getConnection();

try {
    // Check if sections column already exists
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'sections') {
            $hasColumn = true;
            break;
        }
    }
    
    if (!$hasColumn) {
        // Add sections column to store JSON data
        $db->exec("ALTER TABLE backpacks ADD COLUMN sections TEXT");
        echo "✅ Added sections column to backpacks table\n";
    } else {
        echo "ℹ️ Sections column already exists\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}