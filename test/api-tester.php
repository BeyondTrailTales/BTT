<?php
/**
 * API Endpoint Tester
 * Tests LIVE BTT API endpoints with real data
 */

$pageTitle = 'API Endpoint Tester';
$pageDescription = 'Test live BTT API endpoints';
$pageId = 'api-tester';

require_once __DIR__ . '/includes/header.php';

// Get live API base URL
$apiBase = 'http://localhost/BTT/api';
?>

<div class="api-tester-container">
    <h1>🔌 Live API Endpoint Tester</h1>
    <p class="lead">Test actual BTT API endpoints with real data from your application</p>

    <!-- API Endpoint Selector -->
    <div class="api-controls card-forest">
        <div class="control-group">
            <label for="endpoint-group">Endpoint Group:</label>
            <select id="endpoint-group" class="form-control" onchange="loadEndpoints()">
                <option value="backpacks">Backpacks</option>
                <option value="trips">Trips</option>
                <option value="gamification">Gamification</option>
                <option value="health">System Health</option>
            </select>
        </div>

        <div class="control-group">
            <label for="method">Method:</label>
            <select id="method" class="form-control">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>
        </div>

        <div class="control-group">
            <label for="endpoint">Endpoint:</label>
            <input type="text" id="endpoint" class="form-control" value="/index.php?route=backpacks" />
        </div>

        <div class="control-group">
            <label for="request-body">Request Body (JSON):</label>
            <textarea id="request-body" class="form-control code-editor" rows="5" placeholder='{"name": "Test Backpack"}'></textarea>
        </div>

        <div class="control-actions">
            <button onclick="sendRequest()" class="btn btn-primary">
                <span>▶️</span> Send Request
            </button>
            <button onclick="runTestSuite()" class="btn btn-secondary">
                <span>🧪</span> Run Test Suite
            </button>
            <button onclick="clearHistory()" class="btn btn-warning">
                <span>🗑️</span> Clear History
            </button>
        </div>
    </div>

    <!-- Response Display -->
    <div class="response-container card-forest">
        <h3>Response</h3>
        <div class="response-meta">
            <span class="status-badge" id="status">Ready</span>
            <span class="time-badge" id="response-time">0ms</span>
            <span class="size-badge" id="response-size">0 bytes</span>
        </div>
        <pre id="response-display" class="code-display"></pre>
    </div>

    <!-- Test Suite Results -->
    <div id="test-results" class="test-results card-forest" style="display: none;">
        <h3>Test Suite Results</h3>
        <div id="test-summary"></div>
        <div id="test-details"></div>
    </div>

    <!-- Request History -->
    <div class="history-container card-forest">
        <h3>Request History</h3>
        <div id="request-history"></div>
    </div>
</div>

<style>
.api-tester-container {
    max-width: 1200px;
    margin: 0 auto;
}

.card-forest {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
}

.control-group {
    margin-bottom: var(--space-4);
}

.control-group label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: var(--font-semibold);
    color: var(--forest-leaf);
}

.form-control {
    width: 100%;
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    background: var(--forest-canopy);
    color: var(--text-primary);
}

.code-editor {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}

.control-actions {
    display: flex;
    gap: var(--space-3);
    margin-top: var(--space-4);
}

.response-meta {
    display: flex;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
}

.status-badge, .time-badge, .size-badge {
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
}

.status-badge.success { background: var(--forest-mint); color: white; }
.status-badge.error { background: var(--forest-berry); color: white; }
.time-badge { background: var(--forest-lake); color: white; }
.size-badge { background: var(--forest-honey); color: var(--forest-deep); }

.code-display {
    background: var(--forest-shadow);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    overflow-x: auto;
    max-height: 400px;
    color: var(--forest-mint);
}

.history-item {
    padding: var(--space-3);
    border-bottom: 1px solid var(--glass-border);
    cursor: pointer;
    transition: var(--transition-all);
}

.history-item:hover {
    background: var(--glass-bg-hover);
}

.test-pass { color: var(--forest-mint); }
.test-fail { color: var(--forest-berry); }
</style>

<script>
const API_BASE = '<?php echo $apiBase; ?>';
let requestHistory = JSON.parse(localStorage.getItem('btt-api-history') || '[]');

// Endpoint presets for different groups
const endpoints = {
    backpacks: [
        { method: 'GET', path: '/index.php?route=backpacks', description: 'List all backpacks' },
        { method: 'POST', path: '/index.php?route=backpacks', description: 'Create new backpack' },
        { method: 'GET', path: '/index.php?route=backpacks&id={id}', description: 'Get specific backpack' },
        { method: 'PUT', path: '/index.php?route=backpacks&id={id}', description: 'Update backpack' },
        { method: 'DELETE', path: '/index.php?route=backpacks&id={id}', description: 'Delete backpack' }
    ],
    trips: [
        { method: 'GET', path: '/index.php?route=trips', description: 'List all trips' },
        { method: 'POST', path: '/index.php?route=trips', description: 'Create new trip' },
        { method: 'GET', path: '/index.php?route=trips&id={id}', description: 'Get specific trip' },
        { method: 'PUT', path: '/index.php?route=trips&id={id}', description: 'Update trip' },
        { method: 'DELETE', path: '/index.php?route=trips&id={id}', description: 'Delete trip' }
    ],
    gamification: [
        { method: 'GET', path: '/routes/gamification.php?action=status', description: 'Get gamification status' },
        { method: 'GET', path: '/routes/gamification.php?action=badges', description: 'Get all badges' },
        { method: 'POST', path: '/routes/gamification.php?action=award_xp', description: 'Award XP' },
        { method: 'POST', path: '/routes/gamification.php?action=update_streak', description: 'Update streak' }
    ],
    health: [
        { method: 'GET', path: '/index.php?route=health', description: 'System health check' }
    ]
};

