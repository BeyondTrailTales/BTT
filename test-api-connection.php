<?php
session_start();

// Set user session for testing
$_SESSION["user_id"] = 1;
$_SESSION["username"] = "admin";

header("Content-Type: text/plain");
echo "=== API CONNECTION TEST ===\n";

// Check if BTTApi is working
echo "1. Testing basic trip save:\n";

$testData = [
    "title" => "Connection Test " . date("H:i:s"),
    "description" => "Testing frontend-backend connection"
];

try {
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_GET["route"] = "trips";
    $_POST = $testData;
    
    ob_start();
    include __DIR__ . "/api/index.php";
    $output = ob_get_clean();
    
    echo "   Output: " . substr($output, 0, 300) . "\n";
    
    $result = json_decode($output, true);
    if ($result && $result["success"]) {
        echo "   ✅ Basic trip save works\!\n";
    } else {
        echo "   ❌ Trip save failed\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone\!\n";
?>
