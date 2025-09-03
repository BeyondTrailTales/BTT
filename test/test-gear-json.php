<?php
/**
 * Test gear loading from JSON file
 */

// Test 1: Load and count items from gear-default.json
$jsonFile = dirname(__DIR__) . '/assets/data/gear-default.json';

echo "=== Testing Gear JSON Loading ===\n\n";

if (!file_exists($jsonFile)) {
    echo "❌ ERROR: gear-default.json not found at: $jsonFile\n";
    exit(1);
}

echo "✅ Found gear-default.json\n";

$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERROR: Invalid JSON - " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ Valid JSON file\n\n";

// Count items
$totalItems = count($data['items'] ?? []);
echo "📊 Total items in file: $totalItems\n\n";

// Count by category
$categories = [];
foreach ($data['items'] as $item) {
    $cat = $item['category'] ?? 'unknown';
    if (!isset($categories[$cat])) {
        $categories[$cat] = 0;
    }
    $categories[$cat]++;
}

echo "📦 Items by category:\n";
ksort($categories);
foreach ($categories as $cat => $count) {
    echo "  - $cat: $count items\n";
}

// Test API endpoint
echo "\n=== Testing AJAX Handler ===\n";

session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['route'] = 'gear';

// Include the ajax handler functions
include dirname(__DIR__) . '/ajax-handler.php';

echo "\n=== Complete ===\n";
