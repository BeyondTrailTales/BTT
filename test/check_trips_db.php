<?php
// Check trips table schema
$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
$pdo = new PDO('sqlite:' . $dbPath, '', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== TRIPS TABLE SCHEMA ===\n";
$stmt = $pdo->query("PRAGMA table_info('trips')");
$cols = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-20s %-15s %s %s\n", 
        $row['name'], 
        $row['type'], 
        $row['notnull'] ? 'NOT NULL' : 'NULL',
        $row['dflt_value'] ? 'DEFAULT ' . $row['dflt_value'] : '');
    $cols[$row['name']] = true;
}

echo "\n=== MISSING COLUMNS ===\n";
$required = ['user_id', 'title', 'favorite', 'completed', 'created_at', 'updated_at'];
foreach ($required as $col) {
    if (!isset($cols[$col])) {
        echo "- Missing: $col\n";
    }
}

echo "\n=== TRIPS COUNT ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(DISTINCT user_id) as users FROM trips");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total trips: {$counts['total']}\n";
echo "Unique users with trips: {$counts['users']}\n";

echo "\n=== USERS IN DATABASE ===\n";
$stmt = $pdo->query("SELECT id, username, email FROM users LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User #{$row['id']}: {$row['username']} ({$row['email']})\n";
}
?>
