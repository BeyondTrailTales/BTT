<?php
// Include the bootstrap to get proper session handling
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Check if user is logged in
use App\Services\AuthService;

if (!AuthService::isAuthenticated()) {
    // Set a test user in session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['email'] = 'test@example.com';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
}

// Also set the old user array format for backward compatibility
$_SESSION['user'] = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Pack Save</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        #results {
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
            background: #f5f5f5;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Test Pack Save Functionality</h1>
    
    <div>
        <button onclick="testCreatePack()">Test Create Pack</button>
        <button onclick="testLoadPacks()">Test Load Packs</button>
        <button onclick="testUpdatePack()">Test Update Pack</button>
        <button onclick="clearResults()">Clear Results</button>
    </div>
    
    <div id="results"></div>
    
    <!-- Include jQuery first (required by api.js) -->
    <script src="../vendor/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/api.js"></script>
    <script>
        const resultsDiv = document.getElementById('results');
        let lastPackId = null;
        
        function log(message, isError = false) {
            const timestamp = new Date().toLocaleTimeString();
            const className = isError ? 'error' : '';
            resultsDiv.innerHTML += `<div class="${className}">[${timestamp}] ${message}</div>`;
        }
        
        function clearResults() {
            resultsDiv.innerHTML = '';
        }
        
        async function testCreatePack() {
            log('Testing pack creation...');
            
            const testPack = {
                name: 'Test Pack ' + Date.now(),
                description: 'Test pack created for debugging',
                capacity_l: 65,
                weight_empty_g: 1500,
                type: 'weekend',
                sections: [
                    {
                        id: 'main',
                        name: 'Main Compartment',
                        items: [
                            {
                                name: 'Test Sleeping Bag',
                                weight_g: 900,
                                quantity: 1,
                                category: 'sleep-system'
                            },
                            {
                                name: 'Test Tent',
                                weight_g: 1200,
                                quantity: 1,
                                category: 'shelter'
                            }
                        ]
                    }
                ]
            };
            
            try {
                // Add timeout wrapper
                const createWithTimeout = new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => reject(new Error('Create timed out')), 10000);
                    
                    BttApi.backpacks.create(testPack).then(result => {
                        clearTimeout(timeout);
                        resolve(result);
                    }).catch(err => {
                        clearTimeout(timeout);
                        reject(err);
                    });
                });
                
                const result = await createWithTimeout;
                
                if (result && result.id) {
                    lastPackId = result.id;
                    log('✓ Pack created successfully! ID: ' + result.id);
                    log('Pack data: ' + JSON.stringify(result, null, 2));
                } else {
                    log('✗ Pack created but no ID returned', true);
                    log('Result: ' + JSON.stringify(result, null, 2));
                }
            } catch (error) {
                log('✗ Failed to create pack: ' + error.message, true);
                console.error('Create error:', error);
                
                // Try fallback save to localStorage
                log('Attempting fallback save to localStorage...');
                const localPacks = JSON.parse(localStorage.getItem('btt_test_packs') || '[]');
                testPack.id = 'local_' + Date.now();
                localPacks.push(testPack);
                localStorage.setItem('btt_test_packs', JSON.stringify(localPacks));
                lastPackId = testPack.id;
                log('✓ Pack saved locally with ID: ' + testPack.id);
            }
        }
        
        async function testLoadPacks() {
            log('Testing pack loading...');
            
            try {
                // Add timeout wrapper
                const loadWithTimeout = new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => reject(new Error('Load timed out')), 5000);
                    
                    BttApi.backpacks.list().then(result => {
                        clearTimeout(timeout);
                        resolve(result);
                    }).catch(err => {
                        clearTimeout(timeout);
                        reject(err);
                    });
                });
                
                const packs = await loadWithTimeout;
                
                if (packs && Array.isArray(packs)) {
                    log('✓ Loaded ' + packs.length + ' packs');
                    packs.forEach(pack => {
                        log('  - ' + pack.name + ' (ID: ' + pack.id + ')');
                    });
                } else {
                    log('✗ Invalid response from getAll', true);
                    log('Response: ' + JSON.stringify(packs, null, 2));
                }
            } catch (error) {
                log('✗ Failed to load packs: ' + error.message, true);
                
                // Try loading from localStorage
                log('Loading from localStorage fallback...');
                const localPacks = JSON.parse(localStorage.getItem('btt_test_packs') || '[]');
                log('Found ' + localPacks.length + ' local packs');
                localPacks.forEach(pack => {
                    log('  - ' + pack.name + ' (ID: ' + pack.id + ')');
                });
            }
        }
        
        async function testUpdatePack() {
            if (!lastPackId) {
                log('✗ No pack to update. Create a pack first!', true);
                return;
            }
            
            log('Testing pack update for ID: ' + lastPackId);
            
            const updatedData = {
                name: 'Updated Test Pack ' + Date.now(),
                description: 'This pack was updated',
                sections: [
                    {
                        id: 'main',
                        name: 'Main Compartment',
                        items: [
                            {
                                name: 'Updated Sleeping Bag',
                                weight_g: 850,
                                quantity: 1,
                                category: 'sleep-system'
                            }
                        ]
                    }
                ]
            };
            
            try {
                // Add timeout wrapper
                const updateWithTimeout = new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => reject(new Error('Update timed out')), 10000);
                    
                    BttApi.backpacks.update(lastPackId, updatedData).then(result => {
                        clearTimeout(timeout);
                        resolve(result);
                    }).catch(err => {
                        clearTimeout(timeout);
                        reject(err);
                    });
                });
                
                const result = await updateWithTimeout;
                
                if (result && result.id) {
                    log('✓ Pack updated successfully!');
                    log('Updated data: ' + JSON.stringify(result, null, 2));
                } else {
                    log('✗ Update returned unexpected result', true);
                    log('Result: ' + JSON.stringify(result, null, 2));
                }
            } catch (error) {
                log('✗ Failed to update pack: ' + error.message, true);
                console.error('Update error:', error);
            }
        }
        
        // Initialize API when ready
        document.addEventListener('DOMContentLoaded', function() {
            log('Page loaded, checking dependencies...');
            
            // Check jQuery availability
            if (typeof jQuery === 'undefined') {
                log('✗ jQuery not loaded!', true);
                return;
            }
            log('✓ jQuery loaded');
            
            // Check API availability
            setTimeout(() => {
                if (typeof BttApi !== 'undefined') {
                    log('✓ API is available');
                    log('API Base URL: ' + window.location.origin + '/BTT/api/index.php');
                } else {
                    log('✗ API not found!', true);
                }
            }, 1000);
        });
    </script>
</body>
</html>
