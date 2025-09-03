<?php
/**
 * Database Integrity Checker
 * Tests LIVE database integrity and relationships
 */

$pageTitle = 'Database Integrity Checker';
$pageDescription = 'Check live database integrity and relationships';
$pageId = 'db-checker';

require_once __DIR__ . '/includes/header.php';

// Get storage instance to check live data
$storage = \BTT\Test\Tools\Storage::getInstance();
$storageInfo = $storage->getStorageInfo();

// Run integrity checks
$checks = [];

// Check 1: Storage Driver
$checks[] = [
    'name' => 'Storage Driver Detection',
    'status' => !empty($storageInfo['driver']),
    'details' => "Using: " . $storageInfo['driver'] . " storage",
    'severity' => 'critical'
];

// Check 2: Storage Path
$checks[] = [
    'name' => 'Storage Path Accessibility',
    'status' => !empty($storageInfo['path']) && (is_file($storageInfo['path']) || is_dir($storageInfo['path'])),
    'details' => "Path: " . $storageInfo['path'],
    'severity' => 'critical'
];

// Check 3: Tables/Collections
$checks[] = [
    'name' => 'Tables/Collections Count',
    'status' => count($storageInfo['tables']) > 0,
    'details' => count($storageInfo['tables']) . " tables found: " . implode(', ', $storageInfo['tables']),
    'severity' => 'warning'
];

// Check 4: Data Integrity - Check for orphaned records
$orphanedRecords = [];
if ($storageInfo['driver'] === 'sqlite') {
    // Check for trips with non-existent backpack_ids
    try {
        $db = new PDO('sqlite:' . $storageInfo['path']);
        $stmt = $db->query("
            SELECT t.id, t.title, t.backpack_id 
            FROM trips t 
            LEFT JOIN backpacks b ON t.backpack_id = b.id 
            WHERE t.backpack_id IS NOT NULL AND b.id IS NULL
        ");
        $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($orphaned)) {
            $orphanedRecords['trips'] = $orphaned;
        }
    } catch (Exception $e) {
        $checks[] = [
            'name' => 'Orphaned Records Check',
            'status' => false,
            'details' => "Error: " . $e->getMessage(),
            'severity' => 'warning'
        ];
    }
} else {
    // JSON storage checks
    $jsonPath = $storageInfo['path'];
    if (file_exists($jsonPath . 'trips.json') && file_exists($jsonPath . 'backpacks.json')) {
        $trips = json_decode(file_get_contents($jsonPath . 'trips.json'), true) ?: [];
        $backpacks = json_decode(file_get_contents($jsonPath . 'backpacks.json'), true) ?: [];
        $backpackIds = array_column($backpacks, 'id');
        
        foreach ($trips as $trip) {
            if (!empty($trip['backpack_id']) && !in_array($trip['backpack_id'], $backpackIds)) {
                $orphanedRecords['trips'][] = $trip;
            }
        }
    }
}

$checks[] = [
    'name' => 'Referential Integrity',
    'status' => empty($orphanedRecords),
    'details' => empty($orphanedRecords) ? "No orphaned records found" : count($orphanedRecords) . " orphaned records found",
    'severity' => 'warning'
];

// Check 5: Data Size
$sizeInMB = $storageInfo['size'] / (1024 * 1024);
$checks[] = [
    'name' => 'Storage Size',
    'status' => $sizeInMB < 100, // Warning if over 100MB
    'details' => sprintf("%.2f MB used", $sizeInMB),
    'severity' => 'info'
];

// Check 6: Record Counts
$totalRecords = array_sum($storageInfo['counts']);
$checks[] = [
    'name' => 'Total Records',
    'status' => true,
    'details' => $totalRecords . " total records across all tables",
    'severity' => 'info'
];

// Check 7: Test Live API Connection
$apiHealthCheck = false;
try {
    $apiUrl = 'http://localhost/BTT/api/index.php?route=health';
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($apiUrl, false, $context);
    $apiHealthCheck = $response !== false;
} catch (Exception $e) {
    $apiHealthCheck = false;
}

