<?php
/**
 * Debug Dashboard for BeyondTrailTales
 * 
 * Real-time monitoring of errors, logs, and API requests
 * ADA Compliant and responsive design
 */

require_once dirname(__DIR__) . '/app/config.php';
require_once BTT_ROOT . '/app/classes/Debug.php';

// Only allow in development mode
if (!BTT_DEBUG) {
    http_response_code(403);
    die('Debug dashboard is only available in development mode');
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_logs':
            echo json_encode(getRecentLogs());
            break;
        case 'get_stats':
            echo json_encode(getDebugStats());
            break;
        case 'clear_logs':
            clearLogs();
            echo json_encode(['success' => true]);
            break;
        case 'export':
            exportDebugData();
            break;
    }
    exit;
}

function getRecentLogs($limit = 100) {
    $logFiles = glob(BTT_LOGS_PATH . '/debug_*.log');
    $logs = [];
    
    if (!empty($logFiles)) {
        $latestLog = end($logFiles);
        $lines = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -$limit);
        
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] \[(.*?)\] (.*?) \| Context: (.*?) \| File: (.*?):(\d+)/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'message' => $matches[3],
                    'context' => json_decode($matches[4], true),
                    'file' => $matches[5],
                    'line' => $matches[6]
                ];
            }
        }
    }
    
    return array_reverse($logs);
}

