<?php
/**
 * Test backpack sections save and load functionality
 */

// Configuration
$baseUrl = 'http://localhost/BTT';
$apiUrl = $baseUrl . '/api';

// Helper function to make API requests
function apiRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    global $apiUrl;
    
    // Parse the endpoint for query-based routing
    $parts = explode('/', trim($endpoint, '/'));
    $route = $parts[0];
    $params = [];
    
    if (count($parts) > 1) {
        // For auth/login or other routes - the second part is passed as 'id' parameter
        $params['id'] = $parts[1];
        if (isset($parts[2])) {
            $params['action'] = $parts[2];
        }
    }
    
    $url = $apiUrl . '/index.php?route=' . $route;
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

echo "=== Testing Backpack Sections Save/Load ===\n\n";

// Step 1: Login
echo "1. Logging in...\n";
$loginData = [
    'login' => 'test@example.com',  // The field is 'login' not 'email'
    'password' => 'Test123!'
];

$loginResponse = apiRequest('/auth/login', 'POST', $loginData);
if ($loginResponse['code'] === 200) {
    echo "✓ Login successful\n";
    $token = $loginResponse['body']['data']['token'] ?? null;
    $userId = $loginResponse['body']['data']['user']['id'] ?? null;
    echo "  User ID: $userId\n";
} else {
    echo "✗ Login failed:\n";
    print_r($loginResponse);
    exit(1);
}

// Step 2: Create backpack with sections and items
echo "\n2. Creating backpack with sections...\n";
$backpackData = [
    'name' => 'Test Backpack ' . time(),
    'description' => 'Testing sections save/load',
    'capacity_l' => 65,
    'weight_empty_g' => 1500,
    'type' => 'multi-day',
    'sections' => [
        [
            'name' => 'Sleep System',
            'order' => 0,
            'items' => [
                [
                    'name' => 'Sleeping Bag',
                    'weight_g' => 900,
                    'quantity' => 1,
                    'category' => 'sleep',
                    'brand' => 'Test Brand',
                    'price' => 299.99,
                    'notes' => 'Down filled, 20°F'
                ],
                [
                    'name' => 'Sleeping Pad',
                    'weight_g' => 450,
                    'quantity' => 1,
                    'category' => 'sleep',
                    'brand' => 'Test Brand',
                    'price' => 149.99,
                    'notes' => 'Ultralight inflatable'
                ]
            ]
        ],
        [
            'name' => 'Cooking',
            'order' => 1,
            'items' => [
                [
                    'name' => 'Stove',
                    'weight_g' => 73,
                    'quantity' => 1,
                    'category' => 'cooking',
                    'brand' => 'MSR',
                    'price' => 45,
                    'notes' => 'Pocket Rocket 2'
                ],
                [
                    'name' => 'Pot',
                    'weight_g' => 110,
                    'quantity' => 1,
                    'category' => 'cooking',
                    'brand' => 'Toaks',
                    'price' => 35,
                    'notes' => 'Titanium 750ml'
                ],
                [
                    'name' => 'Fuel Canister',
                    'weight_g' => 230,
                    'quantity' => 2,
                    'category' => 'cooking',
                    'brand' => 'MSR',
                    'price' => 6,
                    'notes' => '110g canister'
                ]
            ]
        ],
        [
            'name' => 'Clothing',
            'order' => 2,
            'items' => [
                [
                    'name' => 'Rain Jacket',
                    'weight_g' => 290,
                    'quantity' => 1,
                    'category' => 'clothing',
                    'brand' => 'Outdoor Research',
                    'price' => 199,
                    'notes' => 'Helium II'
                ],
                [
                    'name' => 'Extra Socks',
                    'weight_g' => 60,
                    'quantity' => 3,
                    'category' => 'clothing',
                    'brand' => 'Darn Tough',
                    'price' => 22,
                    'notes' => 'Merino wool hiking socks'
                ]
            ]
        ]
    ]
];

