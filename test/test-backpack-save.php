<?php
/**
 * Test backpack save operation to debug API errors
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Force login if not authenticated
if (!App\Services\AuthService::isAuthenticated()) {
    die('Please login first at http://localhost/BTT/');
}

$user = App\Services\AuthService::getCurrentUser();

echo "=== Testing Backpack Save API ===\n\n";
echo "Logged in as: {$user['username']} (ID: {$user['id']})\n\n";

// Test data for creating a backpack
$testData = [
    'name' => 'Test Pack ' . date('Y-m-d H:i:s'),
    'description' => 'Created via test script',
    'capacity_l' => 45,
    'weight_empty_g' => 1200,
    'type' => 'custom',
    'sections' => [
        [
            'id' => 'main',
            'name' => 'Main Compartment',
            'items' => [],
            'order' => 0
        ],
        [
            'id' => 'lid',
            'name' => 'Top Lid',
            'items' => [],
            'order' => 1
        ]
    ]
];

echo "Test data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Simulate API environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$_GET['route'] = 'backpacks';

// Set the JSON data
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($testData);

// Include API files
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/api/classes/Response.php';

echo "=== Testing Direct Function Call ===\n";

// Try to call the create function directly
try {
    // Load the backpacks route file
    require_once dirname(__DIR__) . '/api/routes/backpacks_helpers.php';
    require_once dirname(__DIR__) . '/api/routes/backpacks.php';
    
    // Mock get_request_data function for this test
    if (!function_exists('get_request_data')) {
        function get_request_data() {
            global $testData;
            return $testData;
        }
    }
    
    // Capture any output
    ob_start();
    
    // Call the create function directly
    createBackpack();
    
    $output = ob_get_clean();
    
    echo "Output from createBackpack():\n";
    echo $output . "\n\n";
    
    // Try to decode as JSON
    $response = json_decode($output, true);
    if ($response) {
        echo "Decoded response:\n";
        print_r($response);
    } else {
        echo "Failed to decode as JSON. Raw output:\n";
        echo substr($output, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Testing via CURL ===\n";

// Now test via actual HTTP request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/BTT/api/index.php?route=backpacks');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";

// Try to detect if it's HTML error
if (strpos($response, '<br') !== false || strpos($response, '<b>') !== false) {
    echo "⚠️ Response contains HTML (likely a PHP error):\n";
    // Strip HTML tags to make it readable
    $cleanError = strip_tags($response);
    echo $cleanError . "\n";
} else {
    // Try to decode as JSON
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
}

echo "\n=== Database Check ===\n";

// Check database directly
try {
    $dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if backpacks table exists and structure
    $stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='backpacks'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Backpacks table structure:\n";
        echo $result['sql'] . "\n\n";
    } else {
        echo "❌ Backpacks table not found!\n";
    }
    
    // Count existing backpacks for this user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "You currently have {$result['count']} backpack(s)\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
