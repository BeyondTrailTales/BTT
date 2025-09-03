<?php
/**
 * BeyondTrailTales - Centralized Bootstrap
 * 
 * This file handles application initialization:
 * - Session management
 * - Path definitions
 * - Configuration loading
 * - Helper functions
 * 
 * All public pages should include this file first
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/BTT');
}

// Load main configuration
require_once BASE_PATH . '/app/config.php';

// Initialize user session if needed
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'guest_' . substr(md5(session_id()), 0, 8);
}

if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Adventurer';
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
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token The token to verify
 * @return bool True if valid
 */
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
