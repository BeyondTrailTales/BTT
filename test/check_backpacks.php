<?php
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

try {
    $db = Database::getInstance();
    
    // Check backpacks
    echo "Checking backpacks table...\n";
    $backpacks = $db->fetchAll('SELECT id, name FROM backpacks');
    echo "Backpacks in DB (" . count($backpacks) . "):\n";
    foreach ($backpacks as $bp) {
        echo "  - ID: {$bp['id']}, Name: {$bp['name']}\n";
    }
    
    if (count($backpacks) === 0) {
        echo "\nNo backpacks found! Creating a default backpack...\n";
        
        // Create a default backpack
        $id = $db->insert('backpacks', [
            'name' => 'My First Backpack',
            'description' => 'Default backpack for testing',
            'capacity' => 65,
            'capacity_unit' => 'liters',
            'base_weight' => 0,
            'target_weight' => 10,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "Created backpack with ID: $id\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
