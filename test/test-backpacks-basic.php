<?php
// Basic Backpacks Test - No Complex Scripts
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

$pageTitle = 'Basic Backpacks Test';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/BTT/assets/css/main.css">
    <style>
        body { padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Basic Backpacks Test</h1>
        
        <div class="card">
            <h2>Current User</h2>
            <pre><?php 
                $user = \App\Services\AuthService::getCurrentUser();
                echo "User: " . ($user ? json_encode($user, JSON_PRETTY_PRINT) : 'Not logged in');
            ?></pre>
        </div>
        
        <div class="card">
            <h2>Load Existing Backpacks</h2>
            <button onclick="loadBackpacks()">Load Backpacks</button>
            <div id="backpacks-list"></div>
        </div>
        
        <div class="card">
            <h2>Create Test Backpack</h2>
            <form id="test-form" onsubmit="createBackpack(event)">
                <input type="text" id="pack-name" placeholder="Pack Name" value="Test Pack" required>
                <button type="submit">Create Pack</button>
            </form>
            <div id="create-result"></div>
        </div>
        
        <div class="card">
            <h2>Console Output</h2>
            <pre id="console"></pre>
        </div>
    </div>

    <script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
    <script>
    function log(msg) {
        const console = document.getElementById('console');
        const time = new Date().toLocaleTimeString();
        console.textContent += `[${time}] ${msg}\n`;
        console.scrollTop = console.scrollHeight;
    }

    async function loadBackpacks() {
        try {
            log('Loading backpacks...');
            
            const response = await fetch('/BTT/api/index.php?route=backpacks', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            log(`Response status: ${response.status}`);
            const text = await response.text();
            log(`Response length: ${text.length} bytes`);
            
            if (text) {
                try {
                    const data = JSON.parse(text);
                    log(`Parsed response: ${JSON.stringify(data, null, 2)}`);
                    
                    const list = document.getElementById('backpacks-list');
                    if (data.success && data.data) {
                        list.innerHTML = '<h3>Backpacks:</h3><pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                    } else {
                        list.innerHTML = '<p class="error">Failed to load backpacks</p>';
                    }
                } catch (e) {
                    log('Failed to parse JSON: ' + e.message);
                    log('Raw response: ' + text.substring(0, 500));
                }
            }
            
        } catch (error) {
            log('ERROR: ' + error.message);
        }
    }

    async function createBackpack(event) {
        event.preventDefault();
        
        try {
            log('Creating backpack...');
            
            const data = {
                name: document.getElementById('pack-name').value,
                description: 'Test pack created at ' + new Date().toISOString(),
                capacity_l: 65,
                weight_empty_g: 2000,
                type: 'custom',
                sections: []
            };
            
            log('Sending: ' + JSON.stringify(data));
            
            const response = await fetch('/BTT/api/index.php?route=backpacks', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            log(`Response status: ${response.status}`);
            const text = await response.text();
            log(`Response: ${text}`);
            
            const result = document.getElementById('create-result');
            if (response.ok) {
                result.innerHTML = '<p class="success">Pack created successfully!</p>';
                loadBackpacks(); // Reload list
            } else {
                result.innerHTML = '<p class="error">Failed to create pack</p>';
            }
            
        } catch (error) {
            log('ERROR: ' + error.message);
        }
    }

    // Auto-load on page load
    window.addEventListener('load', () => {
        log('Page loaded');
        loadBackpacks();
    });
    </script>
</body>
</html>
