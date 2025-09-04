<?php
/**
 * Simple API status checker
 */

require_once __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    $status = [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'authenticated' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'database' => 'checking...',
        'tables' => []
    ];
    
    // Test database connection
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $status['database'] = 'connected';
    
    // Check tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $status['tables'] = $tables;
    
    // Check data counts if authenticated
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        if (in_array('backpacks', $tables)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM backpacks WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $status['backpacks_count'] = $stmt->fetchColumn();
        }
        
        if (in_array('user_gear', $tables)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_gear WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $status['gear_count'] = $stmt->fetchColumn();
        }
        
        if (in_array('trips', $tables)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $status['trips_count'] = $stmt->fetchColumn();
        }
    }
    
    echo json_encode($status, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'authenticated' => isset($_SESSION['user_id']),
        'user_id' => $_SESSION['user_id'] ?? null
    ], JSON_PRETTY_PRINT);
}