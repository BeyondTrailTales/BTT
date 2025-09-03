<?php
/**
 * Debug API timeout issues
 */

// Include bootstrap for session
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Set test user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['email'] = 'test@example.com';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'test_user',
    'email' => 'test@example.com'
];

echo "<h1>API Debug Test</h1>";
echo "<pre>";

// Test 1: Direct cURL to API
echo "=== Test 1: Direct cURL Request ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/BTT/api/index.php?route=backpacks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Include session cookie
$sessionName = session_name();
$sessionId = session_id();
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");

// Add headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json'
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$elapsed = round($endTime - $startTime, 2);

echo "HTTP Code: $httpCode\n";
echo "Time: {$elapsed}s\n";
if ($error) {
    echo "Error: $error\n";
} else {
    $data = json_decode($response, true);
    if ($data) {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Raw Response: $response\n";
    }
}

echo "\n";

// Test 2: Create backpack request
echo "=== Test 2: Create Backpack Request ===\n";

$testData = [
    'name' => 'Debug Test Pack ' . time(),
    'description' => 'Created for debugging',
    'capacity_l' => 65,
    'weight_empty_g' => 1500,
    'type' => 'weekend',
    'sections' => [
        [
            'id' => 'main',
            'name' => 'Main Compartment',
            'items' => [
                [
                    'name' => 'Debug Item',
                    'weight_g' => 500,
                    'quantity' => 1,
                    'category' => 'test'
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/BTT/api/index.php?route=backpacks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json'
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$elapsed = round($endTime - $startTime, 2);

echo "HTTP Code: $httpCode\n";
echo "Time: {$elapsed}s\n";
if ($error) {
    echo "Error: $error\n";
} else {
    $data = json_decode($response, true);
    if ($data) {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Raw Response: $response\n";
    }
}

echo "\n";

// Test 3: Check database directly
echo "=== Test 3: Direct Database Check ===\n";
try {
    require_once dirname(__DIR__) . '/api/classes/Database.php';
    $db = Database::getInstance();
    
    if ($db->isSQLite()) {
        echo "Database: SQLite\n";
        
        // Check tables
        $tables = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
        echo "Tables found: " . count($tables) . "\n";
        foreach ($tables as $table) {
            echo "  - " . $table['name'] . "\n";
        }
        
        // Check backpacks
        $backpacks = $db->fetchAll("SELECT id, name, user_id FROM backpacks");
        echo "\nBackpacks in database: " . count($backpacks) . "\n";
        foreach ($backpacks as $pack) {
            echo "  - ID: {$pack['id']}, Name: {$pack['name']}, User: {$pack['user_id']}\n";
        }
    } else {
        echo "Database: JSON Storage\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check session data
echo "=== Test 4: Session Data ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Data:\n";
foreach ($_SESSION as $key => $value) {
    if (is_array($value) || is_object($value)) {
        echo "  $key: " . json_encode($value) . "\n";
    } else {
        echo "  $key: $value\n";
    }
}

echo "</pre>";
