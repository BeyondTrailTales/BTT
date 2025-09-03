<?php
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$stmt = $db->query('SELECT * FROM user_gear_preferences WHERE user_id = 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User 1 Preferences:\n";
echo "View Mode: " . $row['view_mode'] . "\n";
echo "Hidden IDs: " . $row['hidden_default_ids'] . "\n";

// Decode and show array
$hidden = json_decode($row['hidden_default_ids'], true);
if ($hidden && count($hidden) > 0) {
    echo "Hidden items (" . count($hidden) . "):\n";
    foreach ($hidden as $id) {
        echo "  - $id\n";
    }
} else {
    echo "No hidden items\n";
}
?>
