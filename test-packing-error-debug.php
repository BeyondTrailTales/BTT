<?php
session_start();

// Login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Please login first: http://localhost/BTT/manual-login.php\n";
    exit;
}

header('Content-Type: text/plain');
echo "=== PACKING API ERROR DEBUG ===\n";

require_once __DIR__ . '/app/config.php';

$user_id = $_SESSION['user_id'];
$trip_id = 26;

echo "User ID: $user_id\n";
echo "Trip ID: $trip_id\n\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "=== TESTING DIRECT FUNCTION CALL ===\n";
    
    // Check if files exist
    $tripPackingFile = __DIR__ . '/api/routes/trip_packing.php';
    if (!file_exists($tripPackingFile)) {
        echo "❌ trip_packing.php file not found at: $tripPackingFile\n";
        exit;
    }
    
    echo "✅ trip_packing.php file exists\n";
    
    // Check if TripPacking model exists
    $tripPackingModel = __DIR__ . '/app/models/TripPacking.php';
    if (!file_exists($tripPackingModel)) {
        echo "⚠️ TripPacking.php model not found at: $tripPackingModel\n";
    } else {
        echo "✅ TripPacking.php model exists\n";
    }
    
    // Try to include the file and catch any syntax errors
    try {
        require_once $tripPackingFile;
        echo "✅ trip_packing.php included successfully\n";
    } catch (ParseError $e) {
        echo "❌ Syntax error in trip_packing.php: " . $e->getMessage() . "\n";
        exit;
    } catch (Error $e) {
        echo "❌ Fatal error including trip_packing.php: " . $e->getMessage() . "\n";
        exit;
    }
    
    // Check if function exists
    if (!function_exists('getPackingList')) {
        echo "❌ getPackingList function not found\n";
        exit;
    }
    
    echo "✅ getPackingList function exists\n";
    
    // Test the function with detailed error catching
    echo "\n=== CALLING FUNCTION WITH ERROR HANDLING ===\n";
    
    set_error_handler(function($severity, $message, $file, $line) {
        echo "PHP Error: $message in $file on line $line\n";
    });
    
    try {
        ob_start();
        getPackingList($trip_id);
        $output = ob_get_clean();
        
        echo "Function completed successfully\n";
        echo "Output: $output\n";
        
    } catch (Exception $e) {
        $partialOutput = ob_get_clean();
        echo "❌ Exception caught: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        echo "Partial output before error: $partialOutput\n";
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    } catch (Error $e) {
        $partialOutput = ob_get_clean();
        echo "❌ Fatal error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        echo "Partial output before error: $partialOutput\n";
    }
    
    restore_error_handler();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>