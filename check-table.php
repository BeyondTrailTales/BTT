<?php
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$stmt = $db->query('PRAGMA table_info(user_gear)');
echo "user_gear table columns:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['name'] . " (" . $row['type'] . ")\n";
}
?>