$createResponse = apiRequest('/backpacks', 'POST', $backpackData, $token);
if ($createResponse['code'] === 201) {
    echo "✓ Backpack created successfully\n";
    $backpackId = $createResponse['body']['data']['id'] ?? null;
    echo "  Backpack ID: $backpackId\n";
    
    // Check if sections were included in response
    if (isset($createResponse['body']['data']['sections'])) {
        $sectionCount = count($createResponse['body']['data']['sections']);
        echo "  Sections in response: $sectionCount\n";
        foreach ($createResponse['body']['data']['sections'] as $section) {
            $itemCount = isset($section['items']) ? count($section['items']) : 0;
            echo "    - {$section['name']}: $itemCount items\n";
        }
    } else {
        echo "  ⚠ No sections in create response\n";
    }
} else {
    echo "✗ Failed to create backpack:\n";
    print_r($createResponse);
    exit(1);
}

// Step 3: Retrieve backpack to verify sections
echo "\n3. Retrieving backpack to verify sections...\n";
$getResponse = apiRequest("/backpacks/$backpackId", 'GET', null, $token);
if ($getResponse['code'] === 200) {
    echo "✓ Backpack retrieved successfully\n";
    $backpack = $getResponse['body']['data'] ?? [];
    
    // Check basic fields
    echo "  Name: " . ($backpack['name'] ?? 'N/A') . "\n";
    echo "  Type: " . ($backpack['type'] ?? 'N/A') . "\n";
    echo "  Capacity: " . ($backpack['capacity_l'] ?? 'N/A') . " L\n";
    echo "  Empty Weight: " . ($backpack['weight_empty_g'] ?? 'N/A') . " g\n";
    
    // Check sections
    if (isset($backpack['sections']) && is_array($backpack['sections'])) {
        $sectionCount = count($backpack['sections']);
        echo "  ✓ Sections loaded: $sectionCount\n";
        
        $totalItems = 0;
        $totalWeight = 0;
        
        foreach ($backpack['sections'] as $section) {
            $itemCount = isset($section['items']) ? count($section['items']) : 0;
            $sectionWeight = 0;
            
            if (isset($section['items'])) {
                foreach ($section['items'] as $item) {
                    $itemWeight = ($item['weight_g'] ?? 0) * ($item['quantity'] ?? 1);
                    $sectionWeight += $itemWeight;
                    $totalWeight += $itemWeight;
                    $totalItems += ($item['quantity'] ?? 1);
                }
            }
            
            echo "    - {$section['name']}: $itemCount items, {$sectionWeight}g\n";
            
            // Show first item details as sample
            if (isset($section['items'][0])) {
                $item = $section['items'][0];
                echo "      Sample item: {$item['name']} - ";
                echo "{$item['weight_g']}g x {$item['quantity']} = ";
                echo ($item['weight_g'] * $item['quantity']) . "g\n";
                if (!empty($item['notes'])) {
                    echo "      Notes: {$item['notes']}\n";
                }
            }
        }
        
        echo "  Total items: $totalItems\n";
        echo "  Total weight (items): {$totalWeight}g\n";
        echo "  Total weight (with pack): " . ($totalWeight + ($backpack['weight_empty_g'] ?? 0)) . "g\n";
    } else {
        echo "  ✗ No sections found in retrieved backpack\n";
        echo "  Retrieved data structure:\n";
        print_r($backpack);
    }
} else {
    echo "✗ Failed to retrieve backpack:\n";
    print_r($getResponse);
    exit(1);
}

