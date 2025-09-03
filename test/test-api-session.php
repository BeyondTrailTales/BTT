<?php
/**
 * Web-based test for session and API authentication
 * Access this via browser: http://localhost/BTT/test/test-api-session.php
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Force login if not authenticated
if (!App\Services\AuthService::isAuthenticated()) {
    header('Location: /BTT/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user = App\Services\AuthService::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Session Test - BeyondTrailTales</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 0 1rem;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #10b981; }
        .test-section {
            margin: 2rem 0;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 4px;
            border-left: 4px solid #10b981;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }
        button {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin: 0.5rem;
        }
        button:hover {
            background: #059669;
        }
        .result {
            margin-top: 1rem;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 API Session Test</h1>
        
        <div class="test-section">
            <h2>1. Current Session Info</h2>
            <p class="success">✅ Authenticated as: <?php echo htmlspecialchars($user['username']); ?> (ID: <?php echo $user['id']; ?>)</p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Session ID: <?php echo session_id(); ?></p>
            <p>Session Name: <?php echo session_name(); ?></p>
        </div>

        <div class="test-section">
            <h2>2. Session Variables</h2>
            <pre><?php 
                $sessionData = $_SESSION;
                // Hide sensitive data
                if (isset($sessionData['csrf_token'])) {
                    $sessionData['csrf_token'] = substr($sessionData['csrf_token'], 0, 10) . '...';
                }
                echo htmlspecialchars(json_encode($sessionData, JSON_PRETTY_PRINT));
            ?></pre>
        </div>

        <div class="test-section">
            <h2>3. Cookies</h2>
            <pre><?php 
                $cookies = [];
                foreach ($_COOKIE as $name => $value) {
                    if (strpos($name, 'BTT') !== false || strpos($name, 'PHP') !== false) {
                        $cookies[$name] = substr($value, 0, 20) . '...';
                    }
                }
                echo htmlspecialchars(json_encode($cookies, JSON_PRETTY_PRINT));
            ?></pre>
        </div>

        <div class="test-section">
            <h2>4. Database Check</h2>
            <?php
            try {
                $dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
                $db = new PDO('sqlite:' . $dbPath);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Count backpacks for current user
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $user['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>Backpacks in database for your user: <strong>{$result['count']}</strong></p>";
                
                // List backpacks
                $stmt = $db->prepare("SELECT id, name, created_at FROM backpacks WHERE user_id = :user_id ORDER BY created_at DESC");
                $stmt->execute(['user_id' => $user['id']]);
                $backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($backpacks) {
                    echo "<ul>";
                    foreach ($backpacks as $bp) {
                        echo "<li>{$bp['name']} (ID: {$bp['id']}, Created: {$bp['created_at']})</li>";
                    }
                    echo "</ul>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>

        <div class="test-section">
            <h2>5. API Tests</h2>
            <p>Click the buttons below to test API endpoints with your current session:</p>
            
            <button onclick="testAPI('backpacks', 'GET')">Test GET /backpacks</button>
            <button onclick="testAPI('auth/current-user', 'GET')">Test GET /auth/current-user</button>
            <button onclick="createTestBackpack()">Test CREATE Backpack</button>
            
            <div id="api-result" class="result" style="display: none;">
                <h3>API Response:</h3>
                <pre id="api-response"></pre>
            </div>
        </div>

        <div class="test-section">
            <h2>6. Create Test Data</h2>
            <button onclick="createMultipleBackpacks()">Create 3 Test Backpacks</button>
            <div id="create-result" class="result" style="display: none;"></div>
        </div>
    </div>

    <!-- Load jQuery first -->
    <script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
    <!-- Load our API client -->
    <script src="/BTT/assets/js/api.js"></script>
    
    <script>
    // Test API endpoint
    function testAPI(route, method) {
        console.log(`Testing ${method} /${route}`);
        
        const resultDiv = document.getElementById('api-result');
        const responseDiv = document.getElementById('api-response');
        
        resultDiv.style.display = 'block';
        responseDiv.textContent = 'Loading...';
        
        // Use our BttApi client
        if (route === 'backpacks' && method === 'GET') {
            BttApi.backpacks.list()
                .done(function(response) {
                    console.log('Success:', response);
                    responseDiv.textContent = JSON.stringify(response, null, 2);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', jqXHR.responseText);
                    responseDiv.textContent = 'Error: ' + jqXHR.responseText;
                });
        } else if (route === 'auth/current-user' && method === 'GET') {
            BttApi.auth.getCurrentUser()
                .done(function(response) {
                    console.log('Success:', response);
                    responseDiv.textContent = JSON.stringify(response, null, 2);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', jqXHR.responseText);
                    responseDiv.textContent = 'Error: ' + jqXHR.responseText;
                });
        }
    }
    
    // Create a test backpack
    function createTestBackpack() {
        const resultDiv = document.getElementById('api-result');
        const responseDiv = document.getElementById('api-response');
        
        resultDiv.style.display = 'block';
        responseDiv.textContent = 'Creating backpack...';
        
        const testData = {
            name: 'Test Backpack ' + new Date().toLocaleTimeString(),
            description: 'Created via API test',
            capacity_l: 45,
            weight_empty_g: 1200
        };
        
        BttApi.backpacks.create(testData)
            .done(function(response) {
                console.log('Created:', response);
                responseDiv.textContent = JSON.stringify(response, null, 2);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error:', jqXHR.responseText);
                responseDiv.textContent = 'Error: ' + jqXHR.responseText;
            });
    }
    
    // Create multiple test backpacks
    function createMultipleBackpacks() {
        const resultDiv = document.getElementById('create-result');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<p>Creating test backpacks...</p>';
        
        const backpacks = [
            {
                name: 'Ultralight Day Pack',
                description: 'Perfect for day hikes',
                capacity_l: 20,
                weight_empty_g: 450
            },
            {
                name: 'Weekend Warrior',
                description: '2-3 day trips',
                capacity_l: 45,
                weight_empty_g: 1200
            },
            {
                name: 'Thru-Hiker Pro',
                description: 'Long distance trails',
                capacity_l: 65,
                weight_empty_g: 900
            }
        ];
        
        let created = 0;
        let errors = 0;
        
        backpacks.forEach((pack, index) => {
            BttApi.backpacks.create(pack)
                .done(function(response) {
                    created++;
                    updateCreateResult();
                })
                .fail(function(jqXHR) {
                    errors++;
                    updateCreateResult();
                });
        });
        
        function updateCreateResult() {
            if (created + errors === backpacks.length) {
                resultDiv.innerHTML = `
                    <p class="${created > 0 ? 'success' : 'error'}">
                        Created ${created} backpack(s), ${errors} error(s)
                    </p>
                    <p>Refresh the backpacks page to see your new packs!</p>
                `;
            }
        }
    }
    
    // Test on page load
    window.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded. BttApi available:', typeof BttApi !== 'undefined');
        if (typeof BttApi !== 'undefined') {
            console.log('BttApi methods:', Object.keys(BttApi));
        }
    });
    </script>
</body>
</html>