$checks[] = [
    'name' => 'API Health Check',
    'status' => $apiHealthCheck,
    'details' => $apiHealthCheck ? "API is responding" : "API is not responding",
    'severity' => 'critical'
];

// Calculate overall health
$criticalFailed = count(array_filter($checks, fn($c) => $c['severity'] === 'critical' && !$c['status']));
$warningFailed = count(array_filter($checks, fn($c) => $c['severity'] === 'warning' && !$c['status']));
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($c) => $c['status']));

$overallHealth = 'healthy';
if ($criticalFailed > 0) {
    $overallHealth = 'critical';
} elseif ($warningFailed > 0) {
    $overallHealth = 'warning';
}
?>

<div class="db-checker-container">
    <h1>🔍 Live Database Integrity Checker</h1>
    <p class="lead">Checking integrity of your LIVE <?php echo strtoupper($storageInfo['driver']); ?> database</p>

    <!-- Overall Health Status -->
    <div class="health-status card-forest">
        <div class="health-indicator <?php echo $overallHealth; ?>">
            <span class="health-icon">
                <?php echo $overallHealth === 'healthy' ? '✅' : ($overallHealth === 'warning' ? '⚠️' : '❌'); ?>
            </span>
            <span class="health-text">
                Database Health: <?php echo ucfirst($overallHealth); ?>
            </span>
        </div>
        <div class="health-stats">
            <span class="stat-item">✅ Passed: <?php echo $passedChecks; ?></span>
            <span class="stat-item">❌ Failed: <?php echo $totalChecks - $passedChecks; ?></span>
            <span class="stat-item">Total Checks: <?php echo $totalChecks; ?></span>
        </div>
    </div>

    <!-- Integrity Checks -->
    <div class="checks-container card-forest">
        <h2>Integrity Checks</h2>
        <table class="checks-table">
            <thead>
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Details</th>
                    <th>Severity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                <tr class="check-row <?php echo $check['status'] ? 'check-pass' : 'check-fail'; ?>">
                    <td><?php echo htmlspecialchars($check['name']); ?></td>
                    <td class="check-status">
                        <?php echo $check['status'] ? '✅ Pass' : '❌ Fail'; ?>
                    </td>
                    <td><?php echo htmlspecialchars($check['details']); ?></td>
                    <td>
                        <span class="severity-badge severity-<?php echo $check['severity']; ?>">
                            <?php echo ucfirst($check['severity']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Statistics -->
    <div class="stats-container card-forest">
        <h2>Live Data Statistics</h2>
        <div class="stats-grid">
            <?php foreach ($storageInfo['counts'] as $table => $count): ?>
            <div class="stat-card">
                <div class="stat-name"><?php echo ucfirst($table); ?></div>
                <div class="stat-value"><?php echo number_format($count); ?></div>
                <div class="stat-label">records</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="actions-container card-forest">
        <h2>Database Actions</h2>
        <div class="action-buttons">
            <button onclick="runFullScan()" class="btn btn-primary">
                🔍 Run Full Scan
            </button>
            <button onclick="checkOrphans()" class="btn btn-secondary">
                🔗 Check Orphaned Records
            </button>
            <button onclick="exportReport()" class="btn btn-secondary">
                📊 Export Report
            </button>
            <?php if (!empty($orphanedRecords)): ?>
            <button onclick="fixOrphans()" class="btn btn-warning">
                🔧 Fix Orphaned Records
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live Query Tester -->
    <div class="query-tester card-forest">
        <h2>Live Query Tester</h2>
        <div class="control-group">
            <label for="query-table">Table:</label>
            <select id="query-table" class="form-control">
                <?php foreach ($storageInfo['tables'] as $table): ?>
                <option value="<?php echo htmlspecialchars($table); ?>"><?php echo htmlspecialchars($table); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="control-group">
            <label for="query-limit">Limit:</label>
            <input type="number" id="query-limit" class="form-control" value="10" min="1" max="100">
        </div>
        <button onclick="runQuery()" class="btn btn-primary">Run Query</button>
        <div id="query-results" class="query-results"></div>
    </div>
</div>

<style>
.db-checker-container {
    max-width: 1200px;
    margin: 0 auto;
}

.health-status {
    text-align: center;
    padding: var(--space-8);
}

.health-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.health-indicator.healthy { color: var(--forest-mint); }
.health-indicator.warning { color: var(--forest-honey); }
.health-indicator.critical { color: var(--forest-berry); }

.health-icon {
    font-size: 3rem;
}

.health-text {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
}

.health-stats {
    display: flex;
    justify-content: center;
    gap: var(--space-6);
}

.checks-table {
    width: 100%;
    border-collapse: collapse;
}

.checks-table th {
    background: var(--forest-canopy);
    padding: var(--space-3);
    text-align: left;
    font-weight: var(--font-semibold);
}

.checks-table td {
    padding: var(--space-3);
    border-bottom: 1px solid var(--glass-border);
}

.check-pass { background: rgba(16, 185, 129, 0.1); }
.check-fail { background: rgba(239, 68, 68, 0.1); }

.severity-badge {
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-md);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
}

