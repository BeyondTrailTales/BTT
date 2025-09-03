<?php
/**
 * Test API wrapper to capture any PHP errors
 */

// Disable HTML errors and capture them
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to capture any warnings
ob_start();

// Start error handler
$errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
    $errors[] = [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line
    ];
});

try {
    // Include the API entry point
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['route'] = 'health';
    
    require_once dirname(__DIR__) . '/api/index.php';
    
} catch (Exception $e) {
    $errors[] = [
        'severity' => E_ERROR,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// Get output
$output = ob_get_clean();

// Restore error handler
restore_error_handler();

// Check if output is JSON
$json = json_decode($output, true);

echo "=== API Test Results ===\n\n";

if ($json !== null) {
    echo "✅ Valid JSON response:\n";
    echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Invalid JSON response. Raw output:\n";
    echo $output . "\n";
}

if (!empty($errors)) {
    echo "\n⚠️ PHP Errors/Warnings detected:\n";
    foreach ($errors as $error) {
        $type = match($error['severity']) {
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE => 'NOTICE',
            E_DEPRECATED => 'DEPRECATED',
            default => 'UNKNOWN'
        };
        echo sprintf("[%s] %s\n  File: %s:%d\n", 
            $type, 
            $error['message'],
            str_replace(dirname(__DIR__) . '/', '', $error['file']),
            $error['line']
        );
    }
} else {
    echo "\n✅ No PHP errors detected\n";
}
