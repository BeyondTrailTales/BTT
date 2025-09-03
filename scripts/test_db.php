<?php
$dbPath = 'C:/xampp2/htdocs/BTT/storage/sqlite/btt.db';
echo "Testing database at: $dbPath\n";

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";
    
    // Check what tables exist
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found. Creating initial schema...\n";
        
        // Just create the migrations table first
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL UNIQUE,
            executed_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        echo "Created migrations table\n";
    } else {
        echo "Existing tables: " . implode(', ', $tables) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
