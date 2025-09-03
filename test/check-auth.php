<?php
/**
 * Test authentication and session status
 */

// Load bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Get session info
$sessionInfo = [
    'session_id' => session_id() ? substr(session_id(), 0, 8) . '...' : 'No session',
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'is_authenticated' => AuthService::isAuthenticated(),
    'current_user' => AuthService::getCurrentUser(),
    'php_version' => PHP_VERSION,
    'server_info' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? '',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? ''
    ]
];

// Pretty print JSON
echo json_encode($sessionInfo, JSON_PRETTY_PRINT);
?>
