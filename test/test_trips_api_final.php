<?php
/**
 * Test trips API with proper authentication
 * This test verifies that the API is working with the corrected session configuration
 */

// Test the trips API directly
echo "=== Testing Trips API ===\n\n";

// First test: Direct PHP check to ensure session/auth is working
echo "1. Testing session configuration:\n";
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Check session name
echo "   Session name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";

// Login as test user
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'email' => 'admin@btt.local'];
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@btt.local';
$_SESSION['logged_in'] = true;

echo "   Auth check: " . (AuthService::isAuthenticated() ? "PASSED" : "FAILED") . "\n";
echo "   User ID: " . AuthService::userId() . "\n\n";

// Second test: Make API request with session cookie
echo "2. Testing API GET request with cookie:\n";
$sessionId = session_id();
$cookieName = session_name();

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost/BTT/api/index.php?route=trips',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE => $cookieName . '=' . $sessionId,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Status: $httpCode\n";

if ($httpCode == 200) {
    echo "   ✅ Authentication successful!\n";
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success']) {
        $trips = $data['data'] ?? [];
        echo "   Trips count: " . count($trips) . "\n";
        
        if (count($trips) > 0) {
            echo "\n   Sample trip data:\n";
            $trip = $trips[0];
            echo "   - ID: " . ($trip['id'] ?? 'N/A') . "\n";
            echo "   - Title: " . ($trip['title'] ?? 'N/A') . "\n";
            echo "   - User ID: " . ($trip['user_id'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "   ❌ Authentication failed\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n3. Direct database check:\n";
$db = new PDO('sqlite:' . dirname(__DIR__) . '/storage/sqlite/btt.db');
$stmt = $db->query("SELECT id, title, user_id FROM trips WHERE user_id = 1 LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   Trip #{$row['id']}: {$row['title']} (User: {$row['user_id']})\n";
}

echo "\n=== Test Complete ===\n";
?>
