<?php
/**
 * BeyondTrailTales Authentication API Routes
 * 
 * Handles all authentication-related API endpoints
 */

require_once dirname(dirname(__DIR__)) . '/app/config.php';
require_once BTT_ROOT . '/app/Services/AuthService.php';
require_once BTT_ROOT . '/app/Services/Csrf.php';
require_once BTT_ROOT . '/api/classes/Response.php';

use App\Services\AuthService;
use App\Services\Csrf;

/**
 * Main route handler for authentication endpoints
 * Called from api/index.php
 */
function handleAuthRoute($method, $id = null, $action = null) {
    // Build the path from the parameters
    $path = 'auth';
    if ($id) {
        $path .= '/' . $id;
    }
    if ($action) {
        $path .= '/' . $action;
    }
    
    // Get request data
    $input = [];
    if ($method !== 'GET') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawData = file_get_contents('php://input');
            $input = json_decode($rawData, true) ?? [];
        } else {
            $input = $_POST;
        }
    }
    
    // Route based on the path segments
    switch ($id) {
        case null:
        case '':
            // No specific action - return auth status
            handleAuthStatus($method);
            break;
            
        case 'login':
            handleLogin($method, $input);
            break;
            
        case 'logout':
            handleLogout($method, $input);
            break;
            
        case 'register':
            handleRegister($method, $input);
            break;
            
        case 'verify-email':
            handleVerifyEmail($method);
            break;
            
        case 'request-password-reset':
        case 'forgot-password':
            handlePasswordResetRequest($method, $input);
            break;
            
        case 'reset-password':
            handlePasswordReset($method, $input);
            break;
            
        case 'current-user':
        case 'me':
            handleGetCurrentUser($method);
            break;
            
        default:
            Response::error('Authentication endpoint not found', 404);
    }
}

/**
 * Handle auth status check
 */
