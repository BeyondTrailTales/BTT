<?php
header('Content-Type: text/plain');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== BACKPACK_GEAR FOREIGN KEY CONSTRAINTS ===\n";
    $stmt = $db->query("PRAGMA foreign_key_list(backpack_gear)");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "No foreign key constraints found\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "FK Constraint:\n";
            echo "  Column: {$constraint['from']}\n";
            echo "  References: {$constraint['table']}.{$constraint['to']}\n";
            echo "  On Delete: {$constraint['on_delete']}\n";
            echo "  On Update: {$constraint['on_update']}\n\n";
        }
    }
    
    // Check if gear_id values being used exist
    echo "=== CHECKING GEAR_ID VALUES ===\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM gear_library");
    $gearCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total gear items in library: {$gearCount['total']}\n";
    
    // Check max gear_id
    $stmt = $db->query("SELECT MAX(id) as max_id FROM gear_library");
    $maxId = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Max gear_id: {$maxId['max_id']}\n";
    
    // Check specific gear_ids that were failing (from console log)
    $testIds = [1, 8, 10, 13];
    foreach ($testIds as $testId) {
        $stmt = $db->prepare("SELECT id, name FROM gear_library WHERE id = ?");
        $stmt->execute([$testId]);
        $gear = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($gear) {
            echo "Gear ID $testId: {$gear['name']} ✅\n";
        } else {
            echo "Gear ID $testId: NOT FOUND ❌\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>