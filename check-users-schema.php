<?php
session_start();
header('Content-Type: text/plain');

echo "=== USERS TABLE SCHEMA CHECK ===\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get table info
    $stmt = $db->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in users table:\n";
    foreach ($columns as $column) {
        echo "- {$column['name']}: {$column['type']} " . 
             ($column['notnull'] ? "(NOT NULL)" : "(NULL OK)") . 
             ($column['dflt_value'] ? " DEFAULT {$column['dflt_value']}" : "") . "\n";
    }
    
    echo "\n=== SAMPLE USER DATA ===\n";
    $stmt = $db->query("SELECT * FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found!\n";
    } else {
        foreach ($users as $user) {
            echo "User data:\n";
            foreach ($user as $key => $value) {
                echo "  $key: " . ($value ?? 'NULL') . "\n";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>