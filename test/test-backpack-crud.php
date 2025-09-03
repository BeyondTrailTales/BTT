<?php
// Test Backpack CRUD Operations
require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json');

// Test data
$testPack = [
    'name' => 'Test Pack ' . date('Y-m-d H:i:s'),
    'description' => 'Created via test script',
    'capacity_l' => 50,
    'weight_empty_g' => 1200,
    'type' => 'custom',
    'sections' => [
        [
            'id' => 'main',
            'name' => 'Main Compartment',
            'items' => [],
            'order' => 0
        ]
    ]
];

// Initialize cURL
$ch = curl_init();

// Base API URL
$apiBase = 'http://localhost/BTT/api/index.php?route=backpacks';

// Test 1: Create a backpack
curl_setopt($ch, CURLOPT_URL, $apiBase);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPack));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$createResult = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$createData = json_decode($createResult, true);

$results = [];
$results['create'] = [
    'success' => $createData['success'] ?? false,
    'id' => $createData['data']['id'] ?? null,
    'message' => $createData['message'] ?? 'Failed to create',
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'raw_response' => $createResult
];

if ($results['create']['success']) {
    $packId = $createData['data']['id'];
    
    // Test 2: Get the created backpack
    curl_setopt($ch, CURLOPT_URL, $apiBase . '&id=' . $packId);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    
    $getResult = curl_exec($ch);
    $getData = json_decode($getResult, true);
    
    $results['get'] = [
        'success' => $getData['success'] ?? false,
        'found' => isset($getData['data']['id']) && $getData['data']['id'] == $packId,
        'name' => $getData['data']['name'] ?? null
    ];
    
    // Test 3: Update the backpack
    $updateData = [
        'name' => $testPack['name'] . ' - Updated',
        'description' => 'Updated via test script',
        'capacity_l' => 55
    ];
    
    curl_setopt($ch, CURLOPT_URL, $apiBase . '&id=' . $packId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
    
    $updateResult = curl_exec($ch);
    $updateResponse = json_decode($updateResult, true);
    
    $results['update'] = [
        'success' => $updateResponse['success'] ?? false,
        'message' => $updateResponse['message'] ?? 'Failed to update'
    ];
    
    // Test 4: List all backpacks
    curl_setopt($ch, CURLOPT_URL, $apiBase);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_POSTFIELDS, null);
    
    $listResult = curl_exec($ch);
    $listData = json_decode($listResult, true);
    
    $results['list'] = [
        'success' => $listData['success'] ?? false,
        'count' => count($listData['data'] ?? []),
        'contains_test' => false
    ];
    
    // Check if our test pack is in the list
    if ($results['list']['success']) {
        foreach ($listData['data'] as $pack) {
            if ($pack['id'] == $packId) {
                $results['list']['contains_test'] = true;
                break;
            }
        }
    }
    
    // Test 5: Delete the backpack (cleanup)
    curl_setopt($ch, CURLOPT_URL, $apiBase . '&id=' . $packId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    
    $deleteResult = curl_exec($ch);
    $deleteData = json_decode($deleteResult, true);
    
    $results['delete'] = [
        'success' => $deleteData['success'] ?? false,
        'message' => $deleteData['message'] ?? 'Failed to delete'
    ];
}

curl_close($ch);

// Output results
echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'none',
    'tests' => $results,
    'summary' => [
        'total_tests' => count($results),
        'passed' => count(array_filter($results, function($r) { return $r['success'] ?? false; })),
        'failed' => count(array_filter($results, function($r) { return !($r['success'] ?? false); }))
    ]
], JSON_PRETTY_PRINT);
?>
