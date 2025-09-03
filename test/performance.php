<?php
/**
 * Performance Benchmarks
 * Tests LIVE application performance
 */

$pageTitle = 'Performance Benchmarks';
$pageDescription = 'Test live application performance';
$pageId = 'performance';

require_once __DIR__ . '/includes/header.php';

// Live endpoints to test
$endpoints = [
    ['name' => 'Homepage', 'url' => '/BTT/', 'type' => 'page'],
    ['name' => 'Backpacks Page', 'url' => '/BTT/public/backpacks.php', 'type' => 'page'],
    ['name' => 'Trips Page', 'url' => '/BTT/public/trips.php', 'type' => 'page'],
    ['name' => 'API Health', 'url' => '/BTT/api/index.php?route=health', 'type' => 'api'],
    ['name' => 'API Backpacks', 'url' => '/BTT/api/index.php?route=backpacks', 'type' => 'api'],
    ['name' => 'API Trips', 'url' => '/BTT/api/index.php?route=trips', 'type' => 'api'],
    ['name' => 'Gamification Status', 'url' => '/BTT/api/routes/gamification.php?action=status', 'type' => 'api'],
];
?>

<div class="performance-container">
    <h1>⚡ Live Performance Benchmarks</h1>
    <p class="lead">Testing real-time performance of your BTT application</p>

    <!-- Performance Controls -->
    <div class="perf-controls card-forest">
        <div class="control-row">
            <div class="control-group">
                <label for="iterations">Iterations per endpoint:</label>
                <input type="number" id="iterations" class="form-control" value="5" min="1" max="20">
            </div>
            <div class="control-group">
                <label for="delay">Delay between requests (ms):</label>
                <input type="number" id="delay" class="form-control" value="100" min="0" max="1000">
            </div>
        </div>
        <div class="control-actions">
            <button onclick="runBenchmarks()" class="btn btn-primary" id="run-btn">
                ▶️ Run Performance Tests
            </button>
            <button onclick="stopBenchmarks()" class="btn btn-warning" id="stop-btn" style="display: none;">
                ⏹️ Stop Tests
            </button>
            <button onclick="clearResults()" class="btn btn-secondary">
                🗑️ Clear Results
            </button>
        </div>
    </div>

    <!-- Real-time Metrics -->
    <div class="metrics-grid">
        <div class="metric-card card-forest">
            <div class="metric-label">Average Response Time</div>
            <div class="metric-value" id="avg-response">0 ms</div>
        </div>
        <div class="metric-card card-forest">
            <div class="metric-label">Fastest Response</div>
            <div class="metric-value" id="min-response">0 ms</div>
        </div>
        <div class="metric-card card-forest">
            <div class="metric-label">Slowest Response</div>
            <div class="metric-value" id="max-response">0 ms</div>
        </div>
        <div class="metric-card card-forest">
            <div class="metric-label">Success Rate</div>
            <div class="metric-value" id="success-rate">100%</div>
        </div>
    </div>

    <!-- Progress -->
    <div class="progress-container card-forest" id="progress-container" style="display: none;">
        <h3>Testing Progress</h3>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        <div class="progress-text" id="progress-text">0%</div>
    </div>

    <!-- Results Table -->
    <div class="results-container card-forest">
        <h2>Performance Results</h2>
        <table class="results-table" id="results-table">
            <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Type</th>
                    <th>Avg Time</th>
                    <th>Min Time</th>
                    <th>Max Time</th>
                    <th>P95</th>
                    <th>Success Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="results-body">
                <tr>
                    <td colspan="8" class="no-data">No tests run yet. Click "Run Performance Tests" to start.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Performance Chart -->
    <div class="chart-container card-forest">
        <h2>Response Time Distribution</h2>
        <canvas id="perfChart" width="400" height="200"></canvas>
    </div>

    <!-- Live Application Status -->
    <div class="status-container card-forest">
        <h2>Live Application Status</h2>
        <div id="app-status" class="status-grid">
            <!-- Populated by JavaScript -->
        </div>
    </div>
