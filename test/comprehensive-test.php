<?php
/**
 * Comprehensive Test Suite Runner
 * Tests all pages in the test directory and reports issues
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('TEST_BASE_PATH', __DIR__);
define('TEST_BASE_URL', 'http://localhost/BTT/test');

// Color codes for terminal output (if running from CLI)
$isCLI = php_sapi_name() === 'cli';
$colors = [
    'red' => $isCLI ? "\033[0;31m" : '<span style="color: red;">',
    'green' => $isCLI ? "\033[0;32m" : '<span style="color: green;">',
    'yellow' => $isCLI ? "\033[0;33m" : '<span style="color: orange;">',
    'blue' => $isCLI ? "\033[0;34m" : '<span style="color: blue;">',
    'reset' => $isCLI ? "\033[0m" : '</span>',
    'bold' => $isCLI ? "\033[1m" : '<strong>',
    'endbold' => $isCLI ? "\033[0m" : '</strong>'
];

// HTML header if not CLI
if (!$isCLI) {
    echo '<!DOCTYPE html><html><head><title>BTT Comprehensive Test Report</title>';
    echo '<style>
        body { font-family: monospace; background: #1a1a1a; color: #f0f0f0; padding: 20px; }
        .test-header { background: #2a2a2a; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .test-section { background: #2a2a2a; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        .info { color: #60a5fa; }
        pre { margin: 5px 0; }
    </style></head><body>';
    echo '<div class="test-header"><h1>🧪 BTT Comprehensive Test Report</h1>';
    echo '<p>Generated: ' . date('Y-m-d H:i:s') . '</p></div>';
}

// Test results storage
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'errors' => []
];

function output($message, $type = 'info') {
    global $colors, $isCLI;
    
    $prefix = '';
    $suffix = '';
    
    switch($type) {
        case 'success':
            $prefix = $colors['green'] . '✓ ';
            break;
        case 'error':
            $prefix = $colors['red'] . '✗ ';
            break;
        case 'warning':
            $prefix = $colors['yellow'] . '⚠ ';
            break;
        case 'info':
            $prefix = $colors['blue'] . 'ℹ ';
            break;
        case 'header':
            $prefix = $colors['bold'];
            $suffix = $colors['endbold'];
            break;
    }
    
    $output = $prefix . $message . $suffix . $colors['reset'];
    
    if ($isCLI) {
        echo $output . PHP_EOL;
    } else {
        echo '<pre class="' . $type . '">' . $output . '</pre>';
    }
}

function testPHPSyntax($file) {
    $output = [];
    $returnCode = 0;
    exec("php -l \"$file\" 2>&1", $output, $returnCode);
    
    return [
        'valid' => $returnCode === 0,
        'output' => implode("\n", $output)
    ];
}

function testFileAccess($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $headers = isset($http_response_header) ? $http_response_header : [];
    
    $statusCode = 0;
    foreach ($headers as $header) {
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
            $statusCode = (int)$matches[1];
            break;
        }
    }
    
    return [
        'accessible' => $statusCode === 200,
        'statusCode' => $statusCode,
        'hasContent' => !empty($response),
        'errors' => []
    ];
}

function checkForPHPErrors($content) {
    $errors = [];
    
    // Check for common PHP error patterns
    $errorPatterns = [
        '/Fatal error:/' => 'Fatal Error',
        '/Parse error:/' => 'Parse Error',
        '/Warning:/' => 'Warning',
        '/Notice:/' => 'Notice',
        '/Deprecated:/' => 'Deprecated',
        '/Undefined variable:/' => 'Undefined Variable',
        '/Undefined index:/' => 'Undefined Index',
        '/Call to undefined function/' => 'Undefined Function'
    ];
    
    foreach ($errorPatterns as $pattern => $type) {
        if (preg_match_all($pattern . '(.+)/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $errors[] = $type . ': ' . $match;
            }
        }
    }
    
    return $errors;
}

// Start testing
output("\n=====================================", 'header');
output("BTT TEST SUITE COMPREHENSIVE CHECK", 'header');
output("=====================================\n", 'header');

// Test 1: Check all PHP files for syntax errors
output("\n📝 CHECKING PHP FILE SYNTAX", 'header');
output("----------------------------", 'info');

$phpFiles = glob(TEST_BASE_PATH . '/*.php');
foreach ($phpFiles as $file) {
    $filename = basename($file);
    $result = testPHPSyntax($file);
    
    if ($result['valid']) {
        output("$filename - Syntax OK", 'success');
        $testResults['passed']++;
    } else {
        output("$filename - Syntax Error!", 'error');
        output("  " . $result['output'], 'error');
        $testResults['failed']++;
        $testResults['errors'][] = "Syntax error in $filename";
    }
}

// Test 2: Check file accessibility via HTTP
output("\n🌐 CHECKING HTTP ACCESSIBILITY", 'header');
output("--------------------------------", 'info');

$testPages = [
    'index.php' => 'Test Dashboard',
    'api-tester.php' => 'API Tester',
    'db-checker.php' => 'Database Checker',
    'gamification-test.php' => 'Gamification Test',
    'performance.php' => 'Performance Test',
    'visual-test.php' => 'Visual Test',
    'smart-packing-demo.php' => 'Smart Packing Demo',
    'test.php' => 'General Test',
    'debug-dashboard.php' => 'Debug Dashboard'
];

foreach ($testPages as $page => $name) {
    $url = TEST_BASE_URL . '/' . $page;
    $result = testFileAccess($url);
    
    if ($result['accessible']) {
        output("$name ($page) - HTTP 200 OK", 'success');
        $testResults['passed']++;
    } else {
        output("$name ($page) - HTTP {$result['statusCode']}", 'error');
        $testResults['failed']++;
        $testResults['errors'][] = "$name returned HTTP {$result['statusCode']}";
    }
}

// Test 3: Check for required directories
output("\n📁 CHECKING REQUIRED DIRECTORIES", 'header');
output("---------------------------------", 'info');

$requiredDirs = [
    'includes' => 'Include files directory',
    'assets' => 'Test assets directory',
    'assets/css' => 'Test CSS directory',
    'tools' => 'Test tools directory',
    'gamification' => 'Gamification tests directory'
];

foreach ($requiredDirs as $dir => $description) {
    $path = TEST_BASE_PATH . '/' . $dir;
    if (is_dir($path)) {
        output("$description ($dir/) - Exists", 'success');
        $testResults['passed']++;
    } else {
        output("$description ($dir/) - Missing!", 'error');
        $testResults['failed']++;
        $testResults['errors'][] = "Missing directory: $dir";
    }
}

// Test 4: Check for required files
output("\n📄 CHECKING REQUIRED FILES", 'header');
output("---------------------------", 'info');

$requiredFiles = [
    'includes/header.php' => 'Test Header',
    'includes/footer.php' => 'Test Footer',
    'tools/logger.php' => 'Test Logger',
    'tools/storage.php' => 'Storage Handler',
    'assets/css/test.css' => 'Test Stylesheet'
];

foreach ($requiredFiles as $file => $description) {
    $path = TEST_BASE_PATH . '/' . $file;
    if (file_exists($path)) {
        output("$description ($file) - Exists", 'success');
        $testResults['passed']++;
    } else {
        output("$description ($file) - Missing!", 'error');
        $testResults['failed']++;
        $testResults['errors'][] = "Missing file: $file";
    }
}

// Test 5: Check database connectivity
output("\n💾 CHECKING DATABASE", 'header');
output("--------------------", 'info');

// Include the bootstrap to get database configuration
$bootstrapFile = dirname(TEST_BASE_PATH) . '/app/bootstrap.php';
if (file_exists($bootstrapFile)) {
    require_once $bootstrapFile;
    
    // Check if storage path is defined
    if (defined('BTT_STORAGE_PATH')) {
        if (file_exists(BTT_STORAGE_PATH)) {
            output("Storage path exists: " . BTT_STORAGE_PATH, 'success');
            $testResults['passed']++;
            
            // Try to connect to SQLite
            try {
                $dbFile = BTT_STORAGE_PATH . '/btt.db';
                if (file_exists($dbFile)) {
                    $db = new PDO('sqlite:' . $dbFile);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Count tables
                    $stmt = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
                    $tableCount = $stmt->fetchColumn();
                    
                    output("SQLite database connected - $tableCount tables found", 'success');
                    $testResults['passed']++;
                } else {
                    output("SQLite database file not found", 'warning');
                    $testResults['warnings']++;
                }
            } catch (Exception $e) {
                output("Database connection error: " . $e->getMessage(), 'error');
                $testResults['failed']++;
                $testResults['errors'][] = "Database connection failed";
            }
        } else {
            output("Storage path does not exist: " . BTT_STORAGE_PATH, 'error');
            $testResults['failed']++;
            $testResults['errors'][] = "Storage path missing";
        }
    } else {
        output("BTT_STORAGE_PATH not defined", 'error');
        $testResults['failed']++;
    }
} else {
    output("Bootstrap file not found", 'error');
    $testResults['failed']++;
}

// Test 6: Check API endpoint
output("\n🔌 CHECKING API ENDPOINT", 'header');
output("-------------------------", 'info');

$apiUrl = 'http://localhost/BTT/api/';
$apiResult = testFileAccess($apiUrl);

if ($apiResult['accessible']) {
    output("API endpoint accessible", 'success');
    $testResults['passed']++;
    
    // Try to parse JSON response
    $apiResponse = @file_get_contents($apiUrl);
    $apiData = @json_decode($apiResponse, true);
    
    if ($apiData !== null) {
        output("API returns valid JSON", 'success');
        $testResults['passed']++;
        
        if (isset($apiData['status'])) {
            output("API status: " . $apiData['status'], 'info');
        }
    } else {
        output("API does not return valid JSON", 'warning');
        $testResults['warnings']++;
    }
} else {
    output("API endpoint not accessible (HTTP {$apiResult['statusCode']})", 'error');
    $testResults['failed']++;
    $testResults['errors'][] = "API endpoint returned HTTP {$apiResult['statusCode']}";
}

// Summary
output("\n=====================================", 'header');
output("TEST SUMMARY", 'header');
output("=====================================", 'info');

output("\n✅ Passed: {$testResults['passed']}", 'success');
output("❌ Failed: {$testResults['failed']}", $testResults['failed'] > 0 ? 'error' : 'success');
output("⚠️  Warnings: {$testResults['warnings']}", $testResults['warnings'] > 0 ? 'warning' : 'success');

if (!empty($testResults['errors'])) {
    output("\n🔴 CRITICAL ISSUES:", 'error');
    foreach ($testResults['errors'] as $error) {
        output("  - $error", 'error');
    }
}

// Recommendations
output("\n💡 RECOMMENDATIONS:", 'header');

if ($testResults['failed'] === 0 && $testResults['warnings'] === 0) {
    output("✨ All tests passed! The test suite is working correctly.", 'success');
} else {
    if ($testResults['failed'] > 0) {
        output("- Fix critical errors listed above", 'warning');
        output("- Check file permissions and paths", 'warning');
        output("- Ensure all dependencies are installed", 'warning');
    }
    
    if ($testResults['warnings'] > 0) {
        output("- Review warning messages", 'info');
        output("- Consider running seed.php to populate test data", 'info');
    }
}

// Performance note
$executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
output("\n⏱️  Execution time: " . number_format($executionTime, 3) . " seconds", 'info');

// HTML footer if not CLI
if (!$isCLI) {
    echo '</body></html>';
}

// Return exit code for CLI
if ($isCLI) {
    exit($testResults['failed'] > 0 ? 1 : 0);
}
?>