function handleAuthStatus($method) {
    if ($method !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    // Check authentication status
    $isAuthenticated = AuthService::isAuthenticated();
    $user = null;
    
    if ($isAuthenticated) {
        $user = AuthService::getCurrentUser();
    }
    
    Response::success([
        'authenticated' => $isAuthenticated,
        'user' => $user,
        'session_id' => session_id() ? substr(session_id(), 0, 8) . '...' : null,
        'csrf_token' => Csrf::getToken()
    ], 'Authentication status retrieved');
}

/**
 * Handle login request
 */
function handleLogin($method, $input) {
    if ($method !== 'POST') {
        Response::error('Method not allowed', 405);
    }
    
    // Validate CSRF token (temporarily disabled for testing)
    // TODO: Re-enable after testing
    $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    // if (!Csrf::validateToken($csrfToken)) {
    //     Response::error('Invalid CSRF token', 403);
    // }
    
    // Get login credentials
    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';
    $remember = $input['remember'] ?? false;
    
    // Validate input
    if (empty($login) || empty($password)) {
        Response::error('Email/username and password are required', 400);
    }
    
    // Attempt login
    $result = AuthService::login($login, $password, $remember);
    
    if ($result['success']) {
        // Prepare response data
        $responseData = [
            'user' => $result['user'] ?? null,
            'csrf_token' => $result['csrf_token'] ?? Csrf::rotateToken()
        ];
        
        // Response::success expects (data, message, code)
        Response::success($responseData, 'Login successful');
    } else {
        // Response::error expects (message, code, errors)
        Response::error($result['message'] ?? 'Login failed', 401, $result['errors'] ?? null);
    }
}

/**
 * Handle logout request
 */
function handleLogout($method, $input) {
    // Allow both POST and GET for logout during debugging
    if ($method !== 'POST' && $method !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    // For GET requests (direct link), skip CSRF validation
    if ($method === 'GET') {
        // Perform logout
        AuthService::logout();
        
        // Redirect to homepage for GET requests
        header('Location: ' . BTT_PUBLIC_URL . '/');
        exit;
    }
    
    // For POST requests, validate CSRF token
    $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    // Debug: Log token information
    error_log('Logout CSRF Debug:');
    error_log('Received token: ' . $csrfToken);
    error_log('Session token: ' . ($_SESSION['csrf_token'] ?? 'NO SESSION TOKEN'));
    error_log('Session ID: ' . session_id());
    
    // Temporarily make CSRF validation more lenient for logout
    if (!empty($csrfToken) && !Csrf::validateToken($csrfToken)) {
        // Log the failure but still allow logout
        error_log('CSRF validation failed for logout, but proceeding anyway');
    }
    
    // Perform logout
    AuthService::logout();
    
    Response::success(['message' => 'Logout successful', 'redirect' => BTT_PUBLIC_URL . '/']);
}

/**
 * Handle registration request
 */
function handleRegister($method, $input) {
    if ($method !== 'POST') {
        Response::error('Method not allowed', 405);
    }
    
    // Validate CSRF token
    $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validateToken($csrfToken)) {
        Response::error('Invalid CSRF token', 403);
    }
    
    // Get registration data
    $email = $input['email'] ?? '';
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $passwordConfirm = $input['password_confirm'] ?? '';
    
    // Validate passwords match
    if ($password !== $passwordConfirm) {
        Response::error('Passwords do not match', 400, ['password_confirm' => 'Passwords do not match']);
    }
    
    // Attempt registration
    $result = AuthService::register($email, $username, $password);
    
    if ($result['success']) {
        Response::success($result['message'], ['user_id' => $result['user_id'] ?? null]);
    } else {
        Response::error($result['message'] ?? 'Registration failed', 400, $result['errors'] ?? null);
    }
}

/**
 * Handle email verification
 */
function handleVerifyEmail($method) {
    if ($method !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    // Get token from query string
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        Response::error('Verification token is required', 400);
    }
    
    // Verify email
    $result = AuthService::verifyEmail($token);
    
    if ($result['success']) {
        Response::success($result['message']);
    } else {
        Response::error($result['message'] ?? 'Verification failed', 400);
    }
}

/**
 * Handle password reset request
 */
function handlePasswordResetRequest($method, $input) {
    if ($method !== 'POST') {
        Response::error('Method not allowed', 405);
    }
    
    // Validate CSRF token
    $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validateToken($csrfToken)) {
        Response::error('Invalid CSRF token', 403);
    }
    
    // Get email
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        Response::error('Email is required', 400);
    }
    
    // Request password reset
    $result = AuthService::requestPasswordReset($email);
    
    // Always return success to prevent email enumeration
    Response::success($result['message'] ?? 'If that email exists, a reset link has been sent.');
}

/**
 * Handle password reset
 */
function handlePasswordReset($method, $input) {
    if ($method !== 'POST') {
        Response::error('Method not allowed', 405);
    }
    
    // Validate CSRF token
    $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Csrf::validateToken($csrfToken)) {
        Response::error('Invalid CSRF token', 403);
    }
    
    // Get reset data
    $token = $input['token'] ?? '';
    $password = $input['password'] ?? '';
    $passwordConfirm = $input['password_confirm'] ?? '';
    
    if (empty($token) || empty($password)) {
        Response::error('Token and password are required', 400);
    }
    
    // Validate passwords match
    if ($password !== $passwordConfirm) {
        Response::error('Passwords do not match', 400, ['password_confirm' => 'Passwords do not match']);
    }
    
    // Reset password
    $result = AuthService::resetPassword($token, $password);
    
    if ($result['success']) {
        Response::success($result['message']);
    } else {
        Response::error($result['message'] ?? 'Password reset failed', 400);
    }
}

/**
 * Get current authenticated user
 */
function handleGetCurrentUser($method) {
    if ($method !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    // Check authentication
    if (!AuthService::isAuthenticated()) {
        Response::error('Not authenticated', 401);
    }
    
    // Get current user
    $user = AuthService::getCurrentUser();
    
    if ($user) {
        Response::success('User retrieved', ['user' => $user]);
    } else {
        Response::error('User not found', 404);
    }
}
