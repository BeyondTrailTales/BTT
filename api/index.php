<?php
/**
 * BeyondTrailTales API Router
 * 
 * Main entry point for all API requests
 * Query-based routing: api/index.php?route=trips
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Response.php';

// Auto-setup database on first run
if (!file_exists(DB_PATH) || filesize(DB_PATH) === 0) {
    require_once __DIR__ . '/setup.php';
    setupDatabase();
}

// Get route and method
$route = $_GET['route'] ?? 'health';
$method = get_http_method();

// Handle ID parameter - can be numeric or string (for special routes)
$id = $_GET['id'] ?? null;
if ($id && is_numeric($id)) {
    $id = intval($id);
}

// Get additional path segments
$action = $_GET['action'] ?? null;
// Don't force sub_id to integer - gear IDs can be strings
$sub_id = $_GET['sub_id'] ?? null;

// Store path info for routes to use
$_GET['path'] = $route . ($id ? "/$id" : "") . ($action ? "/$action" : "") . ($sub_id ? "/$sub_id" : "");

// Log request
btt_log("API Request: $method /$route" . ($id ? "/$id" : "") . ($action ? "/$action" : ""));

// Route handling
try {
    switch ($route) {
        case 'health':
            handleHealthCheck();
            break;
            
        case 'trips':
            require_once __DIR__ . '/routes/trips.php';
            handleTripsRoute($method, $id);
            break;
            
        case 'backpacks':
            require_once __DIR__ . '/routes/backpacks.php';
            handleBackpacksRoute($method, $id);
            break;
            
        case 'gear':
            // Enhanced gear library with SQLite support
            require_once __DIR__ . '/routes/gear.php';
            $dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Build path for sub-routes
            $path = 'gear';
            if ($id) $path .= '/' . $id;
            if ($action) $path .= '/' . $action;
            if ($sub_id) $path .= '/' . $sub_id;
            
            handleGearRoute($method, $path, $db);
            break;
            
        default:
            Response::notFound("Route '$route' not found");
    }
} catch (Exception $e) {
    btt_log("API Exception: " . $e->getMessage(), 'ERROR');
    Response::serverError($e->getMessage());
}

/**
 * Health check endpoint
 */
function handleHealthCheck() {
    $db = Database::getInstance();
    
    $health = [
        'status' => 'healthy',
        'version' => BTT_APP_VERSION,
        'storage_engine' => STORAGE_ENGINE,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Check database connection
    if ($db->isSQLite()) {
        try {
            $conn = $db->getConnection();
            $health['database'] = 'connected';
            
            // Check if tables exist
            $result = $conn->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $tables[] = $row['name'];
            }
            $health['tables'] = $tables;
            
        } catch (Exception $e) {
            $health['database'] = 'error';
            $health['database_error'] = $e->getMessage();
        }
    } else {
        $health['database'] = 'json_storage';
    }
    
    // Check write permissions
    $health['permissions'] = [
        'uploads' => is_writable(BTT_UPLOAD_PATH),
        'logs' => is_writable(BTT_LOGS_PATH),
        'storage' => is_writable(BTT_STORAGE_PATH)
    ];
    
    Response::success($health, 'API is healthy');
}
