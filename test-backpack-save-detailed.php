<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== DETAILED BACKPACK SAVE DEBUG ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    // Enable all error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
    echo "User ID: $user_id\n\n";
    
    // Test data similar to what's failing
    $testData = [
        'id' => 43,
        'name' => 'Test Backpack Debug',
        'description' => 'Test description',
        'sections' => [
            [
                'id' => 'main',
                'name' => 'Main Pack', 
                'items' => [
                    [
                        'id' => 'test_123',
                        'name' => 'Test Item 1',
                        'weight_g' => 100,
                        'category' => 'other',
                        'quantity' => 1,
                        'gear_id' => 1,  // Should exist
                        'is_custom' => false
                    ],
                    [
                        'id' => 'test_456', 
                        'name' => 'Custom Test Item',
                        'weight_g' => 200,
                        'category' => 'shelter',
                        'quantity' => 1,
                        'is_custom' => true  // No gear_id
                    ]
                ]
            ]
        ]
    ];
    
    echo "Testing with sample data:\n";
    print_r($testData);
    
    echo "\n=== DIRECT AJAX HANDLER TEST ===\n";
    
    // Set up request environment
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_GET['route'] = 'backpacks';
    $_GET['id'] = '43';
    $_POST = [];
    
    // Create a temporary file for php://input
    $tempInput = tmpfile();
    fwrite($tempInput, json_encode($testData));
    rewind($tempInput);
    
    // Capture all output including errors
    ob_start();
    
    set_error_handler(function($severity, $message, $file, $line) {
        echo "PHP ERROR: $message in $file:$line\n";
    });
    
    try {
        // Include the ajax handler
        include __DIR__ . '/ajax-handler.php';
        
    } catch (ParseError $e) {
        echo "PARSE ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    $output = ob_get_clean();
    
    echo "=== AJAX HANDLER OUTPUT ===\n";
    echo $output . "\n";
    
    // Try to parse as JSON
    $jsonResponse = json_decode($output, true);
    if ($jsonResponse) {
        echo "✅ Valid JSON response\n";
        if (isset($jsonResponse['success'])) {
            echo $jsonResponse['success'] ? "✅ Success: true\n" : "❌ Success: false\n";
            if (isset($jsonResponse['message'])) {
                echo "Message: " . $jsonResponse['message'] . "\n";
            }
        }
    } else {
        echo "❌ Invalid JSON response\n";
        echo "First 200 chars of raw output: " . substr($output, 0, 200) . "\n";
    }
    
    restore_error_handler();
    fclose($tempInput);
    
} catch (Exception $e) {
    echo "OUTER ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>