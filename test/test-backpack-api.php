<?php
/**
 * Test Backpack API Response
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Login as admin
$loginResult = AuthService::login('admin', 'Admin123!', false);
if (!$loginResult['success']) {
    die("Failed to login: " . $loginResult['message'] . "\n");
}

$user = AuthService::getCurrentUser();
echo "=== Testing Backpack API as: " . $user['username'] . " ===\n\n";

// Test 1: Check if user has any backpacks in database
try {
    $db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Backpacks in database for user: " . $result['count'] . "\n\n";
    
    if ($result['count'] == 0) {
        echo "Creating a test backpack...\n";
        $stmt = $db->prepare("
            INSERT INTO backpacks (user_id, name, description, capacity, base_weight, type, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        $stmt->execute([
            $user['id'],
            'My First Backpack',
            'A great starter pack for weekend trips',
            65,
            2.5,
            'weekend',
        ]);
        echo "Test backpack created!\n\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

// Test 2: Make API request
echo "=== Testing API Response ===\n";

// Simulate session for API call
$_SESSION['user_id'] = $user['id'];
$_SESSION['logged_in'] = true;

// Set up the request environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['route'] = 'backpacks';

// Capture the API output
ob_start();
require BASE_PATH . '/api/index.php';
$apiOutput = ob_get_clean();

echo "Raw API Response:\n";
echo $apiOutput . "\n\n";

// Try to decode the response
$response = json_decode($apiOutput, true);
if ($response) {
    echo "Decoded API Response:\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    
    if (isset($response['data'])) {
        echo "Data type: " . gettype($response['data']) . "\n";
        
        if (is_array($response['data'])) {
            echo "Number of backpacks: " . count($response['data']) . "\n";
            
            foreach ($response['data'] as $backpack) {
                echo "\nBackpack:\n";
                echo "  ID: " . ($backpack['id'] ?? 'N/A') . "\n";
                echo "  Name: " . ($backpack['name'] ?? 'N/A') . "\n";
                echo "  User ID: " . ($backpack['user_id'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "No 'data' field in response\n";
        echo "Response structure:\n";
        print_r($response);
    }
} else {
    echo "Failed to decode JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

// Test 3: Direct database query to verify data
echo "\n=== Direct Database Query ===\n";
$db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
$stmt = $db->prepare("SELECT * FROM backpacks WHERE user_id = ?");
$stmt->execute([$user['id']]);
$backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Backpacks in database:\n";
foreach ($backpacks as $backpack) {
    echo "  - " . $backpack['name'] . " (ID: " . $backpack['id'] . ")\n";
}

// Logout
AuthService::logout();
echo "\n\nTest complete.\n";
