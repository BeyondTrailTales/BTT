<?php
// API Diagnostic Tool - Check session and user data scoping
require_once __DIR__ . '/../app/bootstrap.php';
use App\Services\AuthService;

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Diagnostic - BTT</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; margin: 2rem; background: #f3f4f6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem; font-weight: 600; }
        .status.ok { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        pre { background: #1f2937; color: #10b981; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.875rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        h1 { color: #1f2937; margin-bottom: 0.5rem; }
        h2 { color: #374151; font-size: 1.25rem; margin-bottom: 1rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
        .test-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .test-row:last-child { border-bottom: none; }
        button { background: #10b981; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500; }
        button:hover { background: #059669; }
        .error-msg { color: #dc2626; margin-top: 0.5rem; font-size: 0.875rem; }
        .info { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 BTT API Diagnostic Tool</h1>
        <p>Testing session handling and user-specific data isolation</p>
        
        <div class="grid">
            <!-- Session Info -->
            <div class="card">
                <h2>📝 Session Information</h2>
                <div class="test-row">
                    <span>Session Status:</span>
                    <span class="status <?php echo session_status() === PHP_SESSION_ACTIVE ? 'ok' : 'error'; ?>">
                        <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <div class="test-row">
                    <span>Session Name:</span>
                    <code><?php echo session_name(); ?></code>
                </div>
                <div class="test-row">
                    <span>Session ID:</span>
                    <code><?php echo substr(session_id(), 0, 16) . '...'; ?></code>
                </div>
                <div class="test-row">
                    <span>Cookie Path:</span>
                    <code><?php 
                        $params = session_get_cookie_params();
                        echo $params['path'];
                    ?></code>
                </div>
                <div class="test-row">
                    <span>Session Data:</span>
                    <span class="status <?php echo !empty($_SESSION) ? 'ok' : 'warning'; ?>">
                        <?php echo count($_SESSION) . ' items'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Authentication Info -->
            <div class="card">
                <h2>🔐 Authentication Status</h2>
                <div class="test-row">
                    <span>Authenticated:</span>
                    <span class="status <?php echo AuthService::isAuthenticated() ? 'ok' : 'warning'; ?>">
                        <?php echo AuthService::isAuthenticated() ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                <?php if (AuthService::isAuthenticated()): 
                    $user = AuthService::getCurrentUser();
                ?>
                <div class="test-row">
                    <span>User ID:</span>
                    <code><?php echo htmlspecialchars($user['id'] ?? 'N/A'); ?></code>
                </div>
                <div class="test-row">
                    <span>Username:</span>
                    <code><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></code>
                </div>
                <div class="test-row">
                    <span>Email:</span>
                    <code><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></code>
                </div>
                <?php else: ?>
                <div class="info">
                    Not authenticated. <a href="/BTT/public/auth/login.php">Login</a> to test user-specific data.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- API Test -->
        <div class="card">
            <h2>🌐 API Endpoints Test</h2>
            <div style="margin-bottom: 1rem;">
                <button onclick="testAPI('trips')">Test Trips API</button>
                <button onclick="testAPI('backpacks')">Test Backpacks API</button>
                <button onclick="testAPI('gear')">Test Gear API</button>
                <button onclick="testAPI('auth')">Test Auth Status</button>
                <button onclick="testCORS()">Test CORS</button>
            </div>
            <div id="api-results"></div>
        </div>
        
        <!-- Database Check -->
        <div class="card">
            <h2>💾 Database Status</h2>
            <?php
            try {
                $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check tables
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="test-row">
                    <span>Database Connection:</span>
                    <span class="status ok">Connected</span>
                </div>
                <div class="test-row">
                    <span>Database Path:</span>
                    <code><?php echo BTT_SQLITE_PATH; ?></code>
                </div>
                <div class="test-row">
                    <span>Tables Found:</span>
                    <span><?php echo count($tables); ?> tables</span>
                </div>
                
                <?php if (AuthService::isAuthenticated()): 
                    $user = AuthService::getCurrentUser();
                    $userId = $user['id'];
                    
                    // Count user's data
                    $tripCount = $db->query("SELECT COUNT(*) FROM trips WHERE user_id = $userId")->fetchColumn();
                    $backpackCount = $db->query("SELECT COUNT(*) FROM backpacks WHERE user_id = $userId")->fetchColumn();
                ?>
                <div class="test-row">
                    <span>Your Trips:</span>
                    <span class="status <?php echo $tripCount > 0 ? 'ok' : 'warning'; ?>"><?php echo $tripCount; ?> trips</span>
                </div>
                <div class="test-row">
                    <span>Your Backpacks:</span>
                    <span class="status <?php echo $backpackCount > 0 ? 'ok' : 'warning'; ?>"><?php echo $backpackCount; ?> backpacks</span>
                </div>
                <?php endif; ?>
                
            <?php } catch (Exception $e) { ?>
                <div class="test-row">
                    <span>Database Connection:</span>
                    <span class="status error">Failed</span>
                </div>
                <div class="error-msg">Error: <?php echo htmlspecialchars($e->getMessage()); ?></div>
            <?php } ?>
        </div>
        
        <!-- Session Details -->
        <div class="card">
            <h2>🔍 Session Variables</h2>
            <pre><?php echo htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)); ?></pre>
        </div>
    </div>
    
    <script>
        const API_BASE = '/BTT/api/index.php';
        
        async function testAPI(endpoint) {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<div class="info">Testing ' + endpoint + ' endpoint...</div>';
            
            try {
                const response = await fetch(API_BASE + '?route=' + endpoint, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                resultsDiv.innerHTML = `
                    <div class="test-row">
                        <span>${endpoint} API:</span>
                        <span class="status ${response.ok ? 'ok' : 'error'}">
                            ${response.status} ${response.statusText}
                        </span>
                    </div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test-row">
                        <span>${endpoint} API:</span>
                        <span class="status error">Failed</span>
                    </div>
                    <div class="error-msg">Error: ${error.message}</div>
                `;
            }
        }
        
        async function testCORS() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<div class="info">Testing CORS headers...</div>';
            
            try {
                const response = await fetch(API_BASE + '?route=trips', {
                    method: 'OPTIONS',
                    headers: {
                        'Origin': window.location.origin
                    }
                });
                
                const corsHeaders = {
                    'Access-Control-Allow-Origin': response.headers.get('Access-Control-Allow-Origin'),
                    'Access-Control-Allow-Methods': response.headers.get('Access-Control-Allow-Methods'),
                    'Access-Control-Allow-Credentials': response.headers.get('Access-Control-Allow-Credentials')
                };
                
                resultsDiv.innerHTML = `
                    <div class="test-row">
                        <span>CORS Preflight:</span>
                        <span class="status ${response.ok ? 'ok' : 'error'}">
                            ${response.status} ${response.statusText}
                        </span>
                    </div>
                    <pre>${JSON.stringify(corsHeaders, null, 2)}</pre>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="test-row">
                        <span>CORS Test:</span>
                        <span class="status error">Failed</span>
                    </div>
                    <div class="error-msg">Error: ${error.message}</div>
                `;
            }
        }
    </script>
</body>
</html>
