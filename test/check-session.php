<?php
/**
 * Check Session Helper
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

header('Content-Type: application/json');

if (AuthService::isAuthenticated()) {
    $user = AuthService::getCurrentUser();
    echo json_encode([
        'authenticated' => true,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
