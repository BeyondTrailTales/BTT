<?php
/**
 * BeyondTrailTales API Configuration
 * 
 * API-specific configuration for REST endpoints
 */

// Include main app config and bootstrap for services
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/bootstrap.php';

// API Settings
define('API_VERSION', 'v1');
define('API_PREFIX', '/api');

// Database Settings (SQLite)
if (!defined('DB_PATH')) {
    define('DB_PATH', BTT_SQLITE_PATH);
}
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
    header("Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Session is already initialized in app/bootstrap.php which we included above.
// The bootstrap file handles session configuration and starts the session.

// API Rate Limiting (simple implementation)
if (isset($_SESSION)) {
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
}

// Helper function to get request data
function get_request_data() {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    error_log("=== get_request_data DEBUG ===");
    error_log("Method: $method");
    error_log("Content-Type: $content_type");
    
    if (strpos($content_type, 'application/json') !== false) {
        $raw_data = file_get_contents('php://input');
        error_log("JSON data parsed");
        return json_decode($raw_data, true) ?? [];
    } elseif (strpos($content_type, 'multipart/form-data') !== false) {
        // For POST requests, use $_POST
        if ($method === 'POST') {
            error_log("POST multipart - using \$_POST");
            return $_POST;
        }
        
        // For PUT/PATCH requests, we need to parse multipart data manually
        // since PHP doesn't populate $_POST for non-POST requests
        if (in_array($method, ['PUT', 'PATCH'])) {
            error_log("PUT/PATCH multipart - parsing manually");
            $raw_data = file_get_contents('php://input');
            $boundary = substr($content_type, strpos($content_type, 'boundary=') + 9);
            error_log("Boundary: $boundary");
            error_log("Raw data length: " . strlen($raw_data));
            $parsed_data = parse_multipart_data($raw_data, $boundary);
            error_log("Parsed data: " . print_r($parsed_data, true));
            return $parsed_data;
        }
        
        error_log("Other multipart - using \$_POST");
        return $_POST;
    } else {
        error_log("Other content type - using \$_REQUEST");
        return $_REQUEST;
    }
}

// Helper function to parse multipart form data for PUT/PATCH requests
function parse_multipart_data($raw_data, $boundary) {
    $data = [];
    
    if (empty($raw_data) || empty($boundary)) {
        return $data;
    }
    
    // Split by boundary
    $parts = explode('--' . $boundary, $raw_data);
    
    foreach ($parts as $part) {
        if (empty(trim($part)) || $part === '--') {
            continue;
        }
        
        // Split headers and content
        $sections = explode("\r\n\r\n", $part, 2);
        if (count($sections) !== 2) {
            continue;
        }
        
        $headers = $sections[0];
        $content = rtrim($sections[1], "\r\n");
        
        // Parse the Content-Disposition header
        if (preg_match('/Content-Disposition: form-data; name="([^"]*)"/', $headers, $matches)) {
            $name = $matches[1];
            $data[$name] = $content;
        }
    }
    
    return $data;
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
