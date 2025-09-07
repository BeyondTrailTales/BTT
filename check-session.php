<?php
session_start();

echo "<h2>Current Session Status:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "logged_in: " . (($_SESSION['logged_in'] ?? false) ? 'true' : 'NOT SET') . "\n";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";

echo "\nAll Session Data:\n";
print_r($_SESSION);

// Test AuthService
echo "\nTesting AuthService:\n";
require_once __DIR__ . '/app/bootstrap.php';
use App\Services\AuthService;

try {
    $isAuth = AuthService::isAuthenticated();
    echo "AuthService::isAuthenticated(): " . ($isAuth ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "AuthService error: " . $e->getMessage() . "\n";
}

echo "</pre>";

if (!($_SESSION['logged_in'] ?? false)) {
    echo "<p style='color: red;'>❌ You're missing the 'logged_in' session flag!</p>";
    echo "<p><a href='public/auth/login.php'>Login Properly Here</a></p>";
} else {
    echo "<p style='color: green;'>✅ You appear to be properly logged in!</p>";
    echo "<p><a href='trips.php'>Go to Trips Page</a></p>";
}
?>