function loadEndpoints() {
    const group = document.getElementById('endpoint-group').value;
    const endpointList = endpoints[group];
    
    if (endpointList && endpointList.length > 0) {
        const first = endpointList[0];
        document.getElementById('method').value = first.method;
        document.getElementById('endpoint').value = first.path;
    }
}

async function sendRequest() {
    const method = document.getElementById('method').value;
    const endpoint = document.getElementById('endpoint').value;
    const bodyText = document.getElementById('request-body').value;
    
    const url = API_BASE + endpoint;
    const startTime = performance.now();
    
    // Update UI
    document.getElementById('status').textContent = 'Loading...';
    document.getElementById('status').className = 'status-badge';
    
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (method !== 'GET' && bodyText) {
            options.body = bodyText;
        }
        
        const response = await fetch(url, options);
        const endTime = performance.now();
        const responseTime = Math.round(endTime - startTime);
        
        const data = await response.text();
        let jsonData;
        try {
            jsonData = JSON.parse(data);
        } catch {
            jsonData = data;
        }
        
        // Update response display
        document.getElementById('status').textContent = `${response.status} ${response.statusText}`;
        document.getElementById('status').className = response.ok ? 'status-badge success' : 'status-badge error';
        document.getElementById('response-time').textContent = `${responseTime}ms`;
        document.getElementById('response-size').textContent = `${new Blob([data]).size} bytes`;
        document.getElementById('response-display').textContent = typeof jsonData === 'object' 
            ? JSON.stringify(jsonData, null, 2) 
            : data;
        
        // Save to history
        const historyItem = {
            timestamp: new Date().toISOString(),
            method,
            endpoint,
            status: response.status,
            responseTime,
            success: response.ok
        };
        
        requestHistory.unshift(historyItem);
        if (requestHistory.length > 20) requestHistory.pop();
        localStorage.setItem('btt-api-history', JSON.stringify(requestHistory));
        displayHistory();
        
        // Log for debugging
        console.log('API Response:', jsonData);
        
    } catch (error) {
        document.getElementById('status').textContent = 'Error';
        document.getElementById('status').className = 'status-badge error';
        document.getElementById('response-display').textContent = error.message;
        console.error('Request failed:', error);
    }
}

async function runTestSuite() {
    const group = document.getElementById('endpoint-group').value;
    const testEndpoints = endpoints[group];
    const results = [];
    
    document.getElementById('test-results').style.display = 'block';
    document.getElementById('test-summary').innerHTML = '<div class="loading">Running tests...</div>';
    
    for (const endpoint of testEndpoints) {
        const startTime = performance.now();
        
        try {
            const response = await fetch(API_BASE + endpoint.path, {
                method: endpoint.method,
                headers: { 'Content-Type': 'application/json' }
            });
            
            const endTime = performance.now();
            
            results.push({
                endpoint: endpoint.path,
                method: endpoint.method,
                description: endpoint.description,
                status: response.status,
                success: response.ok,
                time: Math.round(endTime - startTime)
            });
        } catch (error) {
            results.push({
                endpoint: endpoint.path,
                method: endpoint.method,
                description: endpoint.description,
                status: 0,
                success: false,
                error: error.message,
                time: 0
            });
        }
    }
    
    displayTestResults(results);
}

function displayTestResults(results) {
    const passed = results.filter(r => r.success).length;
    const failed = results.filter(r => !r.success).length;
    
    let summaryHtml = `
        <div class="test-summary-stats">
            <span class="test-pass">✅ Passed: ${passed}</span>
            <span class="test-fail">❌ Failed: ${failed}</span>
            <span>Total: ${results.length}</span>
        </div>
    `;
    
    let detailsHtml = '<table class="test-table"><thead><tr><th>Endpoint</th><th>Method</th><th>Status</th><th>Time</th></tr></thead><tbody>';
    
    results.forEach(result => {
        const statusClass = result.success ? 'test-pass' : 'test-fail';
        const statusIcon = result.success ? '✅' : '❌';
        detailsHtml += `
            <tr class="${statusClass}">
                <td>${result.endpoint}</td>
                <td>${result.method}</td>
                <td>${statusIcon} ${result.status || 'Failed'}</td>
                <td>${result.time}ms</td>
            </tr>
        `;
    });
    
    detailsHtml += '</tbody></table>';
    
    document.getElementById('test-summary').innerHTML = summaryHtml;
    document.getElementById('test-details').innerHTML = detailsHtml;
}

function displayHistory() {
    let html = '';
    requestHistory.slice(0, 10).forEach(item => {
        const time = new Date(item.timestamp).toLocaleTimeString();
        const statusClass = item.success ? 'test-pass' : 'test-fail';
        html += `
            <div class="history-item" onclick="loadHistoryItem('${item.endpoint}', '${item.method}')">
                <span class="${statusClass}">${item.method}</span>
                <span>${item.endpoint}</span>
                <span>${item.status}</span>
                <span>${item.responseTime}ms</span>
                <span>${time}</span>
            </div>
        `;
    });
    document.getElementById('request-history').innerHTML = html || '<p>No history yet</p>';
}

function loadHistoryItem(endpoint, method) {
    document.getElementById('endpoint').value = endpoint;
    document.getElementById('method').value = method;
}

function clearHistory() {
    if (confirm('Clear all request history?')) {
        requestHistory = [];
        localStorage.removeItem('btt-api-history');
        displayHistory();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadEndpoints();
    displayHistory();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
