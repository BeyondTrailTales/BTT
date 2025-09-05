<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TRIPS TABLE SCHEMA CHECK ===\n\n";
    
    // Get table info
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in trips table:\n";
    foreach ($columns as $column) {
        echo "- {$column['name']}: {$column['type']} " . 
             ($column['notnull'] ? "(NOT NULL)" : "(NULL OK)") . 
             ($column['dflt_value'] ? " DEFAULT {$column['dflt_value']}" : "") . "\n";
    }
    
    echo "\n=== CHECKING FOR PHOTO COLUMNS ===\n";
    $photoColumns = ['photo_path', 'photo_alt_text', 'remove_photo'];
    foreach ($photoColumns as $colName) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['name'] === $colName) {
                echo "✅ {$colName}: {$column['type']}\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "❌ {$colName}: NOT FOUND\n";
        }
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $stmt = $db->query("SELECT id, title, photo_path, photo_alt_text FROM trips LIMIT 3");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trips as $trip) {
        echo "Trip {$trip['id']}: {$trip['title']}\n";
        echo "  - photo_path: " . ($trip['photo_path'] ?? 'NULL') . "\n";
        echo "  - photo_alt_text: " . ($trip['photo_alt_text'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>