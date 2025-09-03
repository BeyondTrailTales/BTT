<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backpack Save Debug Tool</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
        }
        button {
            padding: 10px 20px;
            margin: 10px 10px 10px 0;
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }
        button:hover {
            background: #45a049;
        }
        .log {
            background: #1e1e1e;
            color: #0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-size: 12px;
            min-height: 100px;
        }
        .error {
            color: #f00;
        }
        .success {
            color: #0f0;
        }
        .warning {
            color: #ff0;
        }
        input, select {
            padding: 5px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>🔧 Backpack Save Debug Tool</h1>
    
    <div class="section">
        <h2>1. Login</h2>
        <input type="text" id="username" placeholder="Username" value="demo">
        <input type="password" id="password" placeholder="Password" value="password">
        <button onclick="login()">Login</button>
        <div id="login-log" class="log">Ready to login...</div>
    </div>
    
    <div class="section">
        <h2>2. Create Test Backpack</h2>
        <button onclick="createTestBackpack()">Create Backpack with Items</button>
        <div id="create-log" class="log">Ready to create backpack...</div>
    </div>
    
    <div class="section">
        <h2>3. Check Database</h2>
        <button onclick="checkDatabase()">Check Database Items</button>
        <div id="db-log" class="log">Ready to check database...</div>
    </div>
    
    <div class="section">
        <h2>4. Test Update</h2>
        <input type="number" id="backpack-id" placeholder="Backpack ID">
        <button onclick="updateBackpack()">Update Backpack</button>
        <div id="update-log" class="log">Ready to update...</div>
    </div>
    
    <div class="section">
        <h2>5. Load Backpack</h2>
        <input type="number" id="load-id" placeholder="Backpack ID">
        <button onclick="loadBackpack()">Load Backpack</button>
        <div id="load-log" class="log">Ready to load...</div>
    </div>

    <script>
    const API_URL = 'http://localhost/BTT/api';
    
    function log(elementId, message, type = '') {
        const logEl = document.getElementById(elementId);
        const timestamp = new Date().toLocaleTimeString();
        const typeClass = type ? ` class="${type}"` : '';
        logEl.innerHTML += `<span${typeClass}>[${timestamp}] ${message}</span>\n`;
        logEl.scrollTop = logEl.scrollHeight;
    }
    
    async function login() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        log('login-log', 'Attempting login...', 'warning');
        
        try {
            const response = await fetch(`${API_URL}?route=auth&id=login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include', // Important for session cookies
                body: JSON.stringify({ login: username, password: password })
            });
            
            const data = await response.json();
            log('login-log', 'Response: ' + JSON.stringify(data, null, 2));
            
            if (data.success) {
                log('login-log', '✅ Login successful!', 'success');
                // Session cookie is set automatically, no token needed
            } else {
                log('login-log', '❌ Login failed: ' + (data.message || data.error), 'error');
            }
        } catch (error) {
            log('login-log', '❌ Error: ' + error.message, 'error');
        }
    }
    
    async function createTestBackpack() {
        log('create-log', 'Creating test backpack with items...', 'warning');
        
        const packData = {
            name: 'Debug Test Pack ' + Date.now(),
            description: 'Test pack for debugging item saving',
            capacity_l: 65,
            weight_empty_g: 1500,
            type: 'custom',
            sections: [
                {
                    id: 'main',
                    name: 'Main Compartment',
                    items: [
                        {
                            gear_id: 1, // Sleeping Bag
                            name: 'Sleeping Bag',
                            weight_g: 1200,
                            quantity: 1,
                            category: 'sleep'
                        },
                        {
                            gear_id: 2, // Tent
                            name: 'Tent',
                            weight_g: 2000,
                            quantity: 1,
                            category: 'shelter'
                        }
                    ]
                },
                {
                    id: 'lid',
                    name: 'Top Lid',
                    items: [
                        {
                            gear_id: 10, // First Aid Kit
                            name: 'First Aid Kit',
                            weight_g: 300,
                            quantity: 1,
                            category: 'first-aid'
                        }
                    ]
                }
            ]
        };
        
        log('create-log', 'Sending data:\n' + JSON.stringify(packData, null, 2));
        
        try {
            const response = await fetch(`${API_URL}?route=backpacks`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(packData)
            });
            
            const data = await response.json();
            log('create-log', 'Response:\n' + JSON.stringify(data, null, 2));
            
            if (data.success) {
                log('create-log', `✅ Created backpack ID: ${data.data.id}`, 'success');
                document.getElementById('backpack-id').value = data.data.id;
                document.getElementById('load-id').value = data.data.id;
            } else {
                log('create-log', '❌ Failed: ' + data.error, 'error');
            }
        } catch (error) {
            log('create-log', '❌ Error: ' + error.message, 'error');
        }
    }
    
    async function checkDatabase() {
        log('db-log', 'Checking database for backpack items...', 'warning');
        
        try {
            const response = await fetch('/BTT/test/check-db-items.php');
            const text = await response.text();
            log('db-log', 'Database contents:\n' + text);
        } catch (error) {
            log('db-log', '❌ Error: ' + error.message, 'error');
        }
    }
    
    async function updateBackpack() {
        const backpackId = document.getElementById('backpack-id').value;
        if (!backpackId) {
            log('update-log', '❌ Please enter a backpack ID', 'error');
            return;
        }
        
        log('update-log', `Updating backpack ${backpackId}...`, 'warning');
        
        // Session authentication is used, no need for token
        
        const updateData = {
            name: 'Updated Debug Pack ' + Date.now(),
            sections: [
                {
                    id: 'main',
                    name: 'Main Updated',
                    items: [
                        {
                            gear_id: 3,
                            name: 'Sleeping Pad',
                            weight_g: 500,
                            quantity: 1,
                            category: 'sleep'
                        },
                        {
                            gear_id: null,
                            custom_name: 'Custom Item',
                            custom_weight_g: 250,
                            quantity: 2,
                            category: 'other'
                        }
                    ]
                }
            ]
        };
        
        log('update-log', 'Sending update:\n' + JSON.stringify(updateData, null, 2));
        
        try {
            const response = await fetch(`${API_URL}?route=backpacks&id=${backpackId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(updateData)
            });
            
            const data = await response.json();
            log('update-log', 'Response:\n' + JSON.stringify(data, null, 2));
            
            if (data.success) {
                log('update-log', '✅ Update successful!', 'success');
            } else {
                log('update-log', '❌ Update failed: ' + data.error, 'error');
            }
        } catch (error) {
            log('update-log', '❌ Error: ' + error.message, 'error');
        }
    }
    
    async function loadBackpack() {
        const backpackId = document.getElementById('load-id').value;
        if (!backpackId) {
            log('load-log', '❌ Please enter a backpack ID', 'error');
            return;
        }
        
        log('load-log', `Loading backpack ${backpackId}...`, 'warning');
        
        // Session authentication is used, no need for token
        
        try {
            const response = await fetch(`${API_URL}?route=backpacks&id=${backpackId}`, {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            log('load-log', 'Response:\n' + JSON.stringify(data, null, 2));
            
            if (data.success && data.data) {
                const pack = data.data;
                log('load-log', `✅ Loaded: ${pack.name}`, 'success');
                
                if (pack.sections && pack.sections.length > 0) {
                    log('load-log', `Sections: ${pack.sections.length}`, 'success');
                    pack.sections.forEach(section => {
                        const itemCount = section.items ? section.items.length : 0;
                        log('load-log', `  - ${section.name}: ${itemCount} items`);
                    });
                } else {
                    log('load-log', '⚠️ No sections found', 'warning');
                }
            } else {
                log('load-log', '❌ Load failed: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            log('load-log', '❌ Error: ' + error.message, 'error');
        }
    }
    </script>
</body>
</html>
