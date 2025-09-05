<?php
// Direct database inspection for trip photos
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database path
$dbPath = __DIR__ . '/storage/sqlite/btt.db';

if (!file_exists($dbPath)) {
    die("Database not found at: $dbPath");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Trip Photos Database Inspection</h2>";
    echo "<p>Database: $dbPath</p>";
    
    // 1. Show all trips with photo_path values
    echo "<h3>1. All Trips with Photo Paths:</h3>";
    $stmt = $db->query("SELECT id, user_id, title, photo_path, created_at, updated_at FROM trips ORDER BY id");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Photo Path</th><th>Created</th><th>Updated</th></tr>";
    
    foreach ($trips as $trip) {
        $photoDisplay = $trip['photo_path'] ? htmlspecialchars($trip['photo_path']) : '<em style="color:red">NULL</em>';
        echo "<tr>";
        echo "<td>{$trip['id']}</td>";
        echo "<td>{$trip['user_id']}</td>";
        echo "<td>" . htmlspecialchars($trip['title']) . "</td>";
        echo "<td>$photoDisplay</td>";
        echo "<td>{$trip['created_at']}</td>";
        echo "<td>{$trip['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Specific comparison between trip ID 1 and 6
    echo "<h3>2. Detailed Comparison - Trip ID 1 vs Trip ID 6:</h3>";
    $stmt = $db->prepare("SELECT * FROM trips WHERE id IN (1, 6)");
    $stmt->execute();
    $comparison = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comparison as $trip) {
        echo "<h4>Trip ID {$trip['id']}:</h4>";
        echo "<pre>";
        foreach ($trip as $key => $value) {
            echo "$key: " . ($value !== null ? htmlspecialchars($value) : 'NULL') . "\n";
        }
        echo "</pre>";
    }
    
    // 3. Check table schema
    echo "<h3>3. Trips Table Schema:</h3>";
    $stmt = $db->query("PRAGMA table_info(trips)");
    $schema = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
    foreach ($schema as $column) {
        echo "<tr>";
        echo "<td>{$column['name']}</td>";
        echo "<td>{$column['type']}</td>";
        echo "<td>{$column['notnull']}</td>";
        echo "<td>{$column['dflt_value']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Check for triggers
    echo "<h3>4. Database Triggers:</h3>";
    $stmt = $db->query("SELECT name, sql FROM sqlite_master WHERE type='trigger' AND tbl_name='trips'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triggers)) {
        echo "<p>No triggers found on trips table.</p>";
    } else {
        foreach ($triggers as $trigger) {
            echo "<h4>Trigger: {$trigger['name']}</h4>";
            echo "<pre>" . htmlspecialchars($trigger['sql']) . "</pre>";
        }
    }
    
    // 5. Check indexes
    echo "<h3>5. Indexes on trips table:</h3>";
    $stmt = $db->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='trips'");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($indexes as $index) {
        echo "<p><strong>{$index['name']}:</strong> " . htmlspecialchars($index['sql'] ?: 'PRIMARY KEY') . "</p>";
    }
    
    // 6. Recent photo updates
    echo "<h3>6. Recent Photo Updates (last 10):</h3>";
    $stmt = $db->query("SELECT id, title, photo_path, updated_at FROM trips WHERE photo_path IS NOT NULL ORDER BY updated_at DESC LIMIT 10");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent)) {
        echo "<p>No trips with photos found.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Photo Path</th><th>Updated At</th></tr>";
        foreach ($recent as $trip) {
            echo "<tr>";
            echo "<td>{$trip['id']}</td>";
            echo "<td>" . htmlspecialchars($trip['title']) . "</td>";
            echo "<td>" . htmlspecialchars($trip['photo_path']) . "</td>";
            echo "<td>{$trip['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 7. Check for any constraints
    echo "<h3>7. Foreign Keys and Constraints:</h3>";
    $stmt = $db->query("PRAGMA foreign_key_list(trips)");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fks)) {
        echo "<p>No foreign key constraints on trips table.</p>";
    } else {
        foreach ($fks as $fk) {
            echo "<p>Foreign Key: " . print_r($fk, true) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Error:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
?>