<?php
/**
 * Check database schema for views and dependencies
 */

require_once dirname(dirname(__DIR__)) . '/app/config.php';

try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    echo "=== TABLES ===\n";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Get all views
    echo "\n=== VIEWS ===\n";
    $views = $db->query("SELECT name, sql FROM sqlite_master WHERE type='view' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($views as $view) {
        echo "  - {$view['name']}\n";
        echo "    SQL: " . substr($view['sql'], 0, 100) . "...\n";
    }
    
    // Check if trips table has user_id column
    echo "\n=== TRIPS TABLE STRUCTURE ===\n";
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
    
    // Check if backpacks table has user_id column  
    echo "\n=== BACKPACKS TABLE STRUCTURE ===\n";
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
