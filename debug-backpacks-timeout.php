<?php
// Minimal bootstrap - just session and basic config
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /BTT/public/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Backpacks Timeout</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .test { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Debugging Backpacks Page Timeout</h1>
    
    <div class="test">
        <h3>1. Testing Database Connection</h3>
        <?php
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
            echo '<p class="success">✓ Database connection successful</p>';
            
            // Test query
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<p class="info">Found ' . $result['count'] . ' backpacks for user ' . $user_id . '</p>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Database error: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="test">
        <h3>2. Testing AJAX Handler</h3>
        <button onclick="testAjax()">Test AJAX Call</button>
        <div id="ajaxResult"></div>
    </div>
    
    <div class="test">
        <h3>3. JavaScript Loading Test</h3>
        <button onclick="loadScripts()">Load Pack Builder Scripts</button>
        <div id="scriptResult"></div>
    </div>
    
    <div class="test">
        <h3>4. Memory Usage</h3>
        <?php
        echo '<p class="info">Current memory usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB</p>';
        echo '<p class="info">Peak memory usage: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB</p>';
        ?>
    </div>
    
    <div class="test">
        <h3>5. Check File Existence</h3>
        <?php
        $files = [
            'app/bootstrap.php',
            'api/classes/Database.php',
            'assets/js/pack-builder.js',
            'assets/js/pack-builder-crud.js',
            'assets/css/backpacks-clean.css'
        ];
        
        foreach ($files as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo '<p class="success">✓ ' . $file . ' exists</p>';
            } else {
                echo '<p class="error">✗ ' . $file . ' NOT FOUND</p>';
            }
        }
        ?>
    </div>
    
    <div class="test">
        <h3>6. Session Data</h3>
        <?php
        echo '<pre>';
        echo 'Session ID: ' . session_id() . "\n";
        echo 'User ID: ' . ($_SESSION['user_id'] ?? 'not set') . "\n";
        echo 'Username: ' . ($_SESSION['username'] ?? 'not set') . "\n";
        echo '</pre>';
        ?>
    </div>

    <script>
        async function testAjax() {
            const result = document.getElementById('ajaxResult');
            result.innerHTML = '<p class="info">Testing AJAX call...</p>';
            
            const startTime = performance.now();
            
            try {
                const response = await fetch('/BTT/ajax-handler.php?route=backpacks', {
                    credentials: 'include',
                    signal: AbortSignal.timeout(5000) // 5 second timeout
                });
                
                const endTime = performance.now();
                const duration = Math.round(endTime - startTime);
                
                if (response.ok) {
                    const data = await response.json();
                    result.innerHTML = `<p class="success">✓ AJAX call successful (${duration}ms)</p>`;
                    result.innerHTML += `<p class="info">Response: ${JSON.stringify(data).substring(0, 100)}...</p>`;
                } else {
                    result.innerHTML = `<p class="error">✗ AJAX call failed: ${response.status}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p class="error">✗ AJAX call error: ${error.message}</p>`;
                if (error.name === 'AbortError') {
                    result.innerHTML += '<p class="error">Request timed out after 5 seconds!</p>';
                }
            }
        }
        
        function loadScripts() {
            const result = document.getElementById('scriptResult');
            result.innerHTML = '<p class="info">Loading scripts...</p>';
            
            const scripts = [
                '/BTT/assets/js/pack-builder.js',
                '/BTT/assets/js/pack-builder-crud.js'
            ];
            
            let loaded = 0;
            scripts.forEach(src => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = () => {
                    loaded++;
                    result.innerHTML += `<p class="success">✓ Loaded: ${src}</p>`;
                    if (loaded === scripts.length) {
                        result.innerHTML += '<p class="info">All scripts loaded successfully!</p>';
                    }
                };
                script.onerror = () => {
                    result.innerHTML += `<p class="error">✗ Failed to load: ${src}</p>`;
                };
                document.head.appendChild(script);
            });
        }
    </script>
</body>
</html>