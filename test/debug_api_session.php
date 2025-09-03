<?php
/**
 * Debug script to check what's happening with user session in API
 */

// Include the API config which sets up the session
require_once dirname(__DIR__) . '/api/config.php';

use App\Services\AuthService;

header('Content-Type: text/plain');

echo "=== API Session Debug ===\n\n";

// Check session status
echo "1. Session Info:\n";
echo "   Session Name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Status: " . session_status() . " (1=DISABLED, 2=ACTIVE)\n\n";

// Check session data
echo "2. Session Data:\n";
echo "   \$_SESSION contents:\n";
print_r($_SESSION);
echo "\n";

// Check authentication
echo "3. Authentication Check:\n";
echo "   AuthService::isAuthenticated(): " . (AuthService::isAuthenticated() ? 'true' : 'false') . "\n";

// Get current user
$user = AuthService::getCurrentUser();
echo "   AuthService::getCurrentUser():\n";
if ($user) {
    echo "     ID: " . ($user['id'] ?? 'not set') . "\n";
    echo "     Username: " . ($user['username'] ?? 'not set') . "\n";
    echo "     Email: " . ($user['email'] ?? 'not set') . "\n";
} else {
    echo "     null (not authenticated)\n";
}

// Check userId helper
echo "   AuthService::userId(): " . var_export(AuthService::userId(), true) . "\n\n";

// Direct database check
echo "4. Database Check:\n";
require_once dirname(__DIR__) . '/api/classes/Database.php';
$db = Database::getInstance();

// Count all trips
$allTrips = $db->fetchOne("SELECT COUNT(*) as count FROM trips");
echo "   Total trips in DB: " . $allTrips['count'] . "\n";

// Count trips by user
$userTrips = $db->fetchAll("SELECT user_id, COUNT(*) as count FROM trips GROUP BY user_id");
echo "   Trips by user:\n";
foreach ($userTrips as $row) {
    echo "     User #" . $row['user_id'] . ": " . $row['count'] . " trips\n";
}

echo "\n5. Testing Direct Query:\n";
if ($user && isset($user['id'])) {
    $userId = $user['id'];
    echo "   Querying trips for user_id = $userId\n";
    
    $sql = "SELECT id, title, user_id FROM trips WHERE user_id = :user_id LIMIT 3";
    $trips = $db->fetchAll($sql, ['user_id' => $userId]);
    
    if ($trips) {
        echo "   Found " . count($trips) . " trips:\n";
        foreach ($trips as $trip) {
            echo "     - Trip #" . $trip['id'] . ": " . $trip['title'] . " (user_id: " . $trip['user_id'] . ")\n";
        }
    } else {
        echo "   No trips found for this user\n";
    }
} else {
    echo "   Cannot query - no authenticated user\n";
}

echo "\n=== End Debug ===\n";
?>
