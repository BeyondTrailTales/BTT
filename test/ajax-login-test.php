<?php
/**
 * AJAX Login Test Page
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJAX Login Test - BeyondTrailTales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .test-section {
            background: #2a2a2a;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #4ade80;
        }
        h1 {
            color: #4ade80;
        }
        h2 {
            color: #4ade80;
            font-size: 1.2rem;
            margin-top: 0;
        }
        button {
            background: #4ade80;
            color: #1a1a1a;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        button:hover {
            background: #22c55e;
        }
        .console {
            background: #0a0a0a;
            padding: 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.875rem;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .log-entry {
            margin-bottom: 0.5rem;
            padding: 0.25rem;
            border-left: 3px solid #4ade80;
            padding-left: 0.5rem;
        }
        .log-error {
            border-left-color: #ef4444;
            color: #ef4444;
        }
        .log-success {
            border-left-color: #4ade80;
            color: #4ade80;
        }
        .status {
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .status.success {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid #4ade80;
            color: #4ade80;
        }
        .status.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>🧪 AJAX Login Test Page</h1>
    
    <div class="test-section">
        <h2>Test Accounts</h2>
        <button onclick="testLogin('admin', 'Admin123!')">Test Admin Login</button>
        <button onclick="testLogin('testuser', 'Test123!')">Test User Login</button>
        <button onclick="testLogin('demo', 'Demo123!')">Test Demo Login</button>
        <button onclick="testLogin('baduser', 'wrongpass')">Test Bad Login</button>
    </div>
    
    <div class="test-section">
        <h2>Current Session</h2>
        <button onclick="checkSession()">Check Session</button>
        <button onclick="testLogout()">Test Logout</button>
        <div id="session-status" class="status" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>Console Output</h2>
        <button onclick="clearConsole()">Clear Console</button>
        <div id="console" class="console"></div>
    </div>

    <script>
        const API_URL = '<?php echo BTT_API_URL; ?>';
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const consoleDiv = document.getElementById('console');
        const sessionStatus = document.getElementById('session-status');
        
        function log(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            if (type === 'error') entry.classList.add('log-error');
            if (type === 'success') entry.classList.add('log-success');
            
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${timestamp}] ${message}`;
            consoleDiv.appendChild(entry);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
            
            // Also log to browser console
            console.log(`[${type}]`, message);
        }
        
        function clearConsole() {
            consoleDiv.innerHTML = '';
            log('Console cleared');
        }
        
        async function testLogin(username, password) {
            log(`Testing login for user: ${username}`);
            
            try {
                // Get CSRF token first (if needed)
                const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
                log(`CSRF Token: ${csrfToken ? 'Present' : 'Not found'}`);
                
                const requestBody = {
                    login: username,
                    password: password,
                    remember: false,
                    csrf_token: csrfToken
                };
                
                log(`Sending request to: ${API_URL}/?route=auth&id=login`);
                log(`Request body: ${JSON.stringify(requestBody, null, 2)}`);
                
                const response = await fetch(`${API_URL}/?route=auth&id=login`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify(requestBody)
                });
                
                log(`Response status: ${response.status}`);
                
                const result = await response.json();
                log(`Response data: ${JSON.stringify(result, null, 2)}`);
                
                if (result.success) {
                    log('✅ Login successful!', 'success');
                    if (result.data && result.data.user) {
                        log(`User ID: ${result.data.user.id}`, 'success');
                        log(`Username: ${result.data.user.username}`, 'success');
                    }
                    
                    // Test immediate session check
                    setTimeout(() => {
                        log('Checking session after login...');
                        checkSession();
                    }, 500);
                } else {
                    log(`❌ Login failed: ${result.message}`, 'error');
                    if (result.errors) {
                        log(`Errors: ${JSON.stringify(result.errors)}`, 'error');
                    }
                }
            } catch (error) {
                log(`❌ Network error: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        async function checkSession() {
            log('Checking current session...');
            
            try {
                // Make a simple PHP request to check session
                const response = await fetch('check-session.php', {
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.authenticated) {
                    log(`✅ Session active for user: ${result.username}`, 'success');
                    sessionStatus.style.display = 'block';
                    sessionStatus.className = 'status success';
                    sessionStatus.textContent = `Logged in as: ${result.username} (ID: ${result.user_id})`;
                } else {
                    log('❌ No active session', 'error');
                    sessionStatus.style.display = 'block';
                    sessionStatus.className = 'status error';
                    sessionStatus.textContent = 'Not logged in';
                }
            } catch (error) {
                log(`❌ Session check error: ${error.message}`, 'error');
            }
        }
        
        async function testLogout() {
            log('Testing logout...');
            
            try {
                const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
                
                const response = await fetch(`${API_URL}/?route=auth&id=logout`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log('✅ Logout successful!', 'success');
                    checkSession();
                } else {
                    log(`❌ Logout failed: ${result.message}`, 'error');
                }
            } catch (error) {
                log(`❌ Logout error: ${error.message}`, 'error');
            }
        }
        
        // Initial console message
        log('AJAX Login Test Page ready');
        log('Click a test button to begin');
    </script>
</body>
</html>