// Step 4: Update backpack sections
echo "\n4. Updating backpack sections...\n";
$updateData = [
    'sections' => [
        [
            'name' => 'Sleep System',
            'order' => 0,
            'items' => [
                [
                    'name' => 'Sleeping Bag',
                    'weight_g' => 900,
                    'quantity' => 1,
                    'category' => 'sleep',
                    'brand' => 'Test Brand',
                    'price' => 299.99,
                    'notes' => 'Down filled, 20°F - UPDATED'
                ],
                [
                    'name' => 'Pillow',
                    'weight_g' => 50,
                    'quantity' => 1,
                    'category' => 'sleep',
                    'brand' => 'Sea to Summit',
                    'price' => 39.99,
                    'notes' => 'Inflatable pillow - NEW ITEM'
                ]
            ]
        ],
        [
            'name' => 'Electronics',
            'order' => 1,
            'items' => [
                [
                    'name' => 'Phone',
                    'weight_g' => 200,
                    'quantity' => 1,
                    'category' => 'electronics',
                    'brand' => 'Apple',
                    'price' => 999,
                    'notes' => 'iPhone with case'
                ],
                [
                    'name' => 'Power Bank',
                    'weight_g' => 150,
                    'quantity' => 1,
                    'category' => 'electronics',
                    'brand' => 'Anker',
                    'price' => 29.99,
                    'notes' => '10000mAh'
                ]
            ]
        ]
    ]
];

$updateResponse = apiRequest("/backpacks/$backpackId", 'PUT', $updateData, $token);
if ($updateResponse['code'] === 200) {
    echo "✓ Backpack updated successfully\n";
    
    // Check updated sections
    if (isset($updateResponse['body']['data']['sections'])) {
        $sectionCount = count($updateResponse['body']['data']['sections']);
        echo "  Updated sections: $sectionCount\n";
        foreach ($updateResponse['body']['data']['sections'] as $section) {
            $itemCount = isset($section['items']) ? count($section['items']) : 0;
            echo "    - {$section['name']}: $itemCount items\n";
        }
    }
} else {
    echo "✗ Failed to update backpack:\n";
    print_r($updateResponse);
}

// Step 5: Final verification
echo "\n5. Final verification of updated sections...\n";
$finalResponse = apiRequest("/backpacks/$backpackId", 'GET', null, $token);
if ($finalResponse['code'] === 200) {
    echo "✓ Final retrieval successful\n";
    $finalBackpack = $finalResponse['body']['data'] ?? [];
    
    if (isset($finalBackpack['sections'])) {
        foreach ($finalBackpack['sections'] as $section) {
            echo "  - {$section['name']}:\n";
            if (isset($section['items'])) {
                foreach ($section['items'] as $item) {
                    echo "    • {$item['name']} ({$item['weight_g']}g x {$item['quantity']})\n";
                    if (strpos($item['notes'] ?? '', 'UPDATED') !== false || 
                        strpos($item['notes'] ?? '', 'NEW') !== false) {
                        echo "      → {$item['notes']}\n";
                    }
                }
            }
        }
    }
} else {
    echo "✗ Final retrieval failed\n";
}

// Step 6: Check database directly
echo "\n6. Checking database directly...\n";
require_once dirname(__DIR__) . '/api/database.php';
$db = Database::getInstance();

// Check backpack_gear table
$gearItems = $db->fetchAll(
    "SELECT bg.*, gi.name, gi.weight, gi.category, gi.notes 
     FROM backpack_gear bg 
     JOIN gear_items gi ON bg.gear_id = gi.id 
     WHERE bg.backpack_id = :backpack_id",
    ['backpack_id' => $backpackId]
);

if ($gearItems) {
    echo "✓ Found " . count($gearItems) . " items in backpack_gear table\n";
    foreach ($gearItems as $item) {
        echo "  - {$item['name']} (Section: {$item['section']}, Qty: {$item['quantity']})\n";
    }
} else {
    echo "⚠ No items found in backpack_gear table for backpack ID $backpackId\n";
}

// Cleanup
echo "\n7. Cleanup...\n";
$deleteResponse = apiRequest("/backpacks/$backpackId", 'DELETE', null, $token);
if ($deleteResponse['code'] === 200) {
    echo "✓ Test backpack deleted\n";
} else {
    echo "⚠ Could not delete test backpack\n";
}

echo "\n=== Test Complete ===\n";
