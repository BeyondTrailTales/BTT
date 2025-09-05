<?php
// Test save validation
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/api/classes/Database.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get pack ID from query
$pack_id = $_GET['id'] ?? null;

if (!$pack_id) {
    die(json_encode(['error' => 'No pack ID provided']));
}

// Get pack details
$stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ? AND user_id = ?");
$stmt->execute([$pack_id, $user_id]);
$pack = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pack) {
    die(json_encode(['error' => 'Pack not found']));
}

// Get all items with full details
$stmt = $db->prepare("
    SELECT 
        bg.*,
        ug.name as gear_name,
        ug.category as gear_category,
        ug.weight_g as gear_weight
    FROM backpack_gear bg
    LEFT JOIN user_gear ug ON bg.gear_id = ug.id
    WHERE bg.backpack_id = ?
    ORDER BY bg.section, bg.position
");
$stmt->execute([$pack_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get section stats
$stmt = $db->prepare("
    SELECT 
        section,
        COUNT(*) as item_count,
        SUM(quantity) as total_quantity,
        SUM(custom_weight * quantity) as total_weight
    FROM backpack_gear
    WHERE backpack_id = ?
    GROUP BY section
");
$stmt->execute([$pack_id]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build response
$response = [
    'pack' => [
        'id' => $pack['id'],
        'name' => $pack['name'],
        'updated_at' => $pack['updated_at']
    ],
    'stats' => [
        'total_items' => count($items),
        'total_quantity' => array_sum(array_column($items, 'quantity')),
        'total_weight' => array_sum(array_column($items, 'custom_weight'))
    ],
    'sections' => $sections,
    'items' => $items
];

echo json_encode($response, JSON_PRETTY_PRINT);