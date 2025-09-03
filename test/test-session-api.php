<?php
/**
 * Test session and API authentication
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

echo "=== Session and API Authentication Test ===\n\n";

// Check PHP session
echo "1. PHP Session Status:\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Name: " . session_name() . "\n";
echo "   Session Status: " . session_status() . " (1=disabled, 2=active)\n";

if (isset($_SESSION['user_id'])) {
    echo "   ✅ User ID in session: " . $_SESSION['user_id'] . "\n";
    echo "   Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
    echo "   Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";
} else {
    echo "   ❌ No user_id in session\n";
    echo "   Session contents: " . print_r($_SESSION, true) . "\n";
}

// Check AuthService
echo "\n2. AuthService Check:\n";
if (App\Services\AuthService::isAuthenticated()) {
    echo "   ✅ User is authenticated\n";
    $user = App\Services\AuthService::getCurrentUser();
    if ($user) {
        echo "   User: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
    }
} else {
    echo "   ❌ User is NOT authenticated\n";
}

// Check cookies
echo "\n3. Cookie Check:\n";
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'BTT') !== false || strpos($name, 'PHPSESSID') !== false) {
        echo "   - $name: " . substr($value, 0, 20) . "...\n";
    }
}

// Test direct database query
echo "\n4. Direct Database Test:\n";
try {
    $dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get users
    $stmt = $db->query("SELECT id, username, email FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Found " . count($users) . " user(s):\n";
    foreach ($users as $user) {
        echo "   - {$user['username']} (ID: {$user['id']})\n";
        
        // Count backpacks for this user
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "     Backpacks: " . $result['count'] . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test API call with session
echo "\n5. Testing API Call with Current Session:\n";
echo "   Note: This only works if you're logged in via the web interface.\n";

// Simulate API environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['route'] = 'backpacks';

// Include API files
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/api/classes/Response.php';

// Capture output
ob_start();
try {
    // Call the backpacks route directly
    require_once dirname(__DIR__) . '/api/routes/backpacks.php';
    handleBackpacksRoute('GET', null);
} catch (Exception $e) {
    echo "   API Error: " . $e->getMessage() . "\n";
}
$apiResponse = ob_get_clean();

// Parse response
$response = json_decode($apiResponse, true);
if ($response) {
    if (isset($response['success']) && $response['success']) {
        echo "   ✅ API call successful\n";
        if (isset($response['data']) && is_array($response['data'])) {
            echo "   Returned " . count($response['data']) . " backpack(s)\n";
        }
    } else if (isset($response['message'])) {
        echo "   ⚠️ API returned: " . $response['message'] . "\n";
    } else {
        echo "   Response: " . substr($apiResponse, 0, 100) . "...\n";
    }
} else {
    echo "   ❌ Could not parse API response\n";
    echo "   Raw response: " . substr($apiResponse, 0, 200) . "\n";
}

echo "\n=== Test Complete ===\n";
