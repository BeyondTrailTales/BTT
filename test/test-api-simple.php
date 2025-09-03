<?php
// Simple API Test
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

header('Content-Type: text/plain');

echo "=== Simple API Test ===\n\n";

// Get current user
$user = \App\Services\AuthService::getCurrentUser();
echo "Current user: " . ($user ? $user['username'] : 'None') . "\n";
echo "User ID: " . ($user ? $user['id'] : 'None') . "\n\n";

// Test the API using cURL to simulate a real request
$apiUrl = 'http://localhost/BTT/api/index.php?route=backpacks';

$testData = [
    'name' => 'Test Pack CURL ' . time(),
    'description' => 'Created via cURL test',
    'capacity_l' => 65,
    'weight_empty_g' => 2000,
    'type' => 'custom',
    'sections' => [
        [
            'id' => 'main',
            'name' => 'Main Compartment',
            'items' => [],
            'order' => 0
        ]
    ]
];

echo "Testing API with cURL...\n";
echo "URL: $apiUrl\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Cookie: PHPSESSID=' . session_id()
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
    
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Decoded response:\n";
        print_r($decoded);
    } else {
        echo "Failed to decode JSON response\n";
    }
}

// Also test GET to list backpacks
echo "\n\n=== Testing GET (list backpacks) ===\n";

$ch = curl_init('http://localhost/BTT/api/index.php?route=backpacks');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Cookie: PHPSESSID=' . session_id()
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
}
