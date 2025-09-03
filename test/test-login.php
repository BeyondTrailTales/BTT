<?php
/**
 * Test Login Directly
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Test login
$result = AuthService::login('admin', 'Admin123!', false);

echo "Login Test Results:\n";
echo "==================\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Check session
echo "Session Status:\n";
echo "===============\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
echo "Logged in: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'Yes' : 'No') . "\n";

// Check if user is authenticated
echo "\nAuthentication Check:\n";
echo "====================\n";
echo "Is Authenticated: " . (AuthService::isAuthenticated() ? 'Yes' : 'No') . "\n";

if (AuthService::isAuthenticated()) {
    $user = AuthService::getCurrentUser();
    echo "Current User: " . json_encode($user, JSON_PRETTY_PRINT) . "\n";
}

echo "\nIf login succeeded above, the redirect should work.\n";
echo "Try manually going to: http://localhost/BTT/dashboard.php\n";
?>