</div>

<style>
.performance-container {
    max-width: 1200px;
    margin: 0 auto;
}

.control-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-4);
    margin: var(--space-6) 0;
}

.metric-card {
    text-align: center;
    padding: var(--space-6);
}

.metric-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-2);
}

.metric-value {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--forest-mint);
}

.progress-bar-container {
    width: 100%;
    height: 30px;
    background: var(--forest-shadow);
    border-radius: var(--radius-full);
    overflow: hidden;
    margin: var(--space-4) 0;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(135deg, var(--forest-mint), var(--forest-leaf));
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    text-align: center;
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
}

.results-table {
    width: 100%;
    border-collapse: collapse;
}

.results-table th {
    background: var(--forest-canopy);
    padding: var(--space-3);
    text-align: left;
    font-weight: var(--font-semibold);
}

.results-table td {
    padding: var(--space-3);
    border-bottom: 1px solid var(--glass-border);
}

.no-data {
    text-align: center;
    color: var(--text-secondary);
    padding: var(--space-6) !important;
}

.status-good { color: var(--forest-mint); }
.status-warning { color: var(--forest-honey); }
.status-bad { color: var(--forest-berry); }

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-4);
}

.status-item {
    padding: var(--space-3);
    background: var(--glass-bg);
    border-radius: var(--radius-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let isRunning = false;
let results = {};
let chart = null;

const endpoints = <?php echo json_encode($endpoints); ?>;

async function runBenchmarks() {
    if (isRunning) return;
    
    isRunning = true;
    results = {};
    
    // Update UI
    document.getElementById('run-btn').style.display = 'none';
    document.getElementById('stop-btn').style.display = 'inline-block';
    document.getElementById('progress-container').style.display = 'block';
    document.getElementById('results-body').innerHTML = '';
    
    const iterations = parseInt(document.getElementById('iterations').value);
    const delay = parseInt(document.getElementById('delay').value);
    
    const totalTests = endpoints.length * iterations;
    let completedTests = 0;
    
    for (const endpoint of endpoints) {
        if (!isRunning) break;
        
        results[endpoint.name] = {
            times: [],
            successes: 0,
            failures: 0
        };
        
        for (let i = 0; i < iterations; i++) {
            if (!isRunning) break;
            
            const startTime = performance.now();
            
            try {
                const response = await fetch('http://localhost' + endpoint.url);
                const endTime = performance.now();
                const responseTime = endTime - startTime;
                
                results[endpoint.name].times.push(responseTime);
                
                if (response.ok) {
                    results[endpoint.name].successes++;
                } else {
                    results[endpoint.name].failures++;
                }
            } catch (error) {
                results[endpoint.name].failures++;
                console.error(`Failed to test ${endpoint.name}:`, error);
            }
            
            completedTests++;
            updateProgress(completedTests, totalTests);
            
            if (delay > 0) {
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
        
        // Add result row
        addResultRow(endpoint, results[endpoint.name]);
    }
    
    // Update metrics
    updateMetrics();
    updateChart();
    updateAppStatus();
    
    isRunning = false;
    document.getElementById('run-btn').style.display = 'inline-block';
    document.getElementById('stop-btn').style.display = 'none';
    
    BTTTest.showAlert('Performance tests completed!', 'success');
}

function stopBenchmarks() {
    isRunning = false;
    document.getElementById('run-btn').style.display = 'inline-block';
    document.getElementById('stop-btn').style.display = 'none';
    BTTTest.showAlert('Tests stopped', 'info');
}

function updateProgress(completed, total) {
    const percentage = Math.round((completed / total) * 100);
    document.getElementById('progress-bar').style.width = percentage + '%';
    document.getElementById('progress-text').textContent = `${percentage}% (${completed}/${total})`;
}

function addResultRow(endpoint, data) {
    if (data.times.length === 0) return;
    
    const times = data.times.sort((a, b) => a - b);
    const avg = times.reduce((a, b) => a + b, 0) / times.length;
    const min = times[0];
    const max = times[times.length - 1];
    const p95 = times[Math.floor(times.length * 0.95)];
    const successRate = (data.successes / (data.successes + data.failures)) * 100;
    
    const statusClass = avg < 200 ? 'status-good' : avg < 500 ? 'status-warning' : 'status-bad';
    
    const row = `
        <tr>
            <td>${endpoint.name}</td>
            <td><span class="badge">${endpoint.type.toUpperCase()}</span></td>
            <td class="${statusClass}">${Math.round(avg)} ms</td>
            <td>${Math.round(min)} ms</td>
            <td>${Math.round(max)} ms</td>
            <td>${Math.round(p95)} ms</td>
            <td>${successRate.toFixed(1)}%</td>
            <td><span class="${statusClass}">●</span></td>
        </tr>
    `;
    
    document.getElementById('results-body').innerHTML += row;
}

function updateMetrics() {
    let allTimes = [];
    let totalSuccesses = 0;
    let totalTests = 0;
    
    for (const endpoint in results) {
        allTimes = allTimes.concat(results[endpoint].times);
        totalSuccesses += results[endpoint].successes;
        totalTests += results[endpoint].successes + results[endpoint].failures;
    }
    
    if (allTimes.length > 0) {
        const avg = allTimes.reduce((a, b) => a + b, 0) / allTimes.length;
        const min = Math.min(...allTimes);
        const max = Math.max(...allTimes);
        const successRate = (totalSuccesses / totalTests) * 100;
        
        document.getElementById('avg-response').textContent = Math.round(avg) + ' ms';
        document.getElementById('min-response').textContent = Math.round(min) + ' ms';
        document.getElementById('max-response').textContent = Math.round(max) + ' ms';
        document.getElementById('success-rate').textContent = successRate.toFixed(1) + '%';
    }
}

function updateChart() {
    const ctx = document.getElementById('perfChart').getContext('2d');
    
    const labels = [];
    const data = [];
    
    for (const endpoint in results) {
        if (results[endpoint].times.length > 0) {
            labels.push(endpoint);
            const avg = results[endpoint].times.reduce((a, b) => a + b, 0) / results[endpoint].times.length;
            data.push(Math.round(avg));
        }
    }
    
    if (chart) {
        chart.destroy();
    }
    
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Average Response Time (ms)',
                data: data,
                backgroundColor: 'rgba(16, 185, 129, 0.5)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

async function updateAppStatus() {
    const statusDiv = document.getElementById('app-status');
    let html = '';
    
    for (const endpoint of endpoints) {
        try {
            const response = await fetch('http://localhost' + endpoint.url);
            const status = response.ok ? 'Online' : 'Error';
            const statusClass = response.ok ? 'status-good' : 'status-bad';
            
            html += `
                <div class="status-item">
                    <span>${endpoint.name}</span>
                    <span class="${statusClass}">● ${status}</span>
                </div>
            `;
        } catch {
            html += `
                <div class="status-item">
                    <span>${endpoint.name}</span>
                    <span class="status-bad">● Offline</span>
                </div>
            `;
        }
    }
    
    statusDiv.innerHTML = html;
}

function clearResults() {
    results = {};
    document.getElementById('results-body').innerHTML = '<tr><td colspan="8" class="no-data">Results cleared</td></tr>';
    document.getElementById('avg-response').textContent = '0 ms';
    document.getElementById('min-response').textContent = '0 ms';
    document.getElementById('max-response').textContent = '0 ms';
    document.getElementById('success-rate').textContent = '100%';
    if (chart) chart.destroy();
}

// Check app status on load
document.addEventListener('DOMContentLoaded', () => {
    updateAppStatus();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
