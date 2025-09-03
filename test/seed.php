<?php
/**
 * Seed Script - Populate sample data for testing
 * Run: php test/seed.php or visit http://localhost/BTT/test/seed.php
 */

require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

header('Content-Type: text/plain');

echo "BeyondTrailTales - Seed Script\n";
echo "==============================\n\n";

try {
    $db = Database::getInstance();
    
    if ($db->isSQLite()) {
        echo "Using SQLite database\n";
        
        // Create tables if needed
        require_once dirname(__DIR__) . '/api/setup.php';
        $setup_result = setupDatabase();
        echo "Database setup: " . $setup_result['message'] . "\n\n";
    } else {
        echo "Using JSON storage\n\n";
    }
    
    // Sample backpacks
    $backpacks = [
        [
            'name' => 'Day Hike Pack',
            'description' => 'Light pack for day trips',
            'base_weight' => 2.5,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'Weekend Adventure',
            'description' => 'Perfect for 2-3 day trips',
            'base_weight' => 5.8,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $backpack_ids = [];
    
    echo "Creating backpacks...\n";
    foreach ($backpacks as $backpack) {
        $id = $db->insert('backpacks', $backpack);
        $backpack_ids[] = $id;
        echo "  Created: {$backpack['name']} (ID: $id)\n";
    }
    
    echo "\n";
    
    // Sample trips
    $trips = [
        [
            'title' => 'Mount Rainier Day Hike',
            'location' => 'Mount Rainier National Park, WA',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'description' => 'Beautiful summer day hike to see wildflowers',
            'photo_path' => null,
            'photo_alt_text' => null,
            'backpack_id' => $backpack_ids[0],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'title' => 'Olympic Coast Backpacking',
            'location' => 'Olympic National Park, WA',
            'start_date' => date('Y-m-d', strtotime('+14 days')),
            'end_date' => date('Y-m-d', strtotime('+16 days')),
            'description' => 'Three day coastal backpacking adventure',
            'photo_path' => null,
            'photo_alt_text' => null,
            'backpack_id' => $backpack_ids[1],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo "Creating trips...\n";
    foreach ($trips as $trip) {
        $id = $db->insert('trips', $trip);
        echo "  Created: {$trip['title']} (ID: $id)\n";
    }
    
    echo "\n";
    echo "Seed completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Visit http://localhost/BTT/public/ to see the home page\n";
    echo "2. Visit http://localhost/BTT/public/backpacks.php to manage backpacks\n";
    echo "3. Visit http://localhost/BTT/public/trips.php to manage trips\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
