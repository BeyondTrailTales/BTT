<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== BACKPACK SAVE ERROR DEBUG ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "Testing backpack save for user: $user_id\n\n";
    
    // Test data from the console output
    $testData = [
        'id' => 59,
        'name' => 'Test Backpack Save',
        'description' => 'Test description',
        'sections' => [
            [
                'id' => 'main',
                'name' => 'Main Pack',
                'items' => [
                    [
                        'id' => 'test_item',
                        'name' => 'Test Item',
                        'weight_g' => 100,
                        'category' => 'other',
                        'quantity' => 1,
                        'gear_id' => 1,
                        'is_custom' => true
                    ]
                ]
            ]
        ]
    ];
    
    // Simulate the AJAX request
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_GET['route'] = 'backpacks';
    $_GET['id'] = '59';
    
    // Set up the request data
    $_POST = [];
    file_put_contents('php://input', json_encode($testData));
    
    echo "Simulating PUT request to ajax-handler.php for backpack 59\n";
    echo "Request data:\n";
    print_r($testData);
    
    echo "\n=== CALLING AJAX HANDLER ===\n";
    
    ob_start();
    
    try {
        include __DIR__ . '/ajax-handler.php';
        $response = ob_get_clean();
        
        echo "Response received:\n";
        echo $response . "\n";
        
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse) {
            echo "\n✅ Valid JSON response\n";
            if (isset($jsonResponse['success']) && $jsonResponse['success']) {
                echo "✅ Save successful!\n";
            } else {
                echo "❌ Save failed: " . ($jsonResponse['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "\n❌ Invalid JSON response\n";
            echo "First 200 chars: " . substr($response, 0, 200) . "\n";
        }
        
    } catch (Exception $e) {
        $errorResponse = ob_get_clean();
        echo "❌ Exception during AJAX call: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        echo "Response before exception: $errorResponse\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>