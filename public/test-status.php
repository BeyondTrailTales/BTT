<?php
require_once dirname(__DIR__) . '/app/config.php';

// Handle POST actions
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'seed') {
        // Run seed script
        $output = [];
        $returnCode = 0;
        exec('php ' . escapeshellarg(dirname(__DIR__) . '/scripts/seed_test_data.php') . ' 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            $message = 'Test data seeded successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to seed test data: ' . implode(' ', $output);
            $messageType = 'error';
        }
    } elseif ($action === 'clear_db') {
        // Clear database
        $dbPath = BTT_ROOT . '/storage/sqlite/btt.db';
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->exec('PRAGMA foreign_keys = OFF');
            $pdo->exec('DELETE FROM trip_gear');
            $pdo->exec('DELETE FROM backpack_gear');
            $pdo->exec('DELETE FROM trips');
            $pdo->exec('DELETE FROM backpacks');
            $pdo->exec('DELETE FROM gear_items');
            $pdo->exec('PRAGMA foreign_keys = ON');
            $message = 'Database cleared successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Failed to clear database: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTT System Status</title>
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-theme.css">
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0d3b2e 0%, #1a5f4a 100%);
            color: #e8f5e9;
            padding: 2rem;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .status-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .status-card h3 {
            margin-top: 0;
            color: #81c784;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-ok {
            color: #81c784;
            font-weight: bold;
        }
        .status-error {
            color: #ef5350;
            font-weight: bold;
        }
        .status-warning {
            color: #ffa726;
            font-weight: bold;
        }
        h1 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .subtitle {
            text-align: center;
            opacity: 0.8;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 0.5rem;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            background: #66bb6a;
        }
        .actions {
            text-align: center;
            margin-top: 2rem;
        }
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: bold;
        }
        .message-success {
            background: rgba(129, 199, 132, 0.2);
            border: 1px solid #81c784;
            color: #81c784;
        }
        .message-error {
            background: rgba(239, 83, 80, 0.2);
            border: 1px solid #ef5350;
            color: #ef5350;
        }
        .action-btn {
            background: none;
            border: 2px solid;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-1px);
        }
        .action-btn-green {
            border-color: #81c784;
            color: #81c784;
        }
        .action-btn-green:hover {
            background: rgba(129, 199, 132, 0.1);
        }
        .action-btn-red {
            border-color: #ef5350;
            color: #ef5350;
        }
        .action-btn-red:hover {
            background: rgba(239, 83, 80, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌲 BeyondTrailTales Status</h1>
        <p class="subtitle">System Health Check & Testing Dashboard</p>
        
        <?php if ($message): ?>
        <div class="message message-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="status-grid">
            <!-- Environment Status -->
            <div class="status-card">
                <h3>⚙️ Environment</h3>
                <div class="status-item">
                    <span>PHP Version</span>
                    <span class="status-ok"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="status-item">
                    <span>Storage Engine</span>
                    <span class="<?php echo STORAGE_ENGINE === 'sqlite' ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo STORAGE_ENGINE; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>PDO SQLite</span>
                    <span class="<?php echo extension_loaded('pdo_sqlite') ? 'status-ok' : 'status-error'; ?>">
                        <?php echo extension_loaded('pdo_sqlite') ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Database Status -->
            <div class="status-card">
                <h3>🗄️ Database</h3>
                <?php
                try {
                    $dbPath = BTT_ROOT . '/storage/sqlite/btt.db';
                    $dbExists = file_exists($dbPath);
                    ?>
                    <div class="status-item">
                        <span>SQLite File</span>
                        <span class="<?php echo $dbExists ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $dbExists ? 'Exists' : 'Missing'; ?>
                        </span>
                    </div>
                    <?php
                    if ($dbExists) {
                        $pdo = new PDO('sqlite:' . $dbPath);
                        $result = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                        $tableCount = $result->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        // Get record counts
                        $backpackCount = $pdo->query("SELECT COUNT(*) FROM backpacks")->fetchColumn();
                        $tripCount = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
                        $gearCount = $pdo->query("SELECT COUNT(*) FROM gear_items")->fetchColumn();
                        ?>
                        <div class="status-item">
                            <span>Tables</span>
                            <span class="status-ok"><?php echo $tableCount; ?> tables</span>
                        </div>
                        <div class="status-item">
                            <span>Backpacks</span>
                            <span><?php echo $backpackCount; ?> records</span>
                        </div>
                        <div class="status-item">
                            <span>Trips</span>
                            <span><?php echo $tripCount; ?> records</span>
                        </div>
                        <div class="status-item">
                            <span>Gear Items</span>
                            <span><?php echo $gearCount; ?> records</span>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {
                    echo '<div class="status-item"><span>Error</span><span class="status-error">' . $e->getMessage() . '</span></div>';
                }
                ?>
            </div>
            
            <!-- API Status -->
            <div class="status-card">
                <h3>🔌 API Status</h3>
                <div id="api-status">
                    <div class="status-item">
                        <span>Checking...</span>
                        <span class="status-warning">...</span>
                    </div>
                </div>
            </div>
            
            <!-- File Permissions -->
            <div class="status-card">
                <h3>📁 Permissions</h3>
                <div class="status-item">
                    <span>Storage</span>
                    <span class="<?php echo is_writable(BTT_STORAGE_PATH) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo is_writable(BTT_STORAGE_PATH) ? 'Writable' : 'Not Writable'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>Logs</span>
                    <span class="<?php echo is_writable(BTT_LOGS_PATH) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo is_writable(BTT_LOGS_PATH) ? 'Writable' : 'Not Writable'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>Uploads</span>
                    <span class="<?php echo is_writable(BTT_UPLOAD_PATH) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo is_writable(BTT_UPLOAD_PATH) ? 'Writable' : 'Not Writable'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Pages Status -->
            <div class="status-card">
                <h3>📄 Pages</h3>
                <div class="status-item">
                    <span>Dashboard</span>
                    <a href="index.php" class="status-ok">Test →</a>
                </div>
                <div class="status-item">
                    <span>Backpacks</span>
                    <a href="backpacks.php" class="status-ok">Test →</a>
                </div>
                <div class="status-item">
                    <span>Trips</span>
                    <a href="trips.php" class="status-ok">Test →</a>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="status-card">
                <h3>🚀 Quick Actions</h3>
                <div class="status-item">
                    <span>Seed Test Data</span>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="action" value="seed" class="action-btn action-btn-green">Run</button>
                    </form>
                </div>
                <div class="status-item">
                    <span>Clear Database</span>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('This will delete all data. Are you sure?');">
                        <button type="submit" name="action" value="clear_db" class="action-btn action-btn-red">Clear</button>
                    </form>
                </div>
                <div class="status-item">
                    <span>Test API</span>
                    <button onclick="testAPI()" class="action-btn action-btn-green">Test</button>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn">🏠 Dashboard</a>
            <a href="backpacks.php" class="btn">🎒 Backpacks</a>
            <a href="trips.php" class="btn">🏔️ Trips</a>
        </div>
    </div>
    
    <script>
        // Check API status
        fetch('<?php echo BTT_API_URL; ?>?route=health')
            .then(r => r.json())
            .then(data => {
                const statusHtml = `
                    <div class="status-item">
                        <span>Health Check</span>
                        <span class="${data.success ? 'status-ok' : 'status-error'}">${data.success ? 'OK' : 'Failed'}</span>
                    </div>
                    <div class="status-item">
                        <span>Version</span>
                        <span>${data.data?.version || 'Unknown'}</span>
                    </div>
                    <div class="status-item">
                        <span>Storage</span>
                        <span class="${data.data?.storage_engine === 'sqlite' ? 'status-ok' : 'status-warning'}">
                            ${data.data?.storage_engine || 'Unknown'}
                        </span>
                    </div>
                `;
                document.getElementById('api-status').innerHTML = statusHtml;
            })
            .catch(err => {
                document.getElementById('api-status').innerHTML = `
                    <div class="status-item">
                        <span>API Error</span>
                        <span class="status-error">Offline</span>
                    </div>
                `;
            });
        
        function testAPI() {
            fetch('<?php echo BTT_API_URL; ?>?route=backpacks')
                .then(r => r.json())
                .then(data => {
                    alert('API Test Success!\n\n' + JSON.stringify(data, null, 2));
                })
                .catch(err => {
                    alert('API Test Failed!\n\n' + err);
                });
        }
        
    </script>
</body>
</html>
