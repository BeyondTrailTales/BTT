<?php
/**
 * Enhanced Debug Class for BeyondTrailTales
 * 
 * Provides comprehensive debugging, logging, and error tracking capabilities
 * Following Context7 best practices and ADA compliance
 */

class BTTDebug {
    
    private static $instance = null;
    private $startTime;
    private $queryLog = [];
    private $performanceLog = [];
    private $errorLog = [];
    private $requestLog = [];
    private $enabled;
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->startTime = microtime(true);
        $this->enabled = BTT_DEBUG;
        
        // Set custom error handler
        if ($this->enabled) {
            set_error_handler([$this, 'errorHandler']);
            set_exception_handler([$this, 'exceptionHandler']);
            register_shutdown_function([$this, 'shutdownHandler']);
        }
    }
    
    /**
     * Log a debug message with context
     */
    public function log($message, $context = [], $level = 'DEBUG') {
        if (!$this->enabled && $level !== 'ERROR') {
            return;
        }
        
        $timestamp = microtime(true);
        $memory = memory_get_usage(true);
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory' => $this->formatBytes($memory),
            'time_elapsed' => round($timestamp - $this->startTime, 4),
            'file' => $backtrace[0]['file'] ?? 'unknown',
            'line' => $backtrace[0]['line'] ?? 0,
            'function' => $backtrace[1]['function'] ?? 'unknown',
            'class' => $backtrace[1]['class'] ?? null
        ];
        
        // Write to file
        $this->writeToFile($logEntry);
        
        // Store in memory for dashboard
        if ($level === 'ERROR') {
            $this->errorLog[] = $logEntry;
        }
        
        // Send to browser console if in debug mode
        if ($this->enabled && !headers_sent()) {
            $this->sendToConsole($logEntry);
        }
        
        return $logEntry;
    }
    
    /**
     * Log SQL query with execution time
     */
    public function logQuery($query, $params = [], $executionTime = null, $success = true) {
        if (!$this->enabled) {
            return;
        }
        
        $entry = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime,
            'success' => $success,
            'timestamp' => microtime(true),
            'backtrace' => $this->getSimpleBacktrace()
        ];
        
        $this->queryLog[] = $entry;
        
        if (!$success) {
            $this->log('SQL Query Failed', $entry, 'ERROR');
        }
        
        return $entry;
    }
    
    /**
     * Log API request/response
     */
    public function logRequest($method, $endpoint, $data = [], $response = null, $statusCode = null) {
        if (!$this->enabled) {
            return;
        }
        
        $entry = [
            'method' => $method,
            'endpoint' => $endpoint,
            'request_data' => $data,
            'response' => $response,
            'status_code' => $statusCode,
            'timestamp' => microtime(true),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->requestLog[] = $entry;
        
        // Log errors
        if ($statusCode >= 400) {
            $this->log("API Error: $method $endpoint", $entry, 'ERROR');
        }
        
        return $entry;
    }
    
    /**
     * Start performance timer
     */
    public function startTimer($label) {
        if (!$this->enabled) {
            return;
        }
        
        $this->performanceLog[$label] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];
    }
    
    /**
     * Stop performance timer
     */
    public function stopTimer($label) {
        if (!$this->enabled || !isset($this->performanceLog[$label])) {
            return;
        }
        
        $end = microtime(true);
        $memoryEnd = memory_get_usage(true);
        
        $this->performanceLog[$label]['end'] = $end;
        $this->performanceLog[$label]['memory_end'] = $memoryEnd;
        $this->performanceLog[$label]['duration'] = round($end - $this->performanceLog[$label]['start'], 4);
        $this->performanceLog[$label]['memory_used'] = $this->formatBytes($memoryEnd - $this->performanceLog[$label]['memory_start']);
        
        return $this->performanceLog[$label];
    }
    
    /**
     * Custom error handler
     */
    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        $type = $errorTypes[$errno] ?? 'UNKNOWN';
        
        $this->log("PHP $type: $errstr", [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ], 'ERROR');
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Custom exception handler
     */
    public function exceptionHandler($exception) {
        $this->log('Uncaught Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ], 'ERROR');
    }
    
    /**
     * Shutdown handler for fatal errors
     */
    public function shutdownHandler() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->log('Fatal Error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ], 'FATAL');
        }
        
        // Save performance summary
        if ($this->enabled) {
            $this->savePerformanceSummary();
        }
    }
    
    /**
     * Send debug info to browser console
     */
    private function sendToConsole($data) {
        if (headers_sent()) {
            return;
        }
        
        $json = json_encode($data);
        echo "<script>console.log('[BTT Debug]', $json);</script>\n";
    }
    
    /**
     * Write log entry to file
     */
    private function writeToFile($entry) {
        $logFile = BTT_LOGS_PATH . '/debug_' . date('Y-m-d') . '.log';
        $logLine = sprintf(
            "[%s] [%s] %s | Context: %s | File: %s:%d\n",
            date('Y-m-d H:i:s', (int)$entry['timestamp']),
            $entry['level'],
            $entry['message'],
            json_encode($entry['context']),
            $entry['file'],
            $entry['line']
        );
        
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get simplified backtrace
     */
    private function getSimpleBacktrace($limit = 5) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
        $simple = [];
        
        foreach ($backtrace as $frame) {
            if (isset($frame['file']) && strpos($frame['file'], __FILE__) === false) {
                $simple[] = [
                    'file' => str_replace(BTT_ROOT, '', $frame['file']),
                    'line' => $frame['line'] ?? 0,
                    'function' => $frame['function'] ?? null,
                    'class' => $frame['class'] ?? null
                ];
            }
        }
        
        return $simple;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Save performance summary
     */
    private function savePerformanceSummary() {
        $summary = [
            'total_time' => round(microtime(true) - $this->startTime, 4),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'queries_executed' => count($this->queryLog),
            'total_query_time' => array_sum(array_column($this->queryLog, 'execution_time')),
            'requests_made' => count($this->requestLog),
            'errors_logged' => count($this->errorLog),
            'timers' => $this->performanceLog
        ];
        
        $this->log('Performance Summary', $summary, 'INFO');
    }
    
    /**
     * Get debug toolbar HTML
     */
    public function getToolbarHtml() {
        if (!$this->enabled) {
            return '';
        }
        
        $totalTime = round(microtime(true) - $this->startTime, 4);
        $memory = $this->formatBytes(memory_get_usage(true));
        $peakMemory = $this->formatBytes(memory_get_peak_usage(true));
        $queryCount = count($this->queryLog);
        $errorCount = count($this->errorLog);
        
        ob_start();
        ?>
        <div id="btt-debug-toolbar" style="position: fixed; bottom: 0; left: 0; right: 0; background: #2c3e50; color: white; padding: 10px; font-family: monospace; font-size: 12px; z-index: 9999; display: flex; align-items: center; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <span style="background: #e74c3c; padding: 2px 8px; border-radius: 3px;">DEBUG</span>
            </div>
            <div>⏱️ Time: <strong><?php echo $totalTime; ?>s</strong></div>
            <div>💾 Memory: <strong><?php echo $memory; ?></strong> (Peak: <?php echo $peakMemory; ?>)</div>
            <div>📊 Queries: <strong><?php echo $queryCount; ?></strong></div>
            <?php if ($errorCount > 0): ?>
            <div style="background: #e74c3c; padding: 2px 8px; border-radius: 3px;">
                ⚠️ Errors: <strong><?php echo $errorCount; ?></strong>
            </div>
            <?php endif; ?>
            <div style="margin-left: auto;">
                <button onclick="document.getElementById('btt-debug-panel').style.display='block'" style="background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                    📋 Show Details
                </button>
                <button onclick="this.parentElement.parentElement.style.display='none'" style="background: #95a5a6; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-left: 5px;">
                    ✕ Hide
                </button>
            </div>
        </div>
        
        <!-- Debug Panel -->
        <div id="btt-debug-panel" style="display: none; position: fixed; top: 10%; left: 10%; right: 10%; bottom: 10%; background: white; border: 2px solid #2c3e50; border-radius: 5px; z-index: 10000; overflow: auto; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #2c3e50;">Debug Information</h2>
                <button onclick="this.parentElement.parentElement.style.display='none'" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">✕ Close</button>
            </div>
            
            <!-- Tabs -->
            <div style="border-bottom: 2px solid #ecf0f1; margin-bottom: 20px;">
                <button class="debug-tab" onclick="showDebugTab('queries')" style="padding: 10px 20px; border: none; background: none; cursor: pointer;">Queries</button>
                <button class="debug-tab" onclick="showDebugTab('errors')" style="padding: 10px 20px; border: none; background: none; cursor: pointer;">Errors</button>
                <button class="debug-tab" onclick="showDebugTab('performance')" style="padding: 10px 20px; border: none; background: none; cursor: pointer;">Performance</button>
                <button class="debug-tab" onclick="showDebugTab('requests')" style="padding: 10px 20px; border: none; background: none; cursor: pointer;">Requests</button>
            </div>
            
            <!-- Tab Content -->
            <div id="debug-tab-queries" class="debug-tab-content">
                <h3>SQL Queries (<?php echo $queryCount; ?>)</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($this->queryLog as $query): ?>
                    <div style="margin-bottom: 15px; padding: 10px; background: #ecf0f1; border-radius: 3px;">
                        <code style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($query['query']); ?></code>
                        <small style="color: #7f8c8d;">
                            Time: <?php echo $query['execution_time'] ?? 'N/A'; ?>ms
                            <?php if (!empty($query['params'])): ?>
                            | Params: <?php echo htmlspecialchars(json_encode($query['params'])); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="debug-tab-errors" class="debug-tab-content" style="display: none;">
                <h3>Errors (<?php echo $errorCount; ?>)</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($this->errorLog as $error): ?>
                    <div style="margin-bottom: 15px; padding: 10px; background: #ffe5e5; border-left: 3px solid #e74c3c;">
                        <strong><?php echo htmlspecialchars($error['message']); ?></strong>
                        <div style="margin-top: 5px; font-size: 11px; color: #7f8c8d;">
                            <?php echo htmlspecialchars($error['file']); ?>:<?php echo $error['line']; ?>
                        </div>
                        <?php if (!empty($error['context'])): ?>
                        <details style="margin-top: 5px;">
                            <summary style="cursor: pointer;">Context</summary>
                            <pre style="font-size: 11px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($error['context'], JSON_PRETTY_PRINT)); ?></pre>
                        </details>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="debug-tab-performance" class="debug-tab-content" style="display: none;">
                <h3>Performance Timers</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($this->performanceLog as $label => $timer): ?>
                    <?php if (isset($timer['duration'])): ?>
                    <div style="margin-bottom: 10px; padding: 10px; background: #ecf0f1; border-radius: 3px;">
                        <strong><?php echo htmlspecialchars($label); ?></strong>
                        <div style="margin-top: 5px; font-size: 12px;">
                            Duration: <?php echo $timer['duration']; ?>s | 
                            Memory: <?php echo $timer['memory_used']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="debug-tab-requests" class="debug-tab-content" style="display: none;">
                <h3>API Requests (<?php echo count($this->requestLog); ?>)</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($this->requestLog as $request): ?>
                    <div style="margin-bottom: 15px; padding: 10px; background: #ecf0f1; border-radius: 3px;">
                        <strong><?php echo $request['method']; ?> <?php echo htmlspecialchars($request['endpoint']); ?></strong>
                        <div style="margin-top: 5px; font-size: 12px;">
                            Status: <span style="color: <?php echo $request['status_code'] < 400 ? '#27ae60' : '#e74c3c'; ?>"><?php echo $request['status_code'] ?? 'N/A'; ?></span>
                        </div>
                        <?php if (!empty($request['request_data'])): ?>
                        <details style="margin-top: 5px;">
                            <summary style="cursor: pointer;">Request Data</summary>
                            <pre style="font-size: 11px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($request['request_data'], JSON_PRETTY_PRINT)); ?></pre>
                        </details>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <script>
        function showDebugTab(tab) {
            document.querySelectorAll('.debug-tab-content').forEach(el => el.style.display = 'none');
            document.getElementById('debug-tab-' + tab).style.display = 'block';
            document.querySelectorAll('.debug-tab').forEach(el => el.style.background = 'none');
            event.target.style.background = '#3498db';
            event.target.style.color = 'white';
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Export debug data as JSON
     */
    public function exportDebugData() {
        return [
            'summary' => [
                'total_time' => round(microtime(true) - $this->startTime, 4),
                'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
                'current_memory' => $this->formatBytes(memory_get_usage(true))
            ],
            'queries' => $this->queryLog,
            'errors' => $this->errorLog,
            'requests' => $this->requestLog,
            'performance' => $this->performanceLog
        ];
    }
}

// Initialize debug instance
$BTT_DEBUG = BTTDebug::getInstance();

// Global helper functions
function btt_debug($message, $context = []) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->log($message, $context, 'DEBUG');
}

function btt_error($message, $context = []) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->log($message, $context, 'ERROR');
}

function btt_timer_start($label) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->startTimer($label);
}

function btt_timer_stop($label) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->stopTimer($label);
}

function btt_log_query($query, $params = [], $time = null, $success = true) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->logQuery($query, $params, $time, $success);
}

function btt_log_request($method, $endpoint, $data = [], $response = null, $statusCode = null) {
    global $BTT_DEBUG;
    return $BTT_DEBUG->logRequest($method, $endpoint, $data, $response, $statusCode);
}
