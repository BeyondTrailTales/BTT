<?php
/**
 * BeyondTrailTales API Configuration
 * 
 * API-specific configuration for REST endpoints
 */

// Include main app config
require_once dirname(__DIR__) . '/app/config.php';

// API Settings
define('API_VERSION', 'v1');
define('API_PREFIX', '/api');

// Database Settings (SQLite)
define('DB_PATH', BTT_SQLITE_PATH);
define('DB_TIMEOUT', 5000); // 5 seconds

// JSON Response Headers
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// CORS Settings (same-origin only for MVP)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost',
    'http://localhost:3000',
    BTT_BASE_URL
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");
    header("Access-Control-Allow-Credentials: true");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Rate Limiting (simple implementation)
session_start();
$rate_limit_key = 'api_requests_' . date('YmdH');
$_SESSION[$rate_limit_key] = ($_SESSION[$rate_limit_key] ?? 0) + 1;

if ($_SESSION[$rate_limit_key] > 1000) { // 1000 requests per hour
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded. Please try again later.'
    ]);
    exit();
}

// Helper function to get request data
function get_request_data() {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $raw_data = file_get_contents('php://input');
        return json_decode($raw_data, true) ?? [];
    } elseif (strpos($content_type, 'multipart/form-data') !== false) {
        return $_POST;
    } else {
        return $_REQUEST;
    }
}

// Helper to get HTTP method (supports method override)
function get_http_method() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Support method override for PUT/DELETE via POST
    if ($method === 'POST') {
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? 
                   $_POST['_method'] ?? 
                   $_REQUEST['_method'] ?? null;
        
        if ($override && in_array(strtoupper($override), ['PUT', 'DELETE'])) {
            $method = strtoupper($override);
        }
    }
    
    return $method;
}
