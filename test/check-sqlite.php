<?php
$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/storage/sqlite/btt.db');
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
echo "Tables in SQLite database:\n";
while($row = $stmt->fetch()) {
    echo "- " . $row['name'] . "\n";
}

// Check if backpacks table exists
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name='backpacks'");
$result = $stmt->fetch();
if ($result['count'] > 0) {
    echo "\nBackpacks table exists!\n";
    
    // Count records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM backpacks");
    $result = $stmt->fetch();
    echo "Number of backpacks in SQLite: " . $result['count'] . "\n";
} else {
    echo "\nBackpacks table does NOT exist!\n";
}
?>
