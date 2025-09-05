<?php
// Test CRUD functionality for packs
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/api/classes/Database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

$db = Database::getInstance();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pack CRUD Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #1a1f2e;
            color: #e8f5e9;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #2a3f2e;
            border-radius: 8px;
            border: 1px solid #58cc02;
        }
        h2 { color: #58cc02; }
        .success { color: #58cc02; font-weight: bold; }
        .error { color: #ff4444; font-weight: bold; }
        button {
            background: #58cc02;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #46a000; }
        pre {
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Pack CRUD Functionality Test</h1>
    
    <div class="test-section">
        <h2>Test Pack CRUD Operations</h2>
        
        <button onclick="testCreate()">1. Test Create Pack</button>
        <button onclick="testRead()">2. Test Read Packs</button>
        <button onclick="testUpdate()">3. Test Update Pack</button>
        <button onclick="testDelete()">4. Test Delete Pack</button>
        
        <div id="results"></div>
    </div>
    
    <script src="/BTT/assets/js/jquery.min.js"></script>
    <script>
        const apiUrl = '/BTT/ajax-handler.php?route=backpacks';
        
        async function testCreate() {
            const results = $('#results');
            results.html('<p>Testing Create...</p>');
            
            const testPack = {
                name: 'CRUD Test Pack ' + Date.now(),
                description: 'Testing CRUD operations',
                capacity_l: 65,
                weight_empty_g: 1000,
                type: 'custom',
                sections: [{
                    id: 'main',
                    name: 'Main Pack',
                    items: [{
                        gear_id: 1,
                        name: 'Test Item',
                        weight_g: 500,
                        quantity: 1,
                        category: 'other'
                    }]
                }]
            };
            
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(testPack)
                });
                
                const data = await response.json();
                results.html(`<pre>${JSON.stringify(data, null, 2)}</pre>`);
                
                if (data.id) {
                    localStorage.setItem('test-pack-id', data.id);
                    results.append('<p class="success">✅ Create successful! Pack ID: ' + data.id + '</p>');
                }
            } catch (error) {
                results.html(`<p class="error">❌ Error: ${error.message}</p>`);
            }
        }
        
        async function testRead() {
            const results = $('#results');
            results.html('<p>Testing Read...</p>');
            
            try {
                const response = await fetch(apiUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                results.html(`<pre>${JSON.stringify(data.slice(0, 3), null, 2)}</pre>`);
                results.append(`<p class="success">✅ Found ${data.length} packs</p>`);
            } catch (error) {
                results.html(`<p class="error">❌ Error: ${error.message}</p>`);
            }
        }
        
        async function testUpdate() {
            const results = $('#results');
            const packId = localStorage.getItem('test-pack-id');
            
            if (!packId) {
                results.html('<p class="error">❌ No test pack ID found. Run Create test first.</p>');
                return;
            }
            
            results.html('<p>Testing Update...</p>');
            
            const updateData = {
                name: 'Updated CRUD Test Pack',
                description: 'This pack has been updated',
                capacity_l: 75,
                weight_empty_g: 1200
            };
            
            try {
                const response = await fetch(`${apiUrl}&id=${packId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(updateData)
                });
                
                const data = await response.json();
                results.html(`<pre>${JSON.stringify(data, null, 2)}</pre>`);
                
                if (data.success) {
                    results.append('<p class="success">✅ Update successful!</p>');
                }
            } catch (error) {
                results.html(`<p class="error">❌ Error: ${error.message}</p>`);
            }
        }
        
        async function testDelete() {
            const results = $('#results');
            const packId = localStorage.getItem('test-pack-id');
            
            if (!packId) {
                results.html('<p class="error">❌ No test pack ID found. Run Create test first.</p>');
                return;
            }
            
            if (!confirm('Delete the test pack?')) return;
            
            results.html('<p>Testing Delete...</p>');
            
            try {
                const response = await fetch(`${apiUrl}&id=${packId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                results.html(`<pre>${JSON.stringify(data, null, 2)}</pre>`);
                
                if (data.success) {
                    results.append('<p class="success">✅ Delete successful!</p>');
                    localStorage.removeItem('test-pack-id');
                }
            } catch (error) {
                results.html(`<p class="error">❌ Error: ${error.message}</p>`);
            }
        }
    </script>
    
    <div class="test-section">
        <h2>Database Check</h2>
        <?php
        try {
            $userPacks = $db->fetchAll("SELECT id, name, created_at FROM backpacks WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$_SESSION['user_id']]);
            echo "<h3>Your Recent Packs:</h3>";
            echo "<pre>" . json_encode($userPacks, JSON_PRETTY_PRINT) . "</pre>";
        } catch (Exception $e) {
            echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html>