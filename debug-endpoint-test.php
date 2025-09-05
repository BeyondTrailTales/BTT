<?php
session_start();
header('Content-Type: text/plain');

echo "=== BTT API Endpoint Debug Test ===\n\n";

// Check session
echo "Session Info:\n";
echo "- Session ID: " . session_id() . "\n";
echo "- User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "- User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n\n";

// Check if we can access the API files
echo "File System Check:\n";
echo "- ajax-handler.php exists: " . (file_exists(__DIR__ . '/ajax-handler.php') ? 'YES' : 'NO') . "\n";
echo "- api/index.php exists: " . (file_exists(__DIR__ . '/api/index.php') ? 'YES' : 'NO') . "\n";
echo "- api/routes/trips.php exists: " . (file_exists(__DIR__ . '/api/routes/trips.php') ? 'YES' : 'NO') . "\n\n";

// Test a simple GET request to ajax-handler
echo "Testing ajax-handler.php?route=trips:\n";
$url = 'http://localhost/BTT/ajax-handler.php?route=trips';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE']
    ]
]);

$result = file_get_contents($url, false, $context);
if ($result === false) {
    echo "- ERROR: Could not access ajax-handler\n";
} else {
    echo "- SUCCESS: Got response from ajax-handler\n";
    echo "- Response length: " . strlen($result) . " bytes\n";
    echo "- First 200 chars: " . substr($result, 0, 200) . "\n";
}

echo "\n";

// Test main API
echo "Testing api/index.php?route=trips:\n";
$apiUrl = 'http://localhost/BTT/api/index.php?route=trips';
$apiResult = file_get_contents($apiUrl, false, $context);
if ($apiResult === false) {
    echo "- ERROR: Could not access main API\n";
} else {
    echo "- SUCCESS: Got response from main API\n";
    echo "- Response length: " . strlen($apiResult) . " bytes\n";
    echo "- First 200 chars: " . substr($apiResult, 0, 200) . "\n";
}

echo "\n=== End Debug Test ===\n";
?>