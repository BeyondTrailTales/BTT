<?php
/**
 * Test Registration Script
 * Creates a test user account
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Test data
$email = 'testuser@example.com';
$username = 'testuser';
$password = 'TestPass123!';

echo "Testing Registration System\n";
echo "==========================\n\n";

// Attempt registration
echo "Registering user:\n";
echo "  Email: $email\n";
echo "  Username: $username\n";
echo "  Password: TestPass123!\n\n";

$result = AuthService::register($email, $username, $password);

if ($result['success']) {
    echo "✓ Registration successful!\n";
    echo "  User ID: " . $result['user_id'] . "\n";
    echo "  Message: " . $result['message'] . "\n\n";
    
    // Now test login
    echo "Testing login with new account...\n";
    $loginResult = AuthService::login($username, $password);
    
    if ($loginResult['success']) {
        echo "✓ Login successful!\n";
        echo "  User: " . json_encode($loginResult['data']['user']) . "\n";
    } else {
        echo "✗ Login failed: " . $loginResult['message'] . "\n";
    }
} else {
    echo "✗ Registration failed!\n";
    echo "  Message: " . $result['message'] . "\n";
    if (isset($result['errors'])) {
        echo "  Errors: " . json_encode($result['errors']) . "\n";
    }
}

// Also create an admin account
echo "\n\nCreating admin account...\n";
$adminResult = AuthService::register('admin@example.com', 'admin', 'Admin123!');
if ($adminResult['success']) {
    echo "✓ Admin account created!\n";
} else {
    echo "Note: " . $adminResult['message'] . "\n";
}

echo "\n\nYou can now login with:\n";
echo "  Username: testuser\n";
echo "  Password: TestPass123!\n";
echo "\nOr:\n";
echo "  Username: admin\n";
echo "  Password: Admin123!\n";
?>
