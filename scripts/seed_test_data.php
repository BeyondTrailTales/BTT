<?php
/**
 * Seed Test Data into SQLite Database
 * Creates sample backpacks, trips, and gear items
 */

$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    echo "Connected to database\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Clear existing data (optional)
    $clearData = false; // Set to true to clear existing data
    if ($clearData) {
        $pdo->exec('DELETE FROM trip_gear');
        $pdo->exec('DELETE FROM backpack_gear');
        $pdo->exec('DELETE FROM trips');
        $pdo->exec('DELETE FROM backpacks');
        $pdo->exec('DELETE FROM gear_items');
        echo "Cleared existing data\n";
    }
    
    // Insert gear items
    $gearItems = [
        ['name' => 'Osprey Atmos 65L Backpack', 'weight' => 2100, 'category' => 'Backpack', 'brand' => 'Osprey', 'price' => 270],
        ['name' => 'Big Agnes Copper Spur HV UL2 Tent', 'weight' => 1190, 'category' => 'Shelter', 'brand' => 'Big Agnes', 'price' => 450],
        ['name' => 'Western Mountaineering Alpinlite Sleeping Bag', 'weight' => 907, 'category' => 'Sleep', 'brand' => 'Western Mountaineering', 'price' => 495],
        ['name' => 'Therm-a-Rest NeoAir XLite Sleeping Pad', 'weight' => 340, 'category' => 'Sleep', 'brand' => 'Therm-a-Rest', 'price' => 140],
        ['name' => 'MSR PocketRocket 2 Stove', 'weight' => 73, 'category' => 'Cooking', 'brand' => 'MSR', 'price' => 50],
        ['name' => 'Toaks Titanium 750ml Pot', 'weight' => 86, 'category' => 'Cooking', 'brand' => 'Toaks', 'price' => 35],
        ['name' => 'Sawyer Squeeze Water Filter', 'weight' => 85, 'category' => 'Hydration', 'brand' => 'Sawyer', 'price' => 37],
        ['name' => 'Smartwater 1L Bottle', 'weight' => 38, 'category' => 'Hydration', 'brand' => 'Smartwater', 'price' => 2],
        ['name' => 'Patagonia Houdini Jacket', 'weight' => 105, 'category' => 'Clothing', 'brand' => 'Patagonia', 'price' => 99],
        ['name' => 'Darn Tough Hiker Socks', 'weight' => 72, 'category' => 'Clothing', 'brand' => 'Darn Tough', 'price' => 24],
        ['name' => 'Black Diamond Spot 350 Headlamp', 'weight' => 86, 'category' => 'Electronics', 'brand' => 'Black Diamond', 'price' => 40],
        ['name' => 'Garmin inReach Mini 2', 'weight' => 100, 'category' => 'Electronics', 'brand' => 'Garmin', 'price' => 400],
        ['name' => 'Sea to Summit Aeros Pillow', 'weight' => 60, 'category' => 'Sleep', 'brand' => 'Sea to Summit', 'price' => 43],
        ['name' => 'Leatherman Squirt PS4', 'weight' => 56, 'category' => 'Tools', 'brand' => 'Leatherman', 'price' => 40],
        ['name' => 'First Aid Kit', 'weight' => 200, 'category' => 'Safety', 'brand' => 'Adventure Medical', 'price' => 30],
        ['name' => 'Trekking Poles', 'weight' => 480, 'category' => 'Hiking', 'brand' => 'Black Diamond', 'price' => 140],
        ['name' => 'Bear Canister', 'weight' => 935, 'category' => 'Food Storage', 'brand' => 'BearVault', 'price' => 80],
        ['name' => 'Merino Wool Base Layer', 'weight' => 180, 'category' => 'Clothing', 'brand' => 'Smartwool', 'price' => 85],
        ['name' => 'Rain Pants', 'weight' => 290, 'category' => 'Clothing', 'brand' => 'Outdoor Research', 'price' => 100],
        ['name' => 'Puffy Jacket', 'weight' => 340, 'category' => 'Clothing', 'brand' => 'Patagonia', 'price' => 280]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO gear_items (name, weight, category, brand, price) VALUES (?, ?, ?, ?, ?)');
    foreach ($gearItems as $item) {
        $stmt->execute([$item['name'], $item['weight'], $item['category'], $item['brand'], $item['price']]);
    }
    echo "Inserted " . count($gearItems) . " gear items\n";
    
    // Insert backpacks
    $backpacks = [
        [
            'name' => 'Weekend Warrior',
            'description' => 'Perfect 2-3 day backpacking setup for moderate trails',
            'capacity' => 65,
            'base_weight' => 4500
        ],
        [
            'name' => 'Ultralight Setup',
            'description' => 'Minimalist gear for fast and light adventures',
            'capacity' => 45,
            'base_weight' => 3200
        ],
        [
            'name' => 'Winter Explorer',
            'description' => 'Cold weather gear setup for snow camping',
            'capacity' => 75,
            'base_weight' => 6800
        ],
        [
            'name' => 'Day Hiker',
            'description' => 'Lightweight setup for single day adventures',
            'capacity' => 25,
            'base_weight' => 2000
        ]
    ];
    
    $stmt = $pdo->prepare('INSERT INTO backpacks (name, description, capacity, base_weight) VALUES (?, ?, ?, ?)');
    $backpackIds = [];
    foreach ($backpacks as $pack) {
        $stmt->execute([$pack['name'], $pack['description'], $pack['capacity'], $pack['base_weight']]);
        $backpackIds[] = $pdo->lastInsertId();
    }
    echo "Inserted " . count($backpacks) . " backpacks\n";
    
    // Add gear to backpacks
    // Weekend Warrior gets a typical setup
    $weekendGear = [
        [1, 1, 'main'],  // Backpack
        [2, 1, 'main'],  // Tent
        [3, 1, 'main'],  // Sleeping bag
        [4, 1, 'main'],  // Sleeping pad
        [5, 1, 'lid'],   // Stove
        [6, 1, 'lid'],   // Pot
        [7, 1, 'side'],  // Water filter
        [8, 2, 'side'],  // Water bottles
        [11, 1, 'lid'],  // Headlamp
        [15, 1, 'lid'],  // First aid
        [16, 2, 'external'] // Trekking poles
    ];
    
    $stmt = $pdo->prepare('INSERT INTO backpack_gear (backpack_id, gear_id, quantity, section) VALUES (?, ?, ?, ?)');
    foreach ($weekendGear as $gear) {
        $stmt->execute([$backpackIds[0], $gear[0], $gear[1], $gear[2]]);
    }
    
    // Ultralight setup
    $ultralightGear = [
        [2, 1, 'main'],  // Tent
        [3, 1, 'main'],  // Sleeping bag
        [4, 1, 'main'],  // Sleeping pad
        [5, 1, 'lid'],   // Stove
        [6, 1, 'lid'],   // Pot
        [7, 1, 'side'],  // Water filter
        [8, 1, 'side'],  // Water bottle
        [11, 1, 'lid']   // Headlamp
    ];
    
    foreach ($ultralightGear as $gear) {
        $stmt->execute([$backpackIds[1], $gear[0], $gear[1], $gear[2]]);
    }
    echo "Added gear to backpacks\n";
    
    // Insert trips
    $trips = [
        [
            'title' => 'Yosemite Valley Loop',
            'location' => 'Yosemite National Park, CA',
            'start_date' => '2025-10-15',
            'end_date' => '2025-10-18',
            'distance' => 42.5,
            'distance_unit' => 'miles',
            'elevation_gain' => 4500,
            'difficulty' => 'moderate',
            'trip_type' => 'weekend',
            'description' => 'Classic loop through Yosemite Valley with stops at Half Dome and Nevada Falls',
            'backpack_id' => $backpackIds[0],
            'completed' => 0,
            'favorite' => 1
        ],
        [
            'title' => 'PCT Section Hike',
            'location' => 'Sierra Nevada, CA',
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-14',
            'distance' => 178,
            'distance_unit' => 'miles',
            'elevation_gain' => 28000,
            'difficulty' => 'hard',
            'trip_type' => 'section_hike',
            'description' => 'Two week section hike through the John Muir Wilderness',
            'backpack_id' => $backpackIds[1],
            'completed' => 1,
            'favorite' => 1
        ],
        [
            'title' => 'Local Trail Day Hike',
            'location' => 'Bear Mountain State Park',
            'start_date' => '2025-09-05',
            'end_date' => '2025-09-05',
            'distance' => 8.2,
            'distance_unit' => 'miles',
            'elevation_gain' => 1200,
            'difficulty' => 'easy',
            'trip_type' => 'day_hike',
            'description' => 'Quick day hike to test new gear',
            'backpack_id' => $backpackIds[3],
            'completed' => 1,
            'favorite' => 0
        ]
    ];
    
    $stmt = $pdo->prepare('
        INSERT INTO trips (
            title, location, start_date, end_date, distance, distance_unit,
            elevation_gain, difficulty, trip_type, description, backpack_id,
            completed, favorite
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    foreach ($trips as $trip) {
        $stmt->execute([
            $trip['title'], $trip['location'], $trip['start_date'], $trip['end_date'],
            $trip['distance'], $trip['distance_unit'], $trip['elevation_gain'],
            $trip['difficulty'], $trip['trip_type'], $trip['description'],
            $trip['backpack_id'], $trip['completed'], $trip['favorite']
        ]);
    }
    echo "Inserted " . count($trips) . " trips\n";
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n✅ Test data seeded successfully!\n";
    
    // Display summary
    $backpackCount = $pdo->query("SELECT COUNT(*) FROM backpacks")->fetchColumn();
    $tripCount = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
    $gearCount = $pdo->query("SELECT COUNT(*) FROM gear_items")->fetchColumn();
    
    echo "\nDatabase Summary:\n";
    echo "- Backpacks: $backpackCount\n";
    echo "- Trips: $tripCount\n";
    echo "- Gear Items: $gearCount\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
