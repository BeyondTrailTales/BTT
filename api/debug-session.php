<?php
session_start();
header('Content-Type: application/json');

// Include bootstrap for AuthService
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

$response = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'is_authenticated' => AuthService::isAuthenticated(),
    'current_user' => AuthService::getCurrentUser(),
    'cookies' => $_COOKIE
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
