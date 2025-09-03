<?php
/**
 * Debug version of getAllTrips to see what's happening
 */

// Include API config
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/app/classes/Validator.php';

use App\Services\AuthService;

header('Content-Type: application/json');

// Check authentication
if (!AuthService::isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated',
        'debug' => [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

// Get current user
$user = AuthService::getCurrentUser();
if (!$user) {
    echo json_encode([
        'success' => false,
        'error' => 'User not found',
        'debug' => [
            'authenticated' => true,
            'session_data' => $_SESSION
        ]
    ]);
    exit;
}

$userId = $user['id'];

// Get database instance
$db = Database::getInstance();

// Build and execute query
$sql = "
    SELECT t.*, b.name as backpack_name, b.base_weight 
    FROM trips t
    LEFT JOIN backpacks b ON t.backpack_id = b.id
    WHERE t.user_id = :user_id
    ORDER BY t.created_at DESC
";

$params = ['user_id' => $userId];

// Debug: show the query and params
$debugInfo = [
    'authenticated' => true,
    'user' => $user,
    'user_id_used' => $userId,
    'sql_query' => $sql,
    'params' => $params,
    'session_data' => [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'logged_in' => $_SESSION['logged_in'] ?? null
    ]
];

try {
    $trips = $db->fetchAll($sql, $params);
    
    // Also get all trips without filter for comparison
    $allTrips = $db->fetchAll("SELECT id, title, user_id FROM trips");
    
    echo json_encode([
        'success' => true,
        'data' => $trips,
        'count' => count($trips),
        'debug' => array_merge($debugInfo, [
            'trips_found' => count($trips),
            'all_trips_in_db' => $allTrips,
            'all_trips_count' => count($allTrips)
        ])
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $debugInfo
    ], JSON_PRETTY_PRINT);
}
?>
