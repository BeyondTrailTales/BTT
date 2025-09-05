<?php
session_start();
header('Content-Type: text/plain');

echo "=== SESSION CONFIGURATION ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session name: " . session_name() . "\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Session cookie path: " . session_get_cookie_params()['path'] . "\n";
echo "Session cookie domain: " . session_get_cookie_params()['domain'] . "\n";
echo "Session cookie lifetime: " . session_get_cookie_params()['lifetime'] . "\n";
echo "Session cookie httponly: " . (session_get_cookie_params()['httponly'] ? 'YES' : 'NO') . "\n";
echo "Session cookie secure: " . (session_get_cookie_params()['secure'] ? 'YES' : 'NO') . "\n";

echo "\n=== SESSION DATA ===\n";
print_r($_SESSION);

echo "\n=== COOKIES ===\n";
print_r($_COOKIE);

echo "\n=== TEST LOGIN ===\n";

// Try to login manually
require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for admin user
    $stmt = $db->query("SELECT id, username, email FROM users WHERE username = 'admin' OR email = 'admin@example.com' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found admin user:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        
        // Manually set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        
        echo "\n✅ Manually logged in as admin\n";
        echo "Session after login:\n";
        print_r($_SESSION);
        
    } else {
        echo "❌ No admin user found in database\n";
        
        // Check all users
        $stmt = $db->query("SELECT id, username, email FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nAvailable users:\n";
        foreach ($users as $u) {
            echo "- ID: {$u['id']}, Username: {$u['username']}, Email: {$u['email']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>