function getDebugStats() {
    $stats = [
        'total_logs' => 0,
        'errors' => 0,
        'warnings' => 0,
        'info' => 0,
        'debug' => 0,
        'log_size' => 0,
        'oldest_log' => null,
        'newest_log' => null
    ];
    
    $logFiles = glob(BTT_LOGS_PATH . '/*.log');
    
    foreach ($logFiles as $file) {
        $stats['log_size'] += filesize($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats['total_logs'] += count($lines);
        
        foreach ($lines as $line) {
            if (strpos($line, '[ERROR]') !== false) $stats['errors']++;
            elseif (strpos($line, '[WARNING]') !== false) $stats['warnings']++;
            elseif (strpos($line, '[INFO]') !== false) $stats['info']++;
            elseif (strpos($line, '[DEBUG]') !== false) $stats['debug']++;
        }
    }
    
    if (!empty($logFiles)) {
        $stats['oldest_log'] = date('Y-m-d H:i:s', filemtime(reset($logFiles)));
        $stats['newest_log'] = date('Y-m-d H:i:s', filemtime(end($logFiles)));
    }
    
    $stats['log_size_formatted'] = formatBytes($stats['log_size']);
    
    return $stats;
}

function clearLogs() {
    $logFiles = glob(BTT_LOGS_PATH . '/*.log');
    foreach ($logFiles as $file) {
        unlink($file);
    }
}

function exportDebugData() {
    $logs = getRecentLogs(1000);
    $stats = getDebugStats();
    
    $export = [
        'exported_at' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'logs' => $logs
    ];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="btt-debug-export-' . date('Y-m-d-His') . '.json"');
    echo json_encode($export, JSON_PRETTY_PRINT);
    exit;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard - BeyondTrailTales</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }
        
        .dashboard-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .stat-error { color: #e74c3c; }
        .stat-warning { color: #f39c12; }
        .stat-info { color: #3498db; }
        .stat-debug { color: #95a5a6; }
        
        .dashboard-content {
            padding: 2rem;
        }
        
        .log-filters {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .filter-label {
            font-weight: 500;
        }
        
        .filter-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .log-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .log-header {
            background: #34495e;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .log-entry {
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.2s;
        }
        
        .log-entry:hover {
            background: #f8f9fa;
        }
        
        .log-entry.error {
            border-left: 3px solid #e74c3c;
            background: #ffe5e5;
        }
        
        .log-entry.warning {
            border-left: 3px solid #f39c12;
            background: #fff5e5;
        }
        
        .log-entry.info {
            border-left: 3px solid #3498db;
            background: #e5f5ff;
        }
        
        .log-entry.debug {
            border-left: 3px solid #95a5a6;
        }
        
        .log-timestamp {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }
        
        .log-message {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .log-location {
            font-size: 0.85rem;
            color: #7f8c8d;
            font-family: monospace;
        }
        
        .log-context {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .log-filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Accessibility */
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
        
        button:focus,
        input:focus,
        a:focus {
            outline: 2px solid #3498db;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1 class="dashboard-title">🐛 Debug Dashboard</h1>
        <div class="dashboard-actions">
            <div class="auto-refresh">
                <input type="checkbox" id="auto-refresh" class="filter-checkbox" checked>
                <label for="auto-refresh">Auto-refresh (5s)</label>
            </div>
            <button class="btn btn-primary" onclick="refreshData()">
                <span aria-hidden="true">🔄</span> Refresh
            </button>
            <button class="btn btn-success" onclick="exportData()">
                <span aria-hidden="true">💾</span> Export
            </button>
            <button class="btn btn-danger" onclick="clearLogs()">
                <span aria-hidden="true">🗑️</span> Clear Logs
            </button>
            <a href="<?php echo BTT_PUBLIC_URL; ?>" class="btn btn-primary">
                <span aria-hidden="true">🏠</span> Back to App
            </a>
        </div>
    </div>
    
    <div class="dashboard-stats" id="stats-container">
        <div class="stat-card">
            <div class="stat-value" id="stat-total">0</div>
            <div class="stat-label">Total Logs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value stat-error" id="stat-errors">0</div>
            <div class="stat-label">Errors</div>
        </div>
        <div class="stat-card">
            <div class="stat-value stat-warning" id="stat-warnings">0</div>
            <div class="stat-label">Warnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value stat-info" id="stat-info">0</div>
            <div class="stat-label">Info</div>
        </div>
        <div class="stat-card">
            <div class="stat-value stat-debug" id="stat-debug">0</div>
            <div class="stat-label">Debug</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="stat-size">0 KB</div>
            <div class="stat-label">Log Size</div>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="log-filters">
            <div class="filter-group">
                <span class="filter-label">Show:</span>
            </div>
            <div class="filter-group">
                <input type="checkbox" id="filter-error" class="filter-checkbox" checked>
                <label for="filter-error">Errors</label>
            </div>
            <div class="filter-group">
                <input type="checkbox" id="filter-warning" class="filter-checkbox" checked>
                <label for="filter-warning">Warnings</label>
            </div>
            <div class="filter-group">
                <input type="checkbox" id="filter-info" class="filter-checkbox" checked>
                <label for="filter-info">Info</label>
            </div>
            <div class="filter-group">
                <input type="checkbox" id="filter-debug" class="filter-checkbox" checked>
                <label for="filter-debug">Debug</label>
            </div>
            <input type="search" class="search-box" id="search-logs" placeholder="Search logs..." aria-label="Search logs">
        </div>
        
        <div class="log-container">
            <div class="log-header">
                <h2>Recent Logs</h2>
                <span id="log-count">0 entries</span>
            </div>
            <div class="log-list" id="log-list">
                <div class="empty-state">
                    <p>No logs to display</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let autoRefreshInterval;
        let allLogs = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            refreshData();
            setupEventListeners();
            setupAutoRefresh();
        });
        
        function setupEventListeners() {
            // Filter checkboxes
            document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
                if (checkbox.id !== 'auto-refresh') {
                    checkbox.addEventListener('change', filterLogs);
                }
            });
            
            // Search box
            document.getElementById('search-logs').addEventListener('input', filterLogs);
            
            // Auto-refresh toggle
            document.getElementById('auto-refresh').addEventListener('change', function() {
                if (this.checked) {
                    setupAutoRefresh();
                } else {
                    clearInterval(autoRefreshInterval);
                }
            });
        }
        
        function setupAutoRefresh() {
            if (document.getElementById('auto-refresh').checked) {
                autoRefreshInterval = setInterval(refreshData, 5000);
            }
        }
        
        async function refreshData() {
            try {
                // Show loading state
                const logList = document.getElementById('log-list');
                if (allLogs.length === 0) {
                    logList.innerHTML = '<div class="empty-state"><div class="loading"></div><p>Loading logs...</p></div>';
                }
                
                // Fetch logs and stats
                const [logsResponse, statsResponse] = await Promise.all([
                    fetch('?action=get_logs'),
                    fetch('?action=get_stats')
                ]);
                
                const logs = await logsResponse.json();
                const stats = await statsResponse.json();
                
                // Update stats
                updateStats(stats);
                
                // Update logs
                allLogs = logs;
                displayLogs(logs);
                
            } catch (error) {
                console.error('Error refreshing data:', error);
                document.getElementById('log-list').innerHTML = 
                    '<div class="empty-state"><p>Error loading logs</p></div>';
            }
        }
        
        function updateStats(stats) {
            document.getElementById('stat-total').textContent = stats.total_logs || 0;
            document.getElementById('stat-errors').textContent = stats.errors || 0;
            document.getElementById('stat-warnings').textContent = stats.warnings || 0;
            document.getElementById('stat-info').textContent = stats.info || 0;
            document.getElementById('stat-debug').textContent = stats.debug || 0;
            document.getElementById('stat-size').textContent = stats.log_size_formatted || '0 B';
        }
        
        function displayLogs(logs) {
            const logList = document.getElementById('log-list');
            const logCount = document.getElementById('log-count');
            
            if (!logs || logs.length === 0) {
                logList.innerHTML = '<div class="empty-state"><p>No logs to display</p></div>';
                logCount.textContent = '0 entries';
                return;
            }
            
            logCount.textContent = `${logs.length} entries`;
            
            let html = '';
            logs.forEach(log => {
                const levelClass = log.level.toLowerCase();
                const contextStr = log.context ? JSON.stringify(log.context, null, 2) : '';
                
                html += `
                    <div class="log-entry ${levelClass}" data-level="${log.level}">
                        <div class="log-timestamp">${log.timestamp}</div>
                        <div class="log-message">[${log.level}] ${escapeHtml(log.message)}</div>
                        <div class="log-location">${escapeHtml(log.file)}:${log.line}</div>
                        ${contextStr ? `<div class="log-context">${escapeHtml(contextStr)}</div>` : ''}
                    </div>
                `;
            });
            
            logList.innerHTML = html;
        }
        
        function filterLogs() {
            const showError = document.getElementById('filter-error').checked;
            const showWarning = document.getElementById('filter-warning').checked;
            const showInfo = document.getElementById('filter-info').checked;
            const showDebug = document.getElementById('filter-debug').checked;
            const searchTerm = document.getElementById('search-logs').value.toLowerCase();
            
            const filteredLogs = allLogs.filter(log => {
                // Level filter
                const level = log.level.toUpperCase();
                if (level === 'ERROR' && !showError) return false;
                if (level === 'WARNING' && !showWarning) return false;
                if (level === 'INFO' && !showInfo) return false;
                if (level === 'DEBUG' && !showDebug) return false;
                
                // Search filter
                if (searchTerm) {
                    const searchableText = `${log.message} ${log.file} ${JSON.stringify(log.context)}`.toLowerCase();
                    if (!searchableText.includes(searchTerm)) return false;
                }
                
                return true;
            });
            
            displayLogs(filteredLogs);
        }
        
        async function clearLogs() {
            if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch('?action=clear_logs');
                const result = await response.json();
                
                if (result.success) {
                    allLogs = [];
                    displayLogs([]);
                    updateStats({
                        total_logs: 0,
                        errors: 0,
                        warnings: 0,
                        info: 0,
                        debug: 0,
                        log_size_formatted: '0 B'
                    });
                    alert('Logs cleared successfully');
                }
            } catch (error) {
                console.error('Error clearing logs:', error);
                alert('Failed to clear logs');
            }
        }
        
        function exportData() {
            window.location.href = '?action=export';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
