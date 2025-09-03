<?php
// Test API Create Endpoint Directly
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Login first
require_once dirname(__DIR__) . '/app/Services/AuthService.php';
use App\Services\AuthService;

// Login as test user
$result = AuthService::login('admin', 'Admin123!', false);
if (!$result['success']) {
    die("Failed to login: " . $result['message']);
}

$user = AuthService::getCurrentUser();
echo "Logged in as: " . $user['username'] . " (ID: " . $user['id'] . ")\n\n";

// Prepare test data
$testData = [
    'name' => 'Test Pack Direct ' . time(),
    'description' => 'Created directly',
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

echo "Test data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Set up request environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['route'] = 'backpacks';
$_SESSION['user_id'] = $user['id'];
$_SESSION['logged_in'] = true;

// Mock the input stream
$GLOBALS['php_input'] = json_encode($testData);

// Override php://input
function get_request_data() {
    return json_decode($GLOBALS['php_input'], true);
}

echo "Starting API call...\n";

// Load required files
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/routes/backpacks_helpers.php';
require_once dirname(__DIR__) . '/api/routes/backpacks_items.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Call createBackpack directly
echo "Calling createBackpack()...\n";

try {
    ob_start();
    createBackpack();
    $output = ob_get_clean();
    
    echo "Output:\n";
    echo $output . "\n\n";
    
    $response = json_decode($output, true);
    if ($response) {
        echo "Decoded response:\n";
        print_r($response);
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
