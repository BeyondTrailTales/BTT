<?php
/**
 * BeyondTrailTales - Main Router
 * Handles all routing for the application
 */

// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';
use App\Services\AuthService;

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/BTT/', '', $request_uri);
$request_uri = strtok($request_uri, '?'); // Remove query string
$request_uri = trim($request_uri, '/');

// If empty, show homepage
if (empty($request_uri)) {
    if (AuthService::isAuthenticated()) {
        require __DIR__ . '/dashboard.php';
    } else {
        if (file_exists(__DIR__ . '/landing.php')) {
            require __DIR__ . '/landing.php';
        } else {
            header('Location: /BTT/public/auth/login.php');
            exit;
        }
    }
    exit;
}

// Check if it's a direct file request
$file_path = __DIR__ . '/' . $request_uri;
if (file_exists($file_path) && is_file($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
    require $file_path;
    exit;
}

// Check public directory
$public_path = __DIR__ . '/public/' . $request_uri;
if (file_exists($public_path) && is_file($public_path) && pathinfo($public_path, PATHINFO_EXTENSION) === 'php') {
    require $public_path;
    exit;
}

// Handle special routes
switch ($request_uri) {
    case 'dashboard':
        require __DIR__ . '/dashboard.php';
        break;
    
    case 'trips':
        require __DIR__ . '/trips.php';
        break;
        
    case 'backpacks':
        require __DIR__ . '/backpacks.php';
        break;
        
    case 'gear':
        require __DIR__ . '/gear.php';
        break;
        
    case 'login':
        require __DIR__ . '/public/auth/login.php';
        break;
        
    case 'register':
        require __DIR__ . '/public/auth/register.php';
        break;
        
    case 'logout':
        AuthService::logout();
        header('Location: /BTT/');
        exit;
        
    default:
        // 404 page
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>404 - Page Not Found</title>
            <style>
                body {
                    font-family: system-ui, -apple-system, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }
                .error-box {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    text-align: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }
                h1 { color: #333; }
                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                }
                a:hover { opacity: 0.9; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>404 - Page Not Found</h1>
                <p>The page you're looking for doesn't exist.</p>
                <a href="/BTT/">Go Home</a>
            </div>
        </body>
        </html>
        <?php
        break;
}
?>
