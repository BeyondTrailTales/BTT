<?php
/**
 * Test API Login Response
 */

// Start session first
session_start();

// Set up environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Include required files
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/api/routes/auth.php';

// Mock input data
$input = [
    'login' => 'admin',
    'password' => 'Admin123!',
    'remember' => false,
    'csrf_token' => 'test'
];

echo "Testing API login handler...\n";
echo "============================\n\n";

// Capture output
ob_start();
handleLogin('POST', $input);
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n\n";

// Decode and check structure
$response = json_decode($output, true);
echo "Response Structure:\n";
echo "- Has 'success' key: " . (isset($response['success']) ? 'Yes' : 'No') . "\n";
echo "- Success value: " . ($response['success'] ?? 'Not set') . "\n";
echo "- Has 'data' key: " . (isset($response['data']) ? 'Yes' : 'No') . "\n";

if (isset($response['data'])) {
    echo "- Data contains:\n";
    foreach (array_keys($response['data']) as $key) {
        echo "  - " . $key . "\n";
    }
}

echo "\nSession after API call:\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
?>
