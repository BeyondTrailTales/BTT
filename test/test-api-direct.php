<?php
// Direct API Test
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

header('Content-Type: text/plain');

echo "Testing API directly...\n\n";

// Get current user
$user = \App\Services\AuthService::getCurrentUser();
echo "Current user: " . ($user ? $user['username'] : 'None') . "\n";
echo "User ID: " . ($user ? $user['id'] : 'None') . "\n\n";

// Test creating a backpack via PHP directly
echo "Testing database directly...\n";
try {
    $db = \App\Services\Database::getInstance();
    
    // Create test backpack
    $testData = [
        'user_id' => $user['id'],
        'name' => 'Test Pack Direct ' . time(),
        'description' => 'Created directly via PHP',
        'capacity_l' => 65,
        'weight_empty_g' => 2000,
        'type' => 'custom',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $db->query(
        "INSERT INTO backpacks (user_id, name, description, capacity_l, weight_empty_g, type, created_at, updated_at) 
         VALUES (:user_id, :name, :description, :capacity_l, :weight_empty_g, :type, :created_at, :updated_at)",
        $testData
    );
    
    $lastId = $db->lastInsertId();
    echo "SUCCESS: Created backpack with ID: $lastId\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Now test the API endpoint
echo "Testing API endpoint...\n";

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['route'] = 'backpacks';

// Set the input data
$testApiData = [
    'name' => 'Test Pack API ' . time(),
    'description' => 'Created via API test',
    'capacity_l' => 65,
    'weight_empty_g' => 2000,
    'type' => 'custom',
    'sections' => []
];

// Mock the input
$input = json_encode($testApiData);
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream");
file_put_contents('php://input', $input);

echo "Sending to API: " . json_encode($testApiData, JSON_PRETTY_PRINT) . "\n\n";

// Capture API output
ob_start();
$errorReporting = error_reporting(E_ALL);
try {
    require dirname(__DIR__) . '/api/index.php';
} catch (Exception $e) {
    echo "API Exception: " . $e->getMessage() . "\n";
}
error_reporting($errorReporting);
$apiOutput = ob_get_clean();

echo "API Response:\n";
echo $apiOutput . "\n\n";

// Try to decode response
$response = json_decode($apiOutput, true);
if ($response) {
    echo "Decoded response:\n";
    print_r($response);
} else {
    echo "Failed to decode JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

// Mock PHP stream wrapper
class MockPhpStream {
    protected $data = '';
    protected $position = 0;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_write($data) {
        $this->data = $data;
        return strlen($data);
    }
    
    public function stream_read($count) {
        $result = substr($GLOBALS['input'], $this->position, $count);
        $this->position += strlen($result);
        return $result;
    }
    
    public function stream_eof() {
        return $this->position >= strlen($GLOBALS['input']);
    }
    
    public function stream_stat() {
        return [];
    }
}
