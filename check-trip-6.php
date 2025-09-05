<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

header('Content-Type: application/json');

// Get trip 6 data directly from database
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$stmt = $db->prepare("SELECT id, title, photo_path, photo_alt_text, updated_at FROM trips WHERE id = 6");
$stmt->execute();
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if photo file exists
if ($trip && $trip['photo_path']) {
    $trip['photo_exists'] = file_exists(__DIR__ . '/' . $trip['photo_path']);
    $trip['full_path'] = __DIR__ . '/' . $trip['photo_path'];
}

// Also get what AJAX handler returns
$ajaxResponse = file_get_contents('http://localhost/BTT/ajax-handler.php?route=trips&id=6', false, stream_context_create([
    'http' => [
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]));

$result = [
    'database_direct' => $trip,
    'ajax_handler_response' => json_decode($ajaxResponse, true),
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null
];

echo json_encode($result, JSON_PRETTY_PRINT);