<?php
// DIRECT TEST - No complexity, just save a backpack

// 1. Setup database connection
$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Direct Database Test</h1><pre>";

// 2. Check if database works
echo "Database file exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
echo "Database size: " . filesize($dbPath) . " bytes\n\n";

// 3. Insert a test backpack directly
try {
    $sql = "INSERT INTO backpacks (user_id, name, description, created_at, updated_at) 
            VALUES (1, 'Direct Test Pack " . time() . "', 'Testing direct save', datetime('now'), datetime('now'))";
    
    $db->exec($sql);
    $id = $db->lastInsertId();
    
    echo "✓ SAVED! Backpack ID: $id\n\n";
    
    // 4. Read it back
    $result = $db->query("SELECT * FROM backpacks WHERE id = $id");
    $backpack = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "Retrieved backpack:\n";
    print_r($backpack);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- ALL BACKPACKS IN DATABASE ---\n";
$all = $db->query("SELECT id, name, user_id FROM backpacks ORDER BY id DESC LIMIT 10");
foreach ($all as $row) {
    echo "ID: {$row['id']}, Name: {$row['name']}, User: {$row['user_id']}\n";
}

echo "</pre>";
