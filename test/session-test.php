<?php
/**
 * Simple session test
 */

// Load bootstrap which handles session initialization
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Get action from query param
$action = $_GET['action'] ?? 'status';

header('Content-Type: application/json');

switch ($action) {
    case 'set':
        // Set test session data
        $_SESSION['test_time'] = time();
        $_SESSION['test_value'] = 'Hello from session!';
        $_SESSION['test_id'] = uniqid();
        
        echo json_encode([
            'action' => 'set',
            'session_id' => session_id(),
            'data_set' => [
                'test_time' => $_SESSION['test_time'],
                'test_value' => $_SESSION['test_value'],
                'test_id' => $_SESSION['test_id']
            ]
        ]);
        break;
        
    case 'get':
        // Get session data
        echo json_encode([
            'action' => 'get',
            'session_id' => session_id(),
            'session_data' => $_SESSION,
            'test_value' => $_SESSION['test_value'] ?? 'NOT FOUND',
            'test_id' => $_SESSION['test_id'] ?? 'NOT FOUND'
        ]);
        break;
        
    case 'clear':
        // Clear session
        session_destroy();
        echo json_encode([
            'action' => 'clear',
            'message' => 'Session destroyed'
        ]);
        break;
        
    default:
        // Show session status
        echo json_encode([
            'action' => 'status',
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_name' => session_name(),
            'cookie_params' => session_get_cookie_params(),
            'session_data' => $_SESSION,
            'cookies' => $_COOKIE
        ]);
}
?>
