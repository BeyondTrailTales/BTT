<?php
/**
 * BeyondTrailTales - Main Entry Point
 * This file routes to the appropriate page based on the URL
 */

// Start session
session_start();

// Define base path
define('BASE_PATH', __DIR__);
define('BASE_URL', '/BTT');

// Include necessary files
$config_file = BASE_PATH . '/app/config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_uri = str_replace(BASE_URL, '', $request_uri);
$base_uri = trim($base_uri, '/');

// Route to specific pages
switch($base_uri) {
    case '':
    case 'index.php':
        // Dashboard is the home page
        include BASE_PATH . '/dashboard.php';
        break;
        
    case 'dashboard':
    case 'dashboard.php':
        include BASE_PATH . '/dashboard.php';
        break;
        
    case 'backpacks':
    case 'backpacks.php':
        if (file_exists(BASE_PATH . '/public/backpacks.php')) {
            include BASE_PATH . '/public/backpacks.php';
        } else {
            include BASE_PATH . '/backpacks.php';
        }
        break;
        
    case 'trips':
        // Use the enhanced trips page for /trips
        if (file_exists(BASE_PATH . '/public/trips-enhanced.php')) {
            include BASE_PATH . '/public/trips-enhanced.php';
        } else if (file_exists(BASE_PATH . '/public/trips.php')) {
            include BASE_PATH . '/public/trips.php';
        } else {
            include BASE_PATH . '/trips.php';
        }
        break;
        
    case 'trips.php':
        // Use the regular trips.php for direct access (includes trip editor)
        if (file_exists(BASE_PATH . '/public/trips.php')) {
            include BASE_PATH . '/public/trips.php';
        } else {
            include BASE_PATH . '/trips.php';
        }
        break;
        
    case 'gamification':
    case 'gamification.php':
        if (file_exists(BASE_PATH . '/public/gamification.php')) {
            include BASE_PATH . '/public/gamification.php';
        } else {
            // Create a simple gamification page if it doesn't exist
            include BASE_PATH . '/dashboard.php';
        }
        break;
        
    case 'test':
        if (file_exists(BASE_PATH . '/test/index.php')) {
            include BASE_PATH . '/test/index.php';
        } else {
            include BASE_PATH . '/test/gamification-test.php';
        }
        break;
        
    case 'api':
        if (file_exists(BASE_PATH . '/api/index.php')) {
            include BASE_PATH . '/api/index.php';
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'API not configured']);
        }
        break;
        
    default:
        // Check if it's a public file
        if (strpos($base_uri, 'public/') === 0) {
            $file_path = BASE_PATH . '/' . $base_uri;
            if (file_exists($file_path) && is_file($file_path)) {
                include $file_path;
                break;
            }
        }
        
        // Check if it's a test file
        if (strpos($base_uri, 'test/') === 0) {
            $file_path = BASE_PATH . '/' . $base_uri;
            if (file_exists($file_path) && is_file($file_path)) {
                include $file_path;
                break;
            }
        }
        
        // Try to find the file in root
        $file_path = BASE_PATH . '/' . $base_uri;
        if (file_exists($file_path) && is_file($file_path)) {
            include $file_path;
        } else {
            // 404 page with forest theme
            http_response_code(404);
            ?>
            <!DOCTYPE html>
            <html lang="en" data-theme="forest-dark">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>404 - Page Not Found | BeyondTrailTales</title>
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
                <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forest-tokens.css">
                <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forest-base.css">
                <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forest-components.css">
                <style>
                    body {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        background: var(--gradient-forest);
                        margin: 0;
                        padding: var(--space-4);
                    }
                    .error-container {
                        background: var(--glass-bg);
                        backdrop-filter: var(--glass-blur-heavy);
                        border: 1px solid var(--glass-border);
                        border-radius: var(--radius-2xl);
                        padding: var(--space-8);
                        text-align: center;
                        max-width: 500px;
                    }
                    .error-icon {
                        font-size: 4rem;
                        margin-bottom: var(--space-4);
                    }
                    .error-title {
                        font-size: var(--text-3xl);
                        color: var(--forest-mint);
                        margin-bottom: var(--space-2);
                    }
                    .error-message {
                        color: var(--text-secondary);
                        margin-bottom: var(--space-6);
                    }
                    .btn-home {
                        display: inline-block;
                        padding: var(--space-3) var(--space-6);
                        background: var(--gradient-success);
                        color: var(--forest-deep);
                        text-decoration: none;
                        border-radius: var(--radius-full);
                        font-weight: var(--font-bold);
                        transition: var(--transition-all);
                    }
                    .btn-home:hover {
                        transform: translateY(-2px);
                        box-shadow: var(--shadow-glow-md);
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">🌲</div>
                    <h1 class="error-title">404 - Lost in the Forest</h1>
                    <p class="error-message">The page you're looking for doesn't exist.</p>
                    <a href="<?php echo BASE_URL; ?>/" class="btn-home">Return to Base Camp</a>
                </div>
            </body>
            </html>
            <?php
        }
        break;
}
?>
