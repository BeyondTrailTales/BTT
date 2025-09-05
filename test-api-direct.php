<?php
session_start();
header('Content-Type: text/plain');

echo "=== DIRECT API TEST ===\n";

// Set up session like we're logged in
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['authenticated'] = true;

// Simulate the API call
$_POST['_method'] = 'PUT';
$_POST['title'] = 'Test Title';  
$_POST['remove_photo'] = '1';

echo "Testing API call with:\n";
print_r($_POST);

echo "\n=== CALLING API ===\n";

// Capture all output
ob_start();

try {
    // Call the main API
    $_GET['route'] = 'trips';
    $_GET['id'] = '6';
    
    include __DIR__ . '/api/index.php';
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

echo "=== RAW API OUTPUT ===\n";
echo $output;

echo "\n=== OUTPUT ANALYSIS ===\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Starts with JSON?: " . (substr(trim($output), 0, 1) === '{' ? 'YES' : 'NO') . "\n";
echo "Contains HTML?: " . (strpos($output, '<br') !== false ? 'YES' : 'NO') . "\n";

if (strpos($output, '<br') !== false) {
    echo "\n❌ FOUND PHP ERROR HTML - this is the problem!\n";
    echo "The PHP error is being output before the JSON response.\n";
}

echo "\n=== FIRST 200 CHARS ===\n";
echo substr($output, 0, 200) . "...\n";
?>