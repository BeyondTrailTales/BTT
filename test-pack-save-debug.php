<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/api/classes/Database.php';

// Test page to debug pack saving
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get the most recent pack
$stmt = $db->prepare("SELECT * FROM backpacks WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$pack = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pack) {
    die("No packs found");
}

// Get all items for this pack
$stmt = $db->prepare("SELECT * FROM backpack_gear WHERE backpack_id = ? ORDER BY section, position");
$stmt->execute([$pack['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pack Save Debug</title>
    <style>
        body { font-family: monospace; background: #333; color: #fff; padding: 20px; }
        .section { margin: 20px 0; padding: 10px; background: #444; border-radius: 5px; }
        .item { margin: 5px 0; padding: 5px; background: #555; }
        pre { background: #222; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Pack Save Debug - <?= htmlspecialchars($pack['name']) ?></h1>
    
    <h2>Pack Info</h2>
    <pre><?= json_encode($pack, JSON_PRETTY_PRINT) ?></pre>
    
    <h2>Items by Section</h2>
    <?php
    $sections = [];
    foreach ($items as $item) {
        $section = $item['section'] ?? 'unknown';
        if (!isset($sections[$section])) {
            $sections[$section] = [];
        }
        $sections[$section][] = $item;
    }
    
    foreach ($sections as $sectionId => $sectionItems):
    ?>
        <div class="section">
            <h3>Section: <?= htmlspecialchars($sectionId) ?> (<?= count($sectionItems) ?> items)</h3>
            <?php foreach ($sectionItems as $item): ?>
                <div class="item">
                    <?= htmlspecialchars($item['custom_name']) ?> 
                    - Gear ID: <?= htmlspecialchars($item['gear_id'] ?? 'null') ?>
                    - Qty: <?= htmlspecialchars($item['quantity']) ?>
                    - Weight: <?= htmlspecialchars($item['custom_weight']) ?>g
                    - Category: <?= htmlspecialchars($item['custom_category']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
    <h2>All Items (Raw)</h2>
    <pre><?= json_encode($items, JSON_PRETTY_PRINT) ?></pre>
    
    <h2>Test Instructions</h2>
    <ol>
        <li>Open the pack builder and edit this pack</li>
        <li>Add some items to different sections</li>
        <li>Create a custom section and add items to it</li>
        <li>Move items between sections</li>
        <li>Refresh this page to see what's actually saved in the database</li>
    </ol>
    
    <p><a href="/BTT/pack-builder.php?id=<?= $pack['id'] ?>">Edit this pack</a> | <a href="javascript:location.reload()">Refresh</a></p>
</body>
</html>