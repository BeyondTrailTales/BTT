<?php
/**
 * Test logout redirect to homepage
 */

// Start session
session_start();

// Require necessary files
require_once dirname(__DIR__) . '/app/Services/AuthService.php';
use App\Services\AuthService;

echo "=== Testing Logout Redirect to Homepage ===\n\n";

// Step 1: Simulate logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@example.com';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "✓ User session created\n";
echo "  - User ID: " . $_SESSION['user_id'] . "\n";
echo "  - Username: " . $_SESSION['username'] . "\n\n";

// Step 2: Test the API logout response
echo "Testing API Logout Response:\n";

// Simulate POST request to logout endpoint
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['csrf_token'] = $_SESSION['csrf_token'];
$_GET['route'] = 'auth';
$_GET['id'] = 'logout';

// Capture the response
ob_start();
require __DIR__ . '/../api/index.php';
$output = ob_get_clean();

// Parse response
$response = json_decode($output, true);

if ($response) {
    echo "  - Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
    echo "  - Message: " . ($response['message'] ?? 'N/A') . "\n";
    echo "  - Redirect URL: " . ($response['redirect'] ?? 'N/A') . "\n\n";
    
    // Verify redirect URL points to homepage
    $expectedUrl = 'http://localhost/BTT/public/';
    $actualUrl = $response['redirect'] ?? '';
    
    if ($actualUrl === $expectedUrl) {
        echo "✅ SUCCESS: Logout redirects to homepage!\n";
    } else {
        echo "❌ FAILURE: Redirect URL mismatch\n";
        echo "  Expected: $expectedUrl\n";
        echo "  Actual: $actualUrl\n";
    }
} else {
    echo "Failed to parse API response.\n";
    echo "Raw output: " . substr($output, 0, 500) . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test in browser:\n";
echo "1. Go to: http://localhost/BTT/public/auth/login.php\n";
echo "2. Login with: admin / Admin123!\n";
echo "3. Click logout from the user menu\n";
echo "4. You should be redirected to the homepage (http://localhost/BTT/public/)\n";
