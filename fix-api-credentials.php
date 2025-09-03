<?php
/**
 * Fix API Credentials Issue
 * This script updates the BTTApi JavaScript to include credentials in fetch requests
 * so that session cookies are sent with API calls
 */

require_once 'app/bootstrap.php';
use App\Services\AuthService;

if (!AuthService::isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Check if fix button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_api') {
        // Read the current app.js file
        $appJsPath = __DIR__ . '/assets/js/app.js';
        $content = file_get_contents($appJsPath);
        
        // Check if credentials are already included
        if (strpos($content, 'credentials:') === false) {
            // Update fetch calls to include credentials
            $patterns = [
                // For GET requests
                '/const response = await fetch\(url\);/' => 
                    "const response = await fetch(url, {\n                    credentials: 'same-origin'\n                });",
                
                // For POST/PUT/DELETE requests
                '/const response = await fetch\(url, \{\s*method: \'POST\',\s*body: formData\s*\}\);/' =>
                    "const response = await fetch(url, {\n                    method: 'POST',\n                    body: formData,\n                    credentials: 'same-origin'\n                });"
            ];
            
            foreach ($patterns as $pattern => $replacement) {
                $content = preg_replace($pattern, $replacement, $content);
            }
            
            // Backup original file
            copy($appJsPath, $appJsPath . '.backup-' . date('Y-m-d-His'));
            
            // Write updated content
            if (file_put_contents($appJsPath, $content)) {
                $message = 'Successfully updated app.js to include credentials in API requests!';
            } else {
                $error = 'Failed to write updated app.js file';
            }
        } else {
            $message = 'app.js already includes credentials in fetch requests';
        }
    }
}

// Test current API authentication
$apiTestResult = null;
if (isset($_POST['action']) && $_POST['action'] === 'test_api') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, BTT_BASE_URL . '/api/index.php?route=trips');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, tempnam(sys_get_temp_dir(), 'cookie'));
    curl_setopt($ch, CURLOPT_COOKIEFILE, tempnam(sys_get_temp_dir(), 'cookie'));
    
    // Pass the session cookie
    $sessionName = session_name();
    $sessionId = session_id();
    curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $apiTestResult = [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix API Credentials - BTT</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .fix-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .status-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .code-preview {
            background: #000;
            border: 1px solid #333;
            border-radius: 0.25rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="fix-container">
        <h1>Fix API Credentials Issue</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="status-card">
            <h2>Current Session Status</h2>
            <p>Logged in as: <strong><?= htmlspecialchars(AuthService::getCurrentUser()['email'] ?? 'Unknown') ?></strong></p>
            <p>Session ID: <code><?= htmlspecialchars(session_id()) ?></code></p>
        </div>
        
        <div class="status-card">
            <h2>Problem Explanation</h2>
            <p>The API requests from JavaScript are not sending session cookies, which causes the API to not recognize authenticated users.</p>
            <p>This is because the fetch() API by default doesn't include cookies in cross-origin requests.</p>
            <p>Even though our API is on the same domain, we need to explicitly tell fetch() to include credentials.</p>
        </div>
        
        <div class="status-card">
            <h2>Solution</h2>
            <p>Add <code>credentials: 'same-origin'</code> to all fetch() calls in app.js</p>
            <div class="code-preview">
// Before:
const response = await fetch(url);

// After:
const response = await fetch(url, {
    credentials: 'same-origin'
});
            </div>
        </div>
        
        <form method="post" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
            <button type="submit" name="action" value="fix_api" class="btn-action">
                Apply Fix to app.js
            </button>
            <button type="submit" name="action" value="test_api" class="btn-secondary">
                Test API Authentication
            </button>
        </form>
        
        <?php if ($apiTestResult): ?>
        <div class="status-card">
            <h2>API Test Result</h2>
            <p>HTTP Status: <span class="<?= $apiTestResult['status'] === 200 ? 'success' : 'error' ?>">
                <?= $apiTestResult['status'] ?>
            </span></p>
            <div class="code-preview">
                <?= htmlspecialchars(json_encode($apiTestResult['response'], JSON_PRETTY_PRINT)) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="status-card">
            <h2>Manual Fix Instructions</h2>
            <ol>
                <li>Open <code>assets/js/app.js</code></li>
                <li>Find all <code>fetch()</code> calls (lines 16, 51, 90, 116)</li>
                <li>Add <code>credentials: 'same-origin'</code> to the options object</li>
                <li>Clear browser cache and reload the page</li>
            </ol>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="trips.php" class="btn-secondary">Go to Trips</a>
            <a href="backpacks.php" class="btn-secondary">Go to Backpacks</a>
            <a href="dashboard.php" class="btn-secondary">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>
