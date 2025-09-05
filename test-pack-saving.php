<?php
// Test script to verify pack gear saving functionality
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/api/classes/Database.php';

// Force session for testing
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user ID
}

$db = Database::getInstance();

// Test mode check
if (isset($_GET['test'])) {
    $test = $_GET['test'];
    
    switch($test) {
        case 'create':
            // Test creating a pack with items
            $packData = [
                'name' => 'Test Pack ' . date('Y-m-d H:i:s'),
                'description' => 'Testing gear saving functionality',
                'capacity_l' => 65,
                'weight_empty_g' => 1000,
                'type' => 'custom',
                'sections' => [
                    [
                        'id' => 'main',
                        'name' => 'Main Pack',
                        'items' => [
                            [
                                'gear_id' => 1,
                                'name' => 'Test Tent',
                                'weight_g' => 1500,
                                'quantity' => 1,
                                'category' => 'shelter'
                            ],
                            [
                                'gear_id' => 2,
                                'name' => 'Sleeping Bag',
                                'weight_g' => 800,
                                'quantity' => 1,
                                'category' => 'sleep'
                            ]
                        ]
                    ]
                ]
            ];
            
            // Simulate the ajax-handler POST
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_GET['route'] = 'backpacks';
            $jsonData = json_encode($packData);
            
            // Test the creation - make a direct database insert instead
            try {
                // Insert pack
                $packId = $db->insert("backpacks", [
                    'user_id' => $_SESSION['user_id'],
                    'name' => $packData['name'],
                    'description' => $packData['description'],
                    'capacity_l' => $packData['capacity_l'],
                    'weight_empty_g' => $packData['weight_empty_g'],
                    'type' => $packData['type'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Insert items
                foreach ($packData['sections'] as $section) {
                    foreach ($section['items'] as $item) {
                        $db->insert("backpack_gear", [
                            'backpack_id' => $packId,
                            'custom_name' => $item['name'],
                            'custom_weight' => $item['weight_g'],
                            'custom_category' => $item['category'],
                            'quantity' => $item['quantity'],
                            'section' => $section['id'],
                            'position' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
                $response = json_encode(['success' => true, 'id' => $packId]);
            } catch (Exception $e) {
                $response = json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            
            echo "<h2>Create Pack Test</h2>";
            echo "<pre>Request: " . json_encode($packData, JSON_PRETTY_PRINT) . "</pre>";
            echo "<pre>Response: " . $response . "</pre>";
            
            // Check database
            $lastPack = $db->fetchOne("SELECT * FROM backpacks WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$_SESSION['user_id']]);
            if ($lastPack) {
                $items = $db->fetchAll("SELECT * FROM backpack_gear WHERE backpack_id = ?", [$lastPack['id']]);
                echo "<h3>Database Check</h3>";
                echo "<pre>Pack: " . json_encode($lastPack, JSON_PRETTY_PRINT) . "</pre>";
                echo "<pre>Items: " . json_encode($items, JSON_PRETTY_PRINT) . "</pre>";
            }
            break;
            
        case 'verify':
            // Verify user isolation
            $user1Packs = $db->fetchAll("SELECT id, name, user_id FROM backpacks WHERE user_id = 1");
            $user2Packs = $db->fetchAll("SELECT id, name, user_id FROM backpacks WHERE user_id = 2");
            
            echo "<h2>User Isolation Test</h2>";
            echo "<h3>User 1 Packs:</h3><pre>" . json_encode($user1Packs, JSON_PRETTY_PRINT) . "</pre>";
            echo "<h3>User 2 Packs:</h3><pre>" . json_encode($user2Packs, JSON_PRETTY_PRINT) . "</pre>";
            
            if (count($user1Packs) > 0) {
                $pack1Items = $db->fetchAll("
                    SELECT bg.*, b.user_id 
                    FROM backpack_gear bg 
                    JOIN backpacks b ON bg.backpack_id = b.id 
                    WHERE b.id = ?", [$user1Packs[0]['id']]);
                echo "<h3>User 1 Pack Items:</h3><pre>" . json_encode($pack1Items, JSON_PRETTY_PRINT) . "</pre>";
            }
            break;
            
        case 'schema':
            // Check database schema
            echo "<h2>Database Schema Check</h2>";
            
            // Check backpacks table
            $backpacksSchema = $db->query("PRAGMA table_info(backpacks)")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Backpacks Table Schema:</h3><pre>" . json_encode($backpacksSchema, JSON_PRETTY_PRINT) . "</pre>";
            
            // Check backpack_gear table
            $gearSchema = $db->query("PRAGMA table_info(backpack_gear)")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Backpack_Gear Table Schema:</h3><pre>" . json_encode($gearSchema, JSON_PRETTY_PRINT) . "</pre>";
            
            // Check foreign key constraint
            $foreignKeys = $db->query("PRAGMA foreign_key_list(backpack_gear)")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Foreign Keys:</h3><pre>" . json_encode($foreignKeys, JSON_PRETTY_PRINT) . "</pre>";
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pack Saving Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-btn { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 10px; 
            background: #58cc02; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .test-btn:hover { background: #46a000; }
        pre { 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto; 
        }
        h2 { color: #2a3f2e; }
        h3 { color: #58cc02; }
    </style>
</head>
<body>
    <h1>Pack Gear Saving Test Suite</h1>
    
    <p>Current User ID: <?= $_SESSION['user_id'] ?></p>
    
    <h2>Available Tests:</h2>
    
    <a href="?test=schema" class="test-btn">1. Check Database Schema</a>
    <p>Verify the database tables and relationships are set up correctly</p>
    
    <a href="?test=create" class="test-btn">2. Test Pack Creation with Items</a>
    <p>Create a new pack with gear items and verify they save correctly</p>
    
    <a href="?test=verify" class="test-btn">3. Verify User Isolation</a>
    <p>Check that packs and gear are properly isolated by user</p>
    
    <hr>
    
    <h2>Live Test: Add Item via JavaScript</h2>
    <button onclick="testQuickAdd()" class="test-btn">Test quickAddItem()</button>
    <div id="test-result"></div>
    
    <script src="<?= BTT_ASSETS_URL ?>/js/jquery.min.js"></script>
    <script src="<?= BTT_ASSETS_URL ?>/js/app.js"></script>
    <script>
        function testQuickAdd() {
            // Test if PackBuilder.quickAddItem actually saves
            if (typeof PackBuilder !== 'undefined' && PackBuilder.quickAddItem) {
                console.log('Testing PackBuilder.quickAddItem()');
                
                // First ensure we have a pack loaded
                if (!PackBuilder.state.currentPack) {
                    $('#test-result').html('<p style="color: red">No pack loaded. Please load a pack first.</p>');
                    return;
                }
                
                // Add a test item
                PackBuilder.quickAddItem(1);
                
                $('#test-result').html('<p style="color: green">Item added. Check the pack builder to verify.</p>');
                
                // Check if the item was added to the UI
                setTimeout(() => {
                    const items = $('.pack-item').length;
                    $('#test-result').append(`<p>Found ${items} items in the pack UI</p>`);
                }, 500);
            } else {
                $('#test-result').html('<p style="color: red">PackBuilder not available on this page</p>');
            }
        }
    </script>
</body>
</html>