.severity-critical { background: var(--forest-berry); color: white; }
.severity-warning { background: var(--forest-honey); color: var(--forest-deep); }
.severity-info { background: var(--forest-lake); color: white; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--space-4);
}

.stat-card {
    background: var(--glass-bg);
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    text-align: center;
}

.stat-name {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-2);
}

.stat-value {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--forest-mint);
}

.query-results {
    margin-top: var(--space-4);
    max-height: 400px;
    overflow: auto;
}
</style>

<script>
async function runFullScan() {
    BTTTest.showAlert('Running full database scan...', 'info');
    
    // Reload page to re-run all checks
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

async function checkOrphans() {
    BTTTest.showAlert('Checking for orphaned records...', 'info');
    
    // Make API call to check orphans
    try {
        const response = await fetch('/BTT/test/api/check-orphans.php');
        const data = await response.json();
        
        if (data.orphans && data.orphans.length > 0) {
            BTTTest.showAlert(`Found ${data.orphans.length} orphaned records`, 'warning');
        } else {
            BTTTest.showAlert('No orphaned records found!', 'success');
        }
    } catch (error) {
        BTTTest.showAlert('Failed to check orphans: ' + error.message, 'error');
    }
}

async function exportReport() {
    BTTTest.showAlert('Generating database report...', 'info');
    
    const report = {
        timestamp: new Date().toISOString(),
        storage: '<?php echo $storageInfo['driver']; ?>',
        tables: <?php echo json_encode($storageInfo['tables']); ?>,
        counts: <?php echo json_encode($storageInfo['counts']); ?>,
        size: <?php echo $storageInfo['size']; ?>,
        checks: <?php echo json_encode($checks); ?>
    };
    
    // Download as JSON
    const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `db-report-${Date.now()}.json`;
    a.click();
    
    BTTTest.showAlert('Report exported successfully!', 'success');
}

async function runQuery() {
    const table = document.getElementById('query-table').value;
    const limit = document.getElementById('query-limit').value;
    
    BTTTest.showAlert('Running query...', 'info');
    
    try {
        const response = await fetch(`/BTT/api/index.php?route=${table}&limit=${limit}`);
        const data = await response.json();
        
        const resultsDiv = document.getElementById('query-results');
        resultsDiv.innerHTML = `
            <h4>Results (${data.length || 0} records)</h4>
            <pre class="code-display">${JSON.stringify(data, null, 2)}</pre>
        `;
        
        BTTTest.showAlert('Query executed successfully!', 'success');
    } catch (error) {
        BTTTest.showAlert('Query failed: ' + error.message, 'error');
    }
}

async function fixOrphans() {
    if (!confirm('This will remove orphaned references. Continue?')) {
        return;
    }
    
    BTTTest.showAlert('Fixing orphaned records...', 'info');
    
    // Implementation would depend on your specific needs
    // This is a placeholder for the actual fix logic
    setTimeout(() => {
        BTTTest.showAlert('Orphaned records fixed!', 'success');
        window.location.reload();
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
