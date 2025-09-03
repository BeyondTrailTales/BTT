<?php
require_once 'app/bootstrap.php';
use App\Services\AuthService;

if (!AuthService::isAuthenticated()) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug JavaScript - BTT</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .debug-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .console-output {
            background: #000;
            color: #0f0;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
            min-height: 200px;
            border: 1px solid #333;
            border-radius: 0.5rem;
            margin: 1rem 0;
            white-space: pre-wrap;
        }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        .success { color: #0f0; }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>JavaScript Debug Console</h1>
        
        <h2>Testing API Directly</h2>
        <button onclick="testTripsAPI()" class="btn-action">Test Trips API</button>
        <button onclick="testBackpacksAPI()" class="btn-action">Test Backpacks API</button>
        <button onclick="checkGlobals()" class="btn-action">Check Global Variables</button>
        
        <div id="console" class="console-output"></div>
        
        <h2>Manual API Test with cURL</h2>
        <div class="console-output">
<?php
// Test API directly with PHP
$sessionName = session_name();
$sessionId = session_id();

// Test trips API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BTT_BASE_URL . '/api/index.php?route=trips');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");
$tripsResponse = curl_exec($ch);
$tripsStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "PHP API Test Results:\n";
echo "===================\n\n";
echo "Trips API Status: $tripsStatus\n";
echo "Trips Response: " . substr($tripsResponse, 0, 200) . "...\n\n";

// Test backpacks API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BTT_BASE_URL . '/api/index.php?route=backpacks');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "$sessionName=$sessionId");
$backpacksResponse = curl_exec($ch);
$backpacksStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Backpacks API Status: $backpacksStatus\n";
echo "Backpacks Response: " . substr($backpacksResponse, 0, 200) . "...\n";
?>
        </div>
    </div>
    
    <script>
        // Store BTT configuration
        window.BTT = {
            baseUrl: '<?= BTT_BASE_URL ?>',
            publicUrl: '<?= BTT_PUBLIC_URL ?>',
            apiUrl: '<?= BTT_API_URL ?>'
        };
        
        const log = (msg, type = 'info') => {
            const console = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = type;
            entry.textContent = `[${timestamp}] ${msg}`;
            console.appendChild(entry);
            console.scrollTop = console.scrollHeight;
        };
        
        // Override console methods to capture output
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        
        console.log = (...args) => {
            originalLog(...args);
            log('LOG: ' + args.join(' '), 'info');
        };
        
        console.error = (...args) => {
            originalError(...args);
            log('ERROR: ' + args.join(' '), 'error');
        };
        
        console.warn = (...args) => {
            originalWarn(...args);
            log('WARN: ' + args.join(' '), 'warning');
        };
        
        // Capture unhandled errors
        window.addEventListener('error', (e) => {
            log(`UNCAUGHT ERROR: ${e.message} at ${e.filename}:${e.lineno}:${e.colno}`, 'error');
        });
        
        window.addEventListener('unhandledrejection', (e) => {
            log(`UNHANDLED PROMISE REJECTION: ${e.reason}`, 'error');
        });
    </script>
    
    <!-- Load the main JavaScript files -->
    <script src="assets/js/app.js"></script>
    
    <script>
        function checkGlobals() {
            log('Checking global variables...', 'info');
            log('window.BTT exists: ' + (typeof window.BTT !== 'undefined'), window.BTT ? 'success' : 'error');
            log('window.BTTApi exists: ' + (typeof window.BTTApi !== 'undefined'), window.BTTApi ? 'success' : 'error');
            log('window.BTTUtils exists: ' + (typeof window.BTTUtils !== 'undefined'), window.BTTUtils ? 'success' : 'error');
            
            if (window.BTT) {
                log('BTT.apiUrl: ' + window.BTT.apiUrl, 'info');
                log('BTT.baseUrl: ' + window.BTT.baseUrl, 'info');
            }
        }
        
        async function testTripsAPI() {
            log('Testing Trips API...', 'info');
            
            try {
                if (typeof window.BTTApi === 'undefined') {
                    throw new Error('BTTApi is not defined!');
                }
                
                log('Calling BTTApi.get("trips")...', 'info');
                const data = await window.BTTApi.get('trips');
                log('Success! Received ' + (Array.isArray(data) ? data.length : '1') + ' trips', 'success');
                log('Data: ' + JSON.stringify(data).substring(0, 200) + '...', 'info');
            } catch (error) {
                log('Failed to fetch trips: ' + error.message, 'error');
                log('Error stack: ' + error.stack, 'error');
            }
        }
        
        async function testBackpacksAPI() {
            log('Testing Backpacks API...', 'info');
            
            try {
                if (typeof window.BTTApi === 'undefined') {
                    throw new Error('BTTApi is not defined!');
                }
                
                log('Calling BTTApi.get("backpacks")...', 'info');
                const data = await window.BTTApi.get('backpacks');
                log('Success! Received ' + (Array.isArray(data) ? data.length : '1') + ' backpacks', 'success');
                log('Data: ' + JSON.stringify(data).substring(0, 200) + '...', 'info');
            } catch (error) {
                log('Failed to fetch backpacks: ' + error.message, 'error');
                log('Error stack: ' + error.stack, 'error');
            }
        }
        
        // Auto-check on load
        window.addEventListener('DOMContentLoaded', () => {
            log('Page loaded, checking environment...', 'info');
            checkGlobals();
        });
    </script>
</body>
</html>
