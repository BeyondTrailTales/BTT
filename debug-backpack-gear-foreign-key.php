<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== BACKPACK GEAR FOREIGN KEY DEBUG ===\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Check foreign key constraint:\n";
    $stmt = $db->query("PRAGMA foreign_key_list(backpack_gear)");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($constraints as $constraint) {
        if ($constraint['from'] === 'gear_id') {
            echo "   gear_id references: {$constraint['table']}.{$constraint['to']}\n";
            break;
        }
    }
    
    echo "\n2. Check what's in gear_items table:\n";
    $stmt = $db->query("SELECT id, name FROM gear_items ORDER BY id LIMIT 10");
    $gearItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($gearItems)) {
        echo "   ❌ No items in gear_items table!\n";
    } else {
        foreach ($gearItems as $item) {
            echo "   ID {$item['id']}: {$item['name']}\n";
        }
    }
    
    echo "\n3. Check what IDs are being used in backpack saves:\n";
    // Look at the most recent failed attempts in backpack_gear
    $stmt = $db->query("SELECT DISTINCT gear_id FROM backpack_gear WHERE gear_id IS NOT NULL ORDER BY gear_id");
    $usedIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   IDs currently in backpack_gear: ";
    echo implode(', ', array_column($usedIds, 'gear_id')) . "\n";
    
    echo "\n4. Check for orphaned gear_ids:\n";
    $stmt = $db->query("
        SELECT DISTINCT bg.gear_id 
        FROM backpack_gear bg 
        LEFT JOIN gear_items gi ON bg.gear_id = gi.id 
        WHERE bg.gear_id IS NOT NULL AND gi.id IS NULL
    ");
    $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphaned)) {
        echo "   ✅ No orphaned gear_ids found\n";
    } else {
        echo "   ❌ Orphaned gear_ids: " . implode(', ', array_column($orphaned, 'gear_id')) . "\n";
    }
    
    echo "\n5. Test sample gear insertion:\n";
    try {
        // Try to insert a gear_id that might not exist
        $testGearId = 999; // Definitely won't exist
        $stmt = $db->prepare("INSERT INTO backpack_gear (backpack_id, gear_id, custom_name, custom_weight, custom_category, quantity, section, position, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
        $stmt->execute([1, $testGearId, 'Test Item', 100, 'other', 1, 'main', 0]);
        echo "   ✅ Test insertion succeeded\n";
        
        // Clean up
        $db->exec("DELETE FROM backpack_gear WHERE gear_id = $testGearId");
        
    } catch (PDOException $e) {
        echo "   ❌ Test insertion failed: " . $e->getMessage() . "\n";
        echo "   This confirms the foreign key constraint is active\n";
    }
    
    echo "\n6. Check default gear library structure:\n";
    // Check what tables might contain the default gear
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%gear%' OR name LIKE '%library%' OR name LIKE '%default%'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Available gear-related tables:\n";
    foreach ($tables as $table) {
        $countStmt = $db->query("SELECT COUNT(*) as count FROM `{$table['name']}`");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "   - {$table['name']}: {$count['count']} rows\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>