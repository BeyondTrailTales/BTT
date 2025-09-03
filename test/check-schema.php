<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TRIPS TABLE SCHEMA ===\n";
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("- %s: %s %s %s\n", 
            $col['name'], 
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : 'NULL',
            $col['pk'] ? '(PK)' : ''
        );
    }
    
    echo "\n=== BACKPACKS TABLE SCHEMA ===\n";
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo sprintf("- %s: %s %s %s\n", 
            $col['name'], 
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : 'NULL',
            $col['pk'] ? '(PK)' : ''
        );
    }
    
    echo "\n=== CHECKING FOR USER_ID COLUMNS ===\n";
    $hasUserIdInTrips = false;
    $hasUserIdInBackpacks = false;
    
    $stmt = $db->query("PRAGMA table_info(trips)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['name'] === 'user_id') {
            $hasUserIdInTrips = true;
            break;
        }
    }
    
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['name'] === 'user_id') {
            $hasUserIdInBackpacks = true;
            break;
        }
    }
    
    echo "Trips has user_id: " . ($hasUserIdInTrips ? "YES" : "NO") . "\n";
    echo "Backpacks has user_id: " . ($hasUserIdInBackpacks ? "YES" : "NO") . "\n";
    
    // Count existing records
    $stmt = $db->query("SELECT COUNT(*) as count FROM trips");
    $tripCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nExisting trips: $tripCount\n";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM backpacks");
    $backpackCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Existing backpacks: $backpackCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
