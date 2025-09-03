<?php
// Test User Data - Verify trips and backpacks load per user
require_once __DIR__ . '/../app/bootstrap.php';
use App\Services\AuthService;

// Force authentication check
if (!AuthService::isAuthenticated()) {
    header('Location: /BTT/public/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user = AuthService::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test User Data - BTT</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; margin: 2rem; background: #f3f4f6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .user-info { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .status { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem; font-weight: 600; }
        .status.ok { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        pre { background: #1f2937; color: #10b981; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.875rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        button { background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 500; margin: 0.5rem; }
        button:hover { background: #059669; }
        button:disabled { background: #9ca3af; cursor: not-allowed; }
        h1, h2 { color: #1f2937; }
        .data-item { padding: 1rem; background: #f9fafb; border-left: 4px solid #3b82f6; margin: 0.5rem 0; border-radius: 4px; }
        .loading { opacity: 0.5; pointer-events: none; }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f4f6; border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <h1>🔍 User Data Test</h1>
            <p>Logged in as: <strong><?php echo htmlspecialchars($user['username']); ?></strong> (ID: <?php echo $user['id']; ?>)</p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Session ID: <?php echo substr(session_id(), 0, 16); ?>...</p>
        </div>
        
        <div class="grid">
            <!-- Trips Section -->
            <div class="card">
                <h2>📍 My Trips</h2>
                <div style="margin-bottom: 1rem;">
                    <button onclick="loadTrips()">Load Trips</button>
                    <button onclick="createTestTrip()">Create Test Trip</button>
                    <button onclick="clearTrips()">Clear All</button>
                </div>
                <div id="trips-status"></div>
                <div id="trips-list"></div>
            </div>
            
            <!-- Backpacks Section -->
            <div class="card">
                <h2>🎒 My Backpacks</h2>
                <div style="margin-bottom: 1rem;">
                    <button onclick="loadBackpacks()">Load Backpacks</button>
                    <button onclick="createTestBackpack()">Create Test Pack</button>
                    <button onclick="clearBackpacks()">Clear All</button>
                </div>
                <div id="backpacks-status"></div>
                <div id="backpacks-list"></div>
            </div>
        </div>
        
        <!-- Raw API Response -->
        <div class="card">
            <h2>📝 API Response Log</h2>
            <button onclick="clearLog()">Clear Log</button>
            <pre id="api-log"></pre>
        </div>
    </div>
    
    <!-- Load jQuery and BTT utilities first -->
    <script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
    <script src="/BTT/assets/js/btt-utils.js"></script>
    <script src="/BTT/assets/js/api.js"></script>
    
    <script>
        // Wait for API to be ready
        $(document).ready(function() {
            console.log('Test page ready');
            console.log('BTTApi available:', typeof window.BTTApi);
            console.log('BttApi available:', typeof window.BttApi);
            console.log('BTTUtils available:', typeof window.BTTUtils);
            
            // Use whichever is available
            window.API = window.BTTApi || window.BttApi;
            
            if (!window.API) {
                logMessage('ERROR: API not loaded!', 'error');
            } else {
                logMessage('API loaded successfully', 'success');
                // Auto-load data on page load
                loadTrips();
                loadBackpacks();
            }
        });
        
        function logMessage(message, type = 'info') {
            const log = document.getElementById('api-log');
            const timestamp = new Date().toLocaleTimeString();
            const entry = `[${timestamp}] ${type.toUpperCase()}: ${message}\n`;
            log.textContent = entry + log.textContent;
            
            // Also log to console
            console.log(`[${type}]`, message);
        }
        
        function clearLog() {
            document.getElementById('api-log').textContent = '';
        }
        
        // Trips functions
        async function loadTrips() {
            const container = document.getElementById('trips-list');
            const status = document.getElementById('trips-status');
            
            container.innerHTML = '<div class="spinner"></div> Loading...';
            status.innerHTML = '';
            
            try {
                logMessage('Fetching trips...', 'info');
                const response = await API.get('trips');
                
                // Handle wrapped response format
                const trips = response.data || response;
                const tripsArray = Array.isArray(trips) ? trips : [];
                
                logMessage(`Loaded ${tripsArray.length} trips`, 'success');
                
                if (tripsArray.length === 0) {
                    container.innerHTML = '<p style="color: #6b7280;">No trips found. Click "Create Test Trip" to add one.</p>';
                } else {
                    container.innerHTML = tripsArray.map(trip => `
                        <div class="data-item">
                            <strong>${trip.title || 'Untitled'}</strong><br>
                            <small>
                                ID: ${trip.id} | 
                                User: ${trip.user_id} | 
                                Created: ${trip.created_at || 'N/A'}
                            </small>
                            ${trip.location ? `<br>Location: ${trip.location}` : ''}
                        </div>
                    `).join('');
                }
                
                status.innerHTML = `<span class="status ok">Loaded ${tripsArray.length} trips</span>`;
            } catch (error) {
                logMessage(`Error loading trips: ${error.message || error}`, 'error');
                container.innerHTML = '<p style="color: #dc2626;">Error loading trips. Check console for details.</p>';
                status.innerHTML = '<span class="status error">Failed to load</span>';
                console.error('Load trips error:', error);
            }
        }
        
        async function createTestTrip() {
            const status = document.getElementById('trips-status');
            status.innerHTML = '<div class="spinner"></div> Creating...';
            
            try {
                const tripData = {
                    title: `Test Trip ${Date.now()}`,
                    location: 'Test Location',
                    start_date: new Date().toISOString().split('T')[0],
                    description: 'Created from test page',
                    trip_type: 'day_hike'
                };
                
                logMessage(`Creating trip: ${JSON.stringify(tripData)}`, 'info');
                const response = await API.post('trips', tripData);
                
                logMessage(`Trip created: ${JSON.stringify(response)}`, 'success');
                status.innerHTML = '<span class="status ok">Trip created!</span>';
                
                // Reload trips
                await loadTrips();
            } catch (error) {
                logMessage(`Error creating trip: ${error.message || error}`, 'error');
                status.innerHTML = '<span class="status error">Failed to create</span>';
                console.error('Create trip error:', error);
            }
        }
        
        async function clearTrips() {
            if (!confirm('Delete all your trips? This cannot be undone.')) return;
            
            const status = document.getElementById('trips-status');
            status.innerHTML = '<div class="spinner"></div> Deleting...';
            
            try {
                // First load all trips
                const response = await API.get('trips');
                const trips = response.data || response;
                const tripsArray = Array.isArray(trips) ? trips : [];
                
                if (tripsArray.length > 0) {
                    // Delete each trip
                    for (const trip of tripsArray) {
                        logMessage(`Deleting trip ${trip.id}...`, 'info');
                        await API.delete('trips', trip.id);
                    }
                    logMessage(`Deleted ${tripsArray.length} trips`, 'success');
                }
                
                status.innerHTML = '<span class="status ok">All trips deleted</span>';
                await loadTrips();
            } catch (error) {
                logMessage(`Error clearing trips: ${error.message || error}`, 'error');
                status.innerHTML = '<span class="status error">Failed to clear</span>';
                console.error('Clear trips error:', error);
            }
        }
        
        // Backpacks functions
        async function loadBackpacks() {
            const container = document.getElementById('backpacks-list');
            const status = document.getElementById('backpacks-status');
            
            container.innerHTML = '<div class="spinner"></div> Loading...';
            status.innerHTML = '';
            
            try {
                logMessage('Fetching backpacks...', 'info');
                const response = await API.get('backpacks');
                
                // Handle wrapped response format
                const backpacks = response.data || response;
                const backpacksArray = Array.isArray(backpacks) ? backpacks : [];
                
                logMessage(`Loaded ${backpacksArray.length} backpacks`, 'success');
                
                if (backpacksArray.length === 0) {
                    container.innerHTML = '<p style="color: #6b7280;">No backpacks found. Click "Create Test Pack" to add one.</p>';
                } else {
                    container.innerHTML = backpacksArray.map(pack => `
                        <div class="data-item">
                            <strong>${pack.name || 'Unnamed Pack'}</strong><br>
                            <small>
                                ID: ${pack.id} | 
                                User: ${pack.user_id} | 
                                Items: ${pack.total_items || 0}
                            </small>
                            ${pack.description ? `<br>${pack.description}` : ''}
                        </div>
                    `).join('');
                }
                
                status.innerHTML = `<span class="status ok">Loaded ${backpacksArray.length} backpacks</span>`;
            } catch (error) {
                logMessage(`Error loading backpacks: ${error.message || error}`, 'error');
                container.innerHTML = '<p style="color: #dc2626;">Error loading backpacks. Check console for details.</p>';
                status.innerHTML = '<span class="status error">Failed to load</span>';
                console.error('Load backpacks error:', error);
            }
        }
        
        async function createTestBackpack() {
            const status = document.getElementById('backpacks-status');
            status.innerHTML = '<div class="spinner"></div> Creating...';
            
            try {
                const packData = {
                    name: `Test Pack ${Date.now()}`,
                    description: 'Created from test page',
                    capacity_liters: 65,
                    weight_empty_g: 1500,
                    sections: JSON.stringify([
                        {
                            name: 'Main',
                            items: [
                                { name: 'Test Item 1', quantity: 1, weight: 100 },
                                { name: 'Test Item 2', quantity: 2, weight: 50 }
                            ]
                        }
                    ])
                };
                
                logMessage(`Creating backpack: ${JSON.stringify(packData)}`, 'info');
                const response = await API.post('backpacks', packData);
                
                logMessage(`Backpack created: ${JSON.stringify(response)}`, 'success');
                status.innerHTML = '<span class="status ok">Backpack created!</span>';
                
                // Reload backpacks
                await loadBackpacks();
            } catch (error) {
                logMessage(`Error creating backpack: ${error.message || error}`, 'error');
                status.innerHTML = '<span class="status error">Failed to create</span>';
                console.error('Create backpack error:', error);
            }
        }
        
        async function clearBackpacks() {
            if (!confirm('Delete all your backpacks? This cannot be undone.')) return;
            
            const status = document.getElementById('backpacks-status');
            status.innerHTML = '<div class="spinner"></div> Deleting...';
            
            try {
                // First load all backpacks
                const response = await API.get('backpacks');
                const packs = response.data || response;
                const packsArray = Array.isArray(packs) ? packs : [];
                
                if (packsArray.length > 0) {
                    // Delete each backpack
                    for (const pack of packsArray) {
                        logMessage(`Deleting backpack ${pack.id}...`, 'info');
                        await API.delete('backpacks', pack.id);
                    }
                    logMessage(`Deleted ${packsArray.length} backpacks`, 'success');
                }
                
                status.innerHTML = '<span class="status ok">All backpacks deleted</span>';
                await loadBackpacks();
            } catch (error) {
                logMessage(`Error clearing backpacks: ${error.message || error}`, 'error');
                status.innerHTML = '<span class="status error">Failed to clear</span>';
                console.error('Clear backpacks error:', error);
            }
        }
    </script>
</body>
</html>
