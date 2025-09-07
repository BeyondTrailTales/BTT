<?php
session_start();

// Set user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

header('Content-Type: text/plain');
echo "=== PHOTO SAVE DEBUG ===\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    
    // Check the most recent trips with their photo data
    echo "1. Recent trips with photos:\n";
    $stmt = $db->query("
        SELECT id, title, photo_path, photo_alt_text, created_at, updated_at 
        FROM trips 
        WHERE user_id = 1 
        ORDER BY updated_at DESC 
        LIMIT 5
    ");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trips as $trip) {
        echo "   Trip {$trip['id']}: {$trip['title']}\n";
        echo "     Photo: " . ($trip['photo_path'] ?: 'NONE') . "\n";
        echo "     Alt: " . ($trip['photo_alt_text'] ?: 'NONE') . "\n";
        echo "     Updated: {$trip['updated_at']}\n";
        
        if ($trip['photo_path']) {
            $fullPath = __DIR__ . '/' . $trip['photo_path'];
            echo "     File exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
            if (file_exists($fullPath)) {
                echo "     File size: " . number_format(filesize($fullPath)) . " bytes\n";
            }
        }
        echo "\n";
    }
    
    // Check if there are any error logs
    echo "2. Checking error log (last 20 lines):\n";
    $logFile = __DIR__ . '/storage/logs/app.log';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLines = array_slice($lines, -20);
        foreach ($recentLines as $line) {
            if (stripos($line, 'photo') !== false || stripos($line, 'upload') !== false) {
                echo "   $line\n";
            }
        }
    } else {
        echo "   No log file found at $logFile\n";
    }
    
    // Test the API endpoint directly
    echo "\n3. Testing trip API endpoints:\n";
    echo "   Main API: " . (file_exists(__DIR__ . '/api/trips.php') ? "✅ EXISTS" : "❌ MISSING") . "\n";
    echo "   Routes file: " . (file_exists(__DIR__ . '/api/routes/trips.php') ? "✅ EXISTS" : "❌ MISSING") . "\n";
    echo "   Ajax handler: " . (file_exists(__DIR__ . '/ajax-handler.php') ? "✅ EXISTS" : "❌ MISSING") . "\n";
    
    // Check if the form is submitting to the right endpoint
    echo "\n4. Checking form submission targets:\n";
    $tripsJs = file_get_contents(__DIR__ . '/assets/js/trips.js');
    
    if (strpos($tripsJs, 'callMainAPI') !== false) {
        echo "   ✅ Found callMainAPI calls in trips.js\n";
    } else {
        echo "   ❌ No callMainAPI calls found\n";
    }
    
    if (strpos($tripsJs, 'api/trips') !== false) {
        echo "   ✅ Found api/trips references\n";
    } else {
        echo "   ❌ No api/trips references found\n";
    }
    
    if (strpos($tripsJs, 'ajax-handler') !== false) {
        echo "   ✅ Found ajax-handler references\n";
    } else {
        echo "   ❌ No ajax-handler references found\n";
    }
    
    // Check what happens when we try to access the API
    echo "\n5. Testing API accessibility:\n";
    $apiUrl = 'http://localhost/BTT/api/trips';
    echo "   Testing: $apiUrl\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' => [
                'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]
    ]);
    
    $result = @file_get_contents($apiUrl, false, $context);
    if ($result !== false) {
        echo "   ✅ API is accessible\n";
        echo "   Response: " . substr($result, 0, 200) . "...\n";
    } else {
        echo "   ❌ API is not accessible\n";
        $error = error_get_last();
        if ($error) {
            echo "   Error: " . $error['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>