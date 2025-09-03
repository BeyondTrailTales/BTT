<?php
/**
 * BeyondTrailTales - Centralized Bootstrap
 * 
 * This file handles application initialization:
 * - Custom session handler
 * - Authentication services
 * - CSRF protection
 * - Security headers
 * - Path definitions
 * - Configuration loading
 * - Helper functions
 * 
 * All public pages should include this file first
 */

// Define base paths first
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BTT');
}

// Load main configuration
require_once BASE_PATH . '/app/config.php';

// Only configure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings BEFORE starting
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.name', 'BTTSESSID');
    
    // Set session cookie parameters
    $cookieParams = [
        'lifetime' => 0, // Session cookie
        'path' => '/BTT/', // Use BTT path for proper cookie scope
        'domain' => '', // Empty for default domain
        'secure' => isset($_SERVER['HTTPS']), // True if HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // Lax is sufficient for same-site XHR
    ];
    session_set_cookie_params($cookieParams);
    
    // Initialize database connection for session handler
    $dbPath = BASE_PATH . '/storage/sqlite/btt.db';
    $db = null;
    
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys = ON');
    } catch (PDOException $e) {
        error_log("Failed to connect to database for sessions: " . $e->getMessage());
    }
    
    // Set up custom session handler
    require_once BASE_PATH . '/app/Services/DbSessionHandler.php';
    $sessionHandler = new App\Services\DbSessionHandler($db);
    session_set_save_handler($sessionHandler, true);
    
    // Start the session
    session_start();
} else {
    // Session already active - just load the handler class
    require_once BASE_PATH . '/app/Services/DbSessionHandler.php';
    error_log('Warning: Session already active before bootstrap.php configuration');
}

// Set security headers
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (updated to use local resources)
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ";  // Only allow local scripts
    $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com data:; ";
    $csp .= "img-src 'self' data: https:; ";
    $csp .= "connect-src 'self'";
    header('Content-Security-Policy: ' . $csp);
}

// Load authentication service
require_once BASE_PATH . '/app/Services/AuthService.php';
require_once BASE_PATH . '/app/Services/Csrf.php';

use App\Services\AuthService;
use App\Services\Csrf;

// Check for remember me cookie on first visit
if (!isset($_SESSION['user_id']) && !AuthService::isAuthenticated()) {
    // AuthService will check remember cookie and restore session if valid
    AuthService::isAuthenticated();
}

/**
 * Helper Functions
 */

/**
 * Generate URL for internal routes
 * @param string $path The route path
 * @param array $params Optional query parameters
 * @return string The full URL
 */
