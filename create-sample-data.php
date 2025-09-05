<?php
/**
 * Create Sample Data for Testing
 * This will add some trips, backpacks, and gear to see the dashboard working
 */

require_once __DIR__ . '/app/bootstrap.php';
require_auth();
require_once __DIR__ . '/api/classes/Database.php';

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

echo "<h1>🎯 Creating Sample Data for User ID: {$user_id}</h1>";

try {
    // Check current data
    $trips_before = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ?", [$user_id])['count'];
    $packs_before = $db->fetchOne("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?", [$user_id])['count'];
    $gear_before = $db->fetchOne("SELECT COUNT(*) as count FROM user_gear WHERE user_id = ?", [$user_id])['count'];
    
    echo "<h2>Current Data:</h2>";
    echo "<p>Trips: $trips_before | Backpacks: $packs_before | Gear: $gear_before</p>";
    
    // Create sample trips
    echo "<h2>Creating Sample Trips...</h2>";
    
    $trips = [
        [
            'user_id' => $user_id,
            'title' => 'Mount Washington Summit',
            'location' => 'White Mountains, NH',
            'distance' => 8.2,
            'distance_unit' => 'miles',
            'elevation_gain' => 4250,
            'elevation_unit' => 'feet',
            'start_date' => date('Y-m-d', strtotime('-5 days')),
            'end_date' => date('Y-m-d', strtotime('-4 days')),
            'completed' => 1,
            'difficulty' => 'hard',
            'trail_type' => 'out-and-back',
            'description' => 'Amazing summit hike with spectacular views!',
            'photo_path' => 'assets/img/trips/sample-mountain.jpg',
            'photo_alt_text' => 'Mount Washington summit view',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_id' => $user_id,
            'title' => 'Appalachian Trail Section Hike',
            'location' => 'Vermont',
            'distance' => 42.5,
            'distance_unit' => 'miles',
            'elevation_gain' => 8900,
            'elevation_unit' => 'feet',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date' => date('Y-m-d', strtotime('+10 days')),
            'completed' => 0,
            'difficulty' => 'hard',
            'trail_type' => 'point-to-point',
            'description' => 'Planning a 4-day section hike',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_id' => $user_id,
            'title' => 'Franconia Ridge Loop',
            'location' => 'White Mountains, NH',
            'distance' => 8.9,
            'distance_unit' => 'miles',
            'elevation_gain' => 3900,
            'elevation_unit' => 'feet',
            'start_date' => date('Y-m-d', strtotime('-30 days')),
            'end_date' => date('Y-m-d', strtotime('-30 days')),
            'completed' => 1,
            'difficulty' => 'hard',
            'trail_type' => 'loop',
            'description' => 'Classic ridge walk with incredible 360 views',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    foreach ($trips as $trip) {
        $result = $db->insert('trips', $trip);
        echo "<p>✅ Created trip: {$trip['title']}</p>";
    }
    
    // Create sample gear
    echo "<h2>Creating Sample Gear...</h2>";
    
    $gear_items = [
        ['user_id' => $user_id, 'name' => 'Osprey Atmos AG 65', 'category' => 'backpack', 'brand' => 'Osprey', 'weight_g' => 2100, 'purchase_price' => 270.00, 'notes' => 'Main backpack for multi-day trips'],
        ['user_id' => $user_id, 'name' => 'Big Agnes Copper Spur HV UL2', 'category' => 'shelter', 'brand' => 'Big Agnes', 'weight_g' => 1190, 'purchase_price' => 449.95, 'notes' => 'Ultralight 2-person tent'],
        ['user_id' => $user_id, 'name' => 'Enlightened Equipment Revelation 20°', 'category' => 'sleep', 'brand' => 'Enlightened Equipment', 'weight_g' => 550, 'purchase_price' => 315.00, 'notes' => 'Down quilt for 3-season camping'],
        ['user_id' => $user_id, 'name' => 'MSR PocketRocket 2', 'category' => 'cooking', 'brand' => 'MSR', 'weight_g' => 73, 'purchase_price' => 49.95, 'notes' => 'Ultralight canister stove'],
        ['user_id' => $user_id, 'name' => 'Sawyer Squeeze', 'category' => 'water', 'brand' => 'Sawyer', 'weight_g' => 85, 'purchase_price' => 34.95, 'notes' => 'Water filter system'],
        ['user_id' => $user_id, 'name' => 'Garmin inReach Mini', 'category' => 'electronics', 'brand' => 'Garmin', 'weight_g' => 100, 'purchase_price' => 349.99, 'notes' => 'Satellite communicator for emergencies'],
        ['user_id' => $user_id, 'name' => 'Patagonia Houdini Jacket', 'category' => 'clothing', 'brand' => 'Patagonia', 'weight_g' => 105, 'purchase_price' => 99.00, 'notes' => 'Wind jacket'],
        ['user_id' => $user_id, 'name' => 'Salomon X Ultra 3 GTX', 'category' => 'footwear', 'brand' => 'Salomon', 'weight_g' => 820, 'purchase_price' => 150.00, 'notes' => 'Hiking boots']
    ];
    
    $gear_ids = [];
    foreach ($gear_items as $gear) {
        $gear['created_at'] = date('Y-m-d H:i:s');
        $gear['updated_at'] = date('Y-m-d H:i:s');
        $gear['is_custom'] = 1;
        $result = $db->insert('user_gear', $gear);
        $gear_ids[] = $db->lastInsertId();
        echo "<p>✅ Created gear: {$gear['name']}</p>";
    }
    
    // Create sample backpacks
    echo "<h2>Creating Sample Backpacks...</h2>";
    
    $backpacks = [
        [
            'name' => 'Weekend Warrior Pack',
            'description' => 'My go-to pack for 2-3 day trips',
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'Ultralight Summer Setup',
            'description' => 'Minimal gear for fast and light summer hiking',
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'Day Hike Essentials',
            'description' => 'Everything needed for a safe day hike',
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $pack_index = 0;
    foreach ($backpacks as $pack) {
        $result = $db->insert('backpacks', $pack);
        $pack_id = $db->lastInsertId();
        echo "<p>✅ Created backpack: {$pack['name']}</p>";
        
        // Add some gear to each pack
        $gear_count = min(3 + $pack_index, count($gear_ids));
        for ($i = 0; $i < $gear_count; $i++) {
            $db->insert('backpack_gear', [
                'backpack_id' => $pack_id,
                'gear_id' => $gear_ids[$i],
                'quantity' => 1,
                'added_at' => date('Y-m-d H:i:s')
            ]);
        }
        echo "<p>   Added $gear_count items to pack</p>";
        $pack_index++;
    }
    
    // Check final data
    $trips_after = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ?", [$user_id])['count'];
    $packs_after = $db->fetchOne("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?", [$user_id])['count'];
    $gear_after = $db->fetchOne("SELECT COUNT(*) as count FROM user_gear WHERE user_id = ?", [$user_id])['count'];
    
    echo "<h2>✅ Sample Data Created Successfully!</h2>";
    echo "<p><strong>Final counts:</strong> Trips: $trips_after | Backpacks: $packs_after | Gear: $gear_after</p>";
    
    // Create a sample trip photo
    $sampleImagePath = __DIR__ . '/assets/img/trips/sample-mountain.jpg';
    if (!file_exists(dirname($sampleImagePath))) {
        mkdir(dirname($sampleImagePath), 0777, true);
    }
    
    // Create a simple colored rectangle as a placeholder image
    $img = imagecreatetruecolor(800, 600);
    $bg = imagecolorallocate($img, 135, 206, 235); // Sky blue
    $mountain = imagecolorallocate($img, 105, 105, 105); // Mountain gray
    imagefill($img, 0, 0, $bg);
    
    // Draw simple mountain shapes
    $points = [400, 200, 200, 600, 600, 600];
    imagefilledpolygon($img, $points, 3, $mountain);
    
    imagejpeg($img, $sampleImagePath, 90);
    imagedestroy($img);
    echo "<p>✅ Created sample trip photo</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Next Steps:</h2>";
echo "<p><a href='/BTT/dashboard' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🏠 Go to Dashboard</a>";
echo "<a href='/BTT/debug-dashboard-queries.php' style='background: #2196f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>🔍 Check Dashboard Queries</a></p>";
?>