<?php
/**
 * Diagnostic script to identify why trips aren't loading per user
 */

// Start session to check web context
session_start();

// Load API config
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/app/Services/AuthService.php';

// Use the AuthService class
use App\Services\AuthService;

$db = Database::getInstance();

echo "<pre style='background:#000; color:#0f0; padding:20px;'>";
echo "=== TRIPS LOADING DIAGNOSTIC ===\n\n";

// 1. Check session and authentication
echo "1. SESSION & AUTH CHECK:\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session Data: ";
print_r($_SESSION);
echo "\n";

$isAuth = AuthService::isAuthenticated();
echo "   Authenticated: " . ($isAuth ? "YES" : "NO") . "\n";

if ($isAuth) {
    $user = AuthService::getCurrentUser();
    echo "   Current User: ID=" . $user['id'] . ", Username=" . $user['username'] . "\n";
} else {
    echo "   Current User: None (not authenticated)\n";
}

// 2. Check database trips
echo "\n2. DATABASE TRIPS:\n";
$sql = "SELECT id, title, user_id, created_at FROM trips ORDER BY created_at DESC";
$trips = $db->fetchAll($sql);
echo "   Total trips in database: " . count($trips) . "\n\n";

foreach ($trips as $trip) {
    echo "   - Trip #{$trip['id']}: {$trip['title']} (User #{$trip['user_id']})\n";
}

// 3. Check if user_id column exists and has data
echo "\n3. USER_ID COLUMN CHECK:\n";
$sql = "SELECT COUNT(*) as total, 
        COUNT(user_id) as with_user_id,
        COUNT(CASE WHEN user_id IS NULL THEN 1 END) as null_user_id,
        COUNT(CASE WHEN user_id = 0 THEN 1 END) as zero_user_id
        FROM trips";
$stats = $db->fetchOne($sql);
echo "   Total trips: " . $stats['total'] . "\n";
echo "   Trips with user_id: " . $stats['with_user_id'] . "\n";
echo "   Trips with NULL user_id: " . $stats['null_user_id'] . "\n";
echo "   Trips with user_id=0: " . $stats['zero_user_id'] . "\n";

// 4. Test API endpoint directly
echo "\n4. TEST API ENDPOINT:\n";
echo "   Testing GET /api/trips...\n";

// Simulate API request with session
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/trips';

// Include required files for API
require_once dirname(__DIR__) . '/api/classes/Response.php';
require_once dirname(__DIR__) . '/app/classes/Validator.php';

// Get trips for current user
if ($isAuth) {
    $user = AuthService::getCurrentUser();
    $sql = "SELECT t.*, b.name as backpack_name, b.base_weight 
            FROM trips t
            LEFT JOIN backpacks b ON t.backpack_id = b.id
            WHERE t.user_id = :user_id
            ORDER BY t.created_at DESC";
    
    $userTrips = $db->fetchAll($sql, ['user_id' => $user['id']]);
    echo "   Trips for user #{$user['id']}: " . count($userTrips) . "\n";
    
    if (count($userTrips) > 0) {
        echo "   User's trips:\n";
        foreach ($userTrips as $trip) {
            echo "     - {$trip['title']}\n";
        }
    } else {
        echo "   No trips found for this user\n";
        
        // Check if there are any trips that should belong to this user
        echo "\n   Checking for trips that might belong to user...\n";
        $sql = "SELECT id, title, user_id FROM trips WHERE title LIKE :username OR description LIKE :username";
        $possibleTrips = $db->fetchAll($sql, ['username' => '%' . $user['username'] . '%']);
        if ($possibleTrips) {
            echo "   Found possible trips:\n";
            foreach ($possibleTrips as $trip) {
                echo "     - Trip #{$trip['id']}: {$trip['title']} (currently assigned to user #{$trip['user_id']})\n";
            }
        }
    }
} else {
    echo "   Cannot test - user not authenticated\n";
    echo "   You need to be logged in to see trips\n";
}

// 5. Check for JavaScript errors
echo "\n5. COMMON ISSUES TO CHECK:\n";
echo "   [ ] Are you logged in? (Check at /BTT/auth.php)\n";
echo "   [ ] Check browser console for JavaScript errors\n";
echo "   [ ] Check Network tab for failed API requests\n";
echo "   [ ] Clear browser cache and cookies, then log in again\n";

// 6. Users in database
echo "\n6. USERS IN DATABASE:\n";
$sql = "SELECT id, username, email FROM users";
$users = $db->fetchAll($sql);
foreach ($users as $user) {
    $sql = "SELECT COUNT(*) as count FROM trips WHERE user_id = :user_id";
    $result = $db->fetchOne($sql, ['user_id' => $user['id']]);
    echo "   User #{$user['id']}: {$user['username']} - {$result['count']} trips\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
echo "</pre>";

// Add links for convenience
if (!$isAuth) {
    echo '<p><a href="/BTT/auth.php?action=login" style="color:blue;">Go to Login Page</a></p>';
} else {
    echo '<p><a href="/BTT/trips.php" style="color:blue;">Go to Trips Page</a> | ';
    echo '<a href="/BTT/auth.php?action=logout" style="color:red;">Logout</a></p>';
}
?>