function route_url($path = '', $params = []) {
    $url = BASE_URL;
    
    if ($path) {
        $url .= '/' . ltrim($path, '/');
    }
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Generate URL for static assets
 * @param string $path The asset path relative to assets folder
 * @return string The full asset URL
 */
function asset_url($path) {
    return BTT_ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Check if current route is active
 * @param string $route The route to check
 * @return bool True if active
 */
function is_active($route) {
    $current_page = $_SERVER['REQUEST_URI'] ?? '';
    $current_page = str_replace(BASE_URL, '', $current_page);
    $current_page = trim($current_page, '/');
    
    // Handle exact matches and prefix matches
    if ($route === 'home' || $route === 'dashboard') {
        return empty($current_page) || $current_page === 'index.php';
    }
    
    // Remove .php extension for comparison
    $route = str_replace('.php', '', $route);
    $current_page = str_replace('.php', '', $current_page);
    
    // Check if it starts with public/
    if (strpos($current_page, 'public/') === 0) {
        $current_page = substr($current_page, 7);
    }
    
    return $current_page === $route || strpos($current_page, $route) === 0;
}

/**
 * Get active class for navigation
 * @param string $route The route to check
 * @return string 'active' if route is active, empty otherwise
 */
function active_class($route) {
    return is_active($route) ? 'active' : '';
}

/**
 * Get aria-current attribute for navigation
 * @param string $route The route to check
 * @return string 'page' if route is active, empty otherwise
 */
function aria_current($route) {
    return is_active($route) ? 'page' : '';
}

/**
 * Generate CSRF token if not exists
 * @return string The CSRF token
 */
function csrf_token() {
    return Csrf::getToken();
}

/**
 * Verify CSRF token
 * @param string $token The token to verify
 * @return bool True if valid
 */
function verify_csrf($token) {
    return Csrf::validateToken($token);
}

/**
 * Get CSRF hidden field
 * @return string HTML hidden input
 */
function csrf_field() {
    return Csrf::getHiddenField();
}

/**
 * Get CSRF meta tag
 * @return string HTML meta tag
 */
function csrf_meta() {
    return Csrf::getMetaTag();
}

/**
 * Check if user is authenticated
 * @return bool
 */
function is_authenticated() {
    return AuthService::isAuthenticated();
}

/**
 * Get current authenticated user
 * @return array|null
 */
function current_user() {
    return AuthService::getCurrentUser();
}

/**
 * Get current user ID
 * @return int|null
 */
function user_id() {
    return AuthService::getUserId();
}

/**
 * Require authentication or redirect to login
 * @param string $redirectTo URL to redirect after login
 */
function require_auth($redirectTo = null) {
    if (!AuthService::isAuthenticated()) {
        $loginUrl = BTT_PUBLIC_URL . '/auth/login.php';
        if ($redirectTo) {
            $loginUrl .= '?redirect=' . urlencode($redirectTo);
        }
        header('Location: ' . $loginUrl);
        exit;
    }
}


/**
 * Escape HTML for output
 * @param string $string The string to escape
 * @return string The escaped string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get page-specific metadata
 * @param string $key The metadata key
 * @param mixed $default Default value if not set
 * @return mixed The metadata value
 */
function page_meta($key, $default = null) {
    global $page_meta;
    return $page_meta[$key] ?? $default;
}

/**
 * Set page metadata
 * @param array $meta The metadata to set
 */
function set_page_meta($meta) {
    global $page_meta;
    $page_meta = array_merge($page_meta ?? [], $meta);
}

/**
 * Check if on test page
 * @return bool True if on test page
 */
function is_test_page() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, '/test/') !== false;
}

/**
 * Get current page ID for body class
 * @return string The page ID
 */
function get_page_id() {
    global $pageId;
    if (isset($pageId)) {
        return $pageId;
    }
    
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $uri = str_replace(BASE_URL, '', $uri);
    $uri = trim($uri, '/');
    
    if (empty($uri) || $uri === 'index.php') {
        return 'home';
    }
    
    // Remove public/ prefix and .php extension
    $uri = str_replace(['public/', '.php'], '', $uri);
    
    // Convert to page ID format
    return str_replace('/', '-', $uri);
}

/**
 * Initialize gamification if available
 */
function init_gamification() {
    if (file_exists(BASE_PATH . '/app/classes/Gamification.php')) {
        require_once BASE_PATH . '/app/classes/Gamification.php';
        
        if (class_exists('BTT\\Classes\\Gamification')) {
            $user_id = $_SESSION['user_id'] ?? 'guest';
            return new BTT\Classes\Gamification($user_id);
        }
    }
    return null;
}

/**
 * Log debug information if in development mode
 * @param mixed $data The data to log
 * @param string $label Optional label
 */
function debug_log($data, $label = '') {
    if (BTT_DEBUG) {
        $timestamp = date('Y-m-d H:i:s');
        $message = $label ? "[$label] " : '';
        $message .= is_string($data) ? $data : print_r($data, true);
        
        error_log("[$timestamp] DEBUG: $message");
    }
}

// Set default page metadata
$page_meta = [
    'title' => BTT_APP_NAME,
    'description' => BTT_APP_DESCRIPTION,
    'id' => get_page_id(),
    'canonical' => route_url($_SERVER['REQUEST_URI'] ?? ''),
    'robots' => is_test_page() ? 'noindex,nofollow' : 'index,follow'
];
