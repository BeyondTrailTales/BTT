<?php
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check preferences
$stmt = $db->query("SELECT * FROM user_gear_preferences WHERE user_id = 1");
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User 1 Preferences:\n";
if ($prefs) {
    echo "ID: " . $prefs['id'] . "\n";
    echo "User ID: " . $prefs['user_id'] . "\n";
    echo "View Mode: " . $prefs['view_mode'] . "\n";
    echo "Hidden IDs: " . $prefs['hidden_default_ids'] . "\n";
    echo "Preferred Units: " . $prefs['preferred_units'] . "\n";
    echo "Last Sort: " . $prefs['last_sort'] . "\n";
} else {
    echo "No preferences found for user 1\n";
}
?>
