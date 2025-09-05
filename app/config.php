<?php
/**
 * BeyondTrailTales MVP - Application Configuration
 * 
 * Main configuration for the simplified trip planning application
 * Following Context7 best practices for security and maintainability
 */

// Application Environment
define('BTT_ENV', 'development'); // development or production
define('BTT_DEBUG', BTT_ENV === 'development');

// Base URLs
define('BTT_BASE_URL', 'http://localhost/BTT');
define('BTT_API_URL', BTT_BASE_URL . '/api');
define('BTT_ASSETS_URL', BTT_BASE_URL . '/assets');
define('BTT_PUBLIC_URL', BTT_BASE_URL); // Now points to root
define('BTT_VENDOR_URL', BTT_BASE_URL . '/vendor'); // Vendor assets URL

// Storage Paths (absolute)
define('BTT_ROOT', dirname(__DIR__));
define('BTT_STORAGE_PATH', BTT_ROOT . '/storage');
define('BTT_SQLITE_PATH', BTT_STORAGE_PATH . '/sqlite/btt.db');
define('BTT_JSON_PATH', BTT_STORAGE_PATH . '/json');
define('BTT_LOGS_PATH', BTT_STORAGE_PATH . '/logs');
define('BTT_CACHE_PATH', BTT_STORAGE_PATH . '/cache');
define('BTT_UPLOAD_PATH', BTT_ROOT . '/assets/img/trips');

// Storage Engine (sqlite or json)
// Will fallback to json if SQLite is not available
define('STORAGE_ENGINE', extension_loaded('pdo_sqlite') ? 'sqlite' : 'json');

// Database path alias for compatibility
define('DB_PATH', BTT_SQLITE_PATH);

// Upload Settings
define('BTT_UPLOAD_MAX_SIZE', 4 * 1024 * 1024); // 4MB
define('BTT_UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png']);
define('BTT_UPLOAD_ALLOWED_MIMES', [
    'image/jpeg',
    'image/jpg', 
    'image/png'
]);

// Application Info
define('BTT_APP_NAME', 'BeyondTrailTales');
define('BTT_APP_VERSION', '1.0.0-MVP');
define('BTT_APP_DESCRIPTION', 'Simple Trip Planning with Backpack Management');

// Session Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_name('BTT_SESSION');
}

// Error Reporting
if (BTT_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Set error log path
ini_set('error_log', BTT_LOGS_PATH . '/app.log');

// Timezone
date_default_timezone_set('UTC');

// Create necessary directories if they don't exist
$directories = [
    BTT_STORAGE_PATH,
    BTT_SQLITE_PATH => dirname(BTT_SQLITE_PATH),
    BTT_JSON_PATH,
    BTT_LOGS_PATH,
    BTT_CACHE_PATH,
    BTT_UPLOAD_PATH
];

foreach ($directories as $key => $dir) {
    $path = is_string($key) ? $dir : $dir;
    if (!file_exists($path)) {
        @mkdir($path, 0777, true);
    }
}

// Helper function to get config value
function btt_config($key, $default = null) {
    $configs = [
        'env' => BTT_ENV,
        'debug' => BTT_DEBUG,
        'storage_engine' => STORAGE_ENGINE,
        'base_url' => BTT_BASE_URL,
        'api_url' => BTT_API_URL,
        'upload_max_size' => BTT_UPLOAD_MAX_SIZE,
        'upload_allowed_types' => BTT_UPLOAD_ALLOWED_TYPES
    ];
    
    return $configs[$key] ?? $default;
}

// Log helper function
function btt_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    @file_put_contents(BTT_LOGS_PATH . '/app.log', $log_message, FILE_APPEND | LOCK_EX);
}
