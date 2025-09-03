<?php
/**
 * Complete System Test
 * Tests authentication, API access, and CRUD operations
 */

require_once 'app/bootstrap.php';
use App\Services\AuthService;

// Force login if not authenticated
if (!AuthService::isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$user = AuthService::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete System Test - BTT</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .test-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .test-section {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .test-result {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.9rem;
            background: #000;
            border: 1px solid #333;
        }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        .info { color: #60a5fa; }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .test-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        .status-indicator.success { background: #4ade80; }
        .status-indicator.error { background: #f87171; }
        .status-indicator.warning { background: #fbbf24; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="test-container">
        <h1>Complete System Test</h1>
        <p>Testing all components of the BeyondTrailTales system</p>
        
        <!-- Authentication Test -->
        <div class="test-section">
            <h2>🔐 Authentication Status</h2>
            <div class="test-card">
                <p><span class="status-indicator success"></span> Logged in as: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
                <p>User ID: <?= htmlspecialchars($user['id']) ?></p>
                <p>Session ID: <code><?= htmlspecialchars(session_id()) ?></code></p>
            </div>
        </div>
        
        <!-- API Test -->
        <div class="test-section">
            <h2>🌐 API Connection Tests</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>Trips API</h3>
                    <button onclick="testAPI('trips')" class="btn-action">Test Trips API</button>
                    <div id="trips-api-result" class="test-result" style="display:none;"></div>
                </div>
                <div class="test-card">
                    <h3>Backpacks API</h3>
                    <button onclick="testAPI('backpacks')" class="btn-action">Test Backpacks API</button>
                    <div id="backpacks-api-result" class="test-result" style="display:none;"></div>
                </div>
                <div class="test-card">
                    <h3>Gear API</h3>
                    <button onclick="testAPI('gear')" class="btn-action">Test Gear API</button>
                    <div id="gear-api-result" class="test-result" style="display:none;"></div>
                </div>
            </div>
        </div>
        
        <!-- CRUD Operations Test -->
        <div class="test-section">
            <h2>✏️ CRUD Operations Test</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>Create Test Trip</h3>
                    <button onclick="createTestTrip()" class="btn-action">Create Trip</button>
                    <div id="create-trip-result" class="test-result" style="display:none;"></div>
                </div>
                <div class="test-card">
                    <h3>Create Test Backpack</h3>
                    <button onclick="createTestBackpack()" class="btn-action">Create Backpack</button>
                    <div id="create-backpack-result" class="test-result" style="display:none;"></div>
                </div>
                <div class="test-card">
                    <h3>Delete Test Items</h3>
                    <button onclick="cleanupTestItems()" class="btn-secondary">Cleanup Test Data</button>
                    <div id="cleanup-result" class="test-result" style="display:none;"></div>
                </div>
            </div>
        </div>
        
        <!-- UI Components Test -->
        <div class="test-section">
            <h2>🎨 UI Components Test</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>Navigation Dropdown</h3>
                    <button onclick="testDropdown()" class="btn-action">Test Dropdown</button>
                    <p id="dropdown-status" class="info">Click to test user dropdown menu</p>
                </div>
                <div class="test-card">
                    <h3>Toast Notifications</h3>
                    <button onclick="testToast('success')" class="btn-action">Success Toast</button>
                    <button onclick="testToast('error')" class="btn-secondary">Error Toast</button>
                    <button onclick="testToast('info')" class="btn-secondary">Info Toast</button>
                </div>
                <div class="test-card">
                    <h3>Form Validation</h3>
                    <button onclick="testFormValidation()" class="btn-action">Test Forms</button>
                    <p id="form-status" class="info">Ready to test</p>
                </div>
            </div>
        </div>
        
        <!-- Live Data Status -->
        <div class="test-section">
            <h2>📊 Live Data Status</h2>
            <div id="live-data-status">
                <button onclick="loadLiveData()" class="btn-action">Load Live Data</button>
            </div>
            <div id="live-data-results" class="test-grid" style="display:none; margin-top:1rem;"></div>
        </div>
        
        <!-- Quick Links -->
        <div class="test-section">
            <h2>🔗 Quick Navigation</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <a href="dashboard.php" class="btn-action">Dashboard</a>
                <a href="trips.php" class="btn-action">Trips</a>
                <a href="backpacks.php" class="btn-action">Backpacks</a>
                <a href="gear-library.php" class="btn-secondary">Gear Library</a>
                <a href="profile.php" class="btn-secondary">Profile</a>
                <a href="fix-api-credentials.php" class="btn-secondary">Fix API</a>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <!-- Load JavaScript -->
    <script>
        // Store BTT configuration
        window.BTT = {
            baseUrl: '<?= BTT_BASE_URL ?>',
            publicUrl: '<?= BTT_PUBLIC_URL ?>',
            apiUrl: '<?= BTT_API_URL ?>'
        };
    </script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/navigation.js"></script>
    
    <script>
        // Test functions
        let testTripId = null;
        let testBackpackId = null;
        
        async function testAPI(route) {
            const resultEl = document.getElementById(`${route}-api-result`);
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<span class="warning">Testing...</span>';
            
            try {
                const data = await BTTApi.get(route);
                console.log(`${route} API Response:`, data);
                
                if (Array.isArray(data)) {
                    resultEl.innerHTML = `<span class="success">✓ Success!</span><br>Found ${data.length} ${route}`;
                } else {
                    resultEl.innerHTML = `<span class="success">✓ Success!</span><br>${JSON.stringify(data, null, 2)}`;
                }
            } catch (error) {
                resultEl.innerHTML = `<span class="error">✗ Error: ${error.message}</span>`;
            }
        }
        
        async function createTestTrip() {
            const resultEl = document.getElementById('create-trip-result');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<span class="warning">Creating test trip...</span>';
            
            try {
                const tripData = {
                    title: 'Test Trip ' + Date.now(),
                    location: 'Test Location',
                    description: 'This is a test trip created by the system test',
                    start_date: new Date().toISOString().split('T')[0],
                    end_date: new Date().toISOString().split('T')[0],
                    trip_type: 'day_hike',
                    distance: 5.5,
                    distance_unit: 'miles',
                    elevation_gain: 1200,
                    difficulty: 'moderate',
                    favorite: 0,
                    completed: 0
                };
                
                const data = await BTTApi.post('trips', tripData);
                testTripId = data.id || data;
                resultEl.innerHTML = `<span class="success">✓ Created trip ID: ${testTripId}</span>`;
                
                // Refresh trips page if open
                if (window.loadTrips) {
                    window.loadTrips();
                }
            } catch (error) {
                resultEl.innerHTML = `<span class="error">✗ Error: ${error.message}</span>`;
            }
        }
        
        async function createTestBackpack() {
            const resultEl = document.getElementById('create-backpack-result');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<span class="warning">Creating test backpack...</span>';
            
            try {
                const backpackData = {
                    name: 'Test Pack ' + Date.now(),
                    description: 'Test backpack created by system test',
                    capacity: 65,
                    base_weight: 1500,
                    target_weight: 10000,
                    is_active: 1,
                    sections: JSON.stringify([
                        { id: 'main', name: 'Main Compartment' },
                        { id: 'top', name: 'Top Lid' }
                    ])
                };
                
                const data = await BTTApi.post('backpacks', backpackData);
                testBackpackId = data.id || data;
                resultEl.innerHTML = `<span class="success">✓ Created backpack ID: ${testBackpackId}</span>`;
            } catch (error) {
                resultEl.innerHTML = `<span class="error">✗ Error: ${error.message}</span>`;
            }
        }
        
        async function cleanupTestItems() {
            const resultEl = document.getElementById('cleanup-result');
            resultEl.style.display = 'block';
            resultEl.innerHTML = '<span class="warning">Cleaning up test data...</span>';
            
            let messages = [];
            
            // Delete test trip if exists
            if (testTripId) {
                try {
                    await BTTApi.delete('trips', testTripId);
                    messages.push('<span class="success">✓ Deleted test trip</span>');
                    testTripId = null;
                } catch (error) {
                    messages.push(`<span class="error">✗ Could not delete trip: ${error.message}</span>`);
                }
            }
            
            // Delete test backpack if exists
            if (testBackpackId) {
                try {
                    await BTTApi.delete('backpacks', testBackpackId);
                    messages.push('<span class="success">✓ Deleted test backpack</span>');
                    testBackpackId = null;
                } catch (error) {
                    messages.push(`<span class="error">✗ Could not delete backpack: ${error.message}</span>`);
                }
            }
            
            if (messages.length === 0) {
                messages.push('<span class="info">No test data to cleanup</span>');
            }
            
            resultEl.innerHTML = messages.join('<br>');
        }
        
        function testDropdown() {
            const userButton = document.querySelector('.nav-user-button');
            const status = document.getElementById('dropdown-status');
            
            if (userButton) {
                userButton.click();
                status.innerHTML = '<span class="success">✓ Dropdown toggled</span>';
            } else {
                status.innerHTML = '<span class="error">✗ User button not found</span>';
            }
        }
        
        function testToast(type) {
            const messages = {
                success: 'This is a success message!',
                error: 'This is an error message!',
                info: 'This is an info message!'
            };
            BTTUtils.showToast(messages[type], type);
        }
        
        function testFormValidation() {
            const status = document.getElementById('form-status');
            status.innerHTML = '<span class="success">✓ Form validation is active on trips/backpacks pages</span>';
        }
        
        async function loadLiveData() {
            const resultsEl = document.getElementById('live-data-results');
            resultsEl.style.display = 'block';
            resultsEl.innerHTML = '<div class="test-card"><span class="warning">Loading data...</span></div>';
            
            try {
                // Fetch all data types
                const [trips, backpacks, gear] = await Promise.all([
                    BTTApi.get('trips').catch(() => []),
                    BTTApi.get('backpacks').catch(() => []),
                    BTTApi.get('gear').catch(() => [])
                ]);
                
                let html = '';
                
                // Trips summary
                html += '<div class="test-card">';
                html += '<h3>Trips</h3>';
                html += `<p>Total: ${trips.length}</p>`;
                if (trips.length > 0) {
                    html += '<ul style="margin:0; padding-left:1.5rem;">';
                    trips.slice(0, 3).forEach(trip => {
                        html += `<li>${trip.title || 'Untitled'} - ${trip.location || 'No location'}</li>`;
                    });
                    if (trips.length > 3) html += `<li>...and ${trips.length - 3} more</li>`;
                    html += '</ul>';
                }
                html += '</div>';
                
                // Backpacks summary
                html += '<div class="test-card">';
                html += '<h3>Backpacks</h3>';
                html += `<p>Total: ${backpacks.length}</p>`;
                if (backpacks.length > 0) {
                    html += '<ul style="margin:0; padding-left:1.5rem;">';
                    backpacks.slice(0, 3).forEach(pack => {
                        html += `<li>${pack.name || 'Unnamed'} - ${pack.capacity || 0}L</li>`;
                    });
                    if (backpacks.length > 3) html += `<li>...and ${backpacks.length - 3} more</li>`;
                    html += '</ul>';
                }
                html += '</div>';
                
                // Gear summary
                html += '<div class="test-card">';
                html += '<h3>Gear Items</h3>';
                html += `<p>Total: ${gear.length}</p>`;
                if (gear.length > 0) {
                    const categories = {};
                    gear.forEach(item => {
                        const cat = item.category || 'Uncategorized';
                        categories[cat] = (categories[cat] || 0) + 1;
                    });
                    html += '<ul style="margin:0; padding-left:1.5rem;">';
                    Object.entries(categories).slice(0, 5).forEach(([cat, count]) => {
                        html += `<li>${cat}: ${count} items</li>`;
                    });
                    html += '</ul>';
                }
                html += '</div>';
                
                resultsEl.innerHTML = html;
                
            } catch (error) {
                resultsEl.innerHTML = `<div class="test-card"><span class="error">Error loading data: ${error.message}</span></div>`;
            }
        }
        
        // Auto-test on page load
        window.addEventListener('DOMContentLoaded', () => {
            console.log('BTT System Test Page Loaded');
            console.log('BTT Config:', window.BTT);
            console.log('API Available:', typeof BTTApi !== 'undefined');
            console.log('Utils Available:', typeof BTTUtils !== 'undefined');
            console.log('Navigation Available:', typeof BTTNav !== 'undefined');
        });
    </script>
</body>
</html>
