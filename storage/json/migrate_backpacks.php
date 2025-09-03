<?php
/**
 * Migration script to update existing backpacks to new schema
 * Run once to migrate existing data
 */

// Backup existing data first
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backpacksFile = __DIR__ . '/backpacks.json';
$gearFile = __DIR__ . '/gear.json';

// Backup existing backpacks
if (file_exists($backpacksFile)) {
    $backupFile = $backupDir . '/backpacks_' . date('Y-m-d_His') . '.json';
    copy($backpacksFile, $backupFile);
    echo "Backed up existing backpacks to: $backupFile\n";
}

// Load existing backpacks
$backpacks = [];
if (file_exists($backpacksFile)) {
    $backpacks = json_decode(file_get_contents($backpacksFile), true) ?? [];
}

// Migrate each backpack to new schema
foreach ($backpacks as &$backpack) {
    // Add missing fields with defaults
    if (!isset($backpack['version'])) {
        $backpack['version'] = '1.0.0';
    }
    
    if (!isset($backpack['capacity_l'])) {
        $backpack['capacity_l'] = 65; // Default 65L backpack
    }
    
    if (!isset($backpack['weight_empty_g'])) {
        // Convert base_weight from kg to grams if exists
        if (isset($backpack['base_weight'])) {
            $backpack['weight_empty_g'] = $backpack['base_weight'] * 1000;
        } else {
            $backpack['weight_empty_g'] = 0;
        }
    }
    
    if (!isset($backpack['type'])) {
        // Guess type based on name
        $name = strtolower($backpack['name'] ?? '');
        if (strpos($name, 'day') !== false) {
            $backpack['type'] = 'day-hike';
        } elseif (strpos($name, 'weekend') !== false) {
            $backpack['type'] = 'weekend';
        } else {
            $backpack['type'] = 'custom';
        }
    }
    
    if (!isset($backpack['tags'])) {
        $backpack['tags'] = [];
    }
    
    if (!isset($backpack['sections'])) {
        // Create default sections
        $backpack['sections'] = [
            [
                'id' => 'main-' . $backpack['id'],
                'name' => 'Main Compartment',
                'order' => 0,
                'capacity_percentage' => 40,
                'color' => '#10b981',
                'items' => []
            ],
            [
                'id' => 'top-' . $backpack['id'],
                'name' => 'Top Lid',
                'order' => 1,
                'capacity_percentage' => 15,
                'color' => '#3b82f6',
                'items' => []
            ],
            [
                'id' => 'side-' . $backpack['id'],
                'name' => 'Side Pockets',
                'order' => 2,
                'capacity_percentage' => 15,
                'color' => '#8b5cf6',
                'items' => []
            ],
            [
                'id' => 'front-' . $backpack['id'],
                'name' => 'Front Mesh',
                'order' => 3,
                'capacity_percentage' => 10,
                'color' => '#f59e0b',
                'items' => []
            ],
            [
                'id' => 'hip-' . $backpack['id'],
                'name' => 'Hip Belt',
                'order' => 4,
                'capacity_percentage' => 10,
                'color' => '#ef4444',
                'items' => []
            ],
            [
                'id' => 'external-' . $backpack['id'],
                'name' => 'External Attachments',
                'order' => 5,
                'capacity_percentage' => 10,
                'color' => '#14b8a6',
                'items' => []
            ]
        ];
    }
    
    if (!isset($backpack['linked_trip_id'])) {
        $backpack['linked_trip_id'] = null;
    }
    
    if (!isset($backpack['template_id'])) {
        $backpack['template_id'] = null;
    }
}

// Save migrated backpacks
file_put_contents($backpacksFile, json_encode($backpacks, JSON_PRETTY_PRINT));
echo "Migrated " . count($backpacks) . " backpacks to new schema\n";

// Create gear inventory if doesn't exist
if (!file_exists($gearFile)) {
    $defaultGear = [
        [
            'id' => 1,
            'name' => 'Osprey Atmos AG 65',
            'category' => 'other',
            'weight_g' => 2130,
            'brand' => 'Osprey',
            'model' => 'Atmos AG 65',
            'notes' => '65L capacity with Anti-Gravity suspension',
            'tags' => ['backpack', 'multi-day'],
            'quantity_owned' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'name' => 'MSR PocketRocket 2',
            'category' => 'cooking',
            'weight_g' => 73,
            'brand' => 'MSR',
            'model' => 'PocketRocket 2',
            'notes' => 'Ultralight canister stove',
            'tags' => ['stove', 'ultralight'],
            'quantity_owned' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'name' => 'Sawyer Squeeze',
            'category' => 'water',
            'weight_g' => 85,
            'brand' => 'Sawyer',
            'model' => 'Squeeze',
            'notes' => 'Water filter system',
            'tags' => ['filter', 'essential'],
            'quantity_owned' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 4,
            'name' => 'Therm-a-Rest NeoAir XLite',
            'category' => 'sleep_system',
            'weight_g' => 355,
            'brand' => 'Therm-a-Rest',
            'model' => 'NeoAir XLite',
            'notes' => 'Ultralight inflatable sleeping pad',
            'tags' => ['sleeping-pad', 'ultralight'],
            'quantity_owned' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 5,
            'name' => 'REI Co-op Down Jacket',
            'category' => 'clothing',
            'weight_g' => 340,
            'brand' => 'REI Co-op',
            'model' => '650 Down Jacket',
            'notes' => 'Packable insulation layer',
            'tags' => ['insulation', 'packable'],
            'quantity_owned' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    file_put_contents($gearFile, json_encode($defaultGear, JSON_PRETTY_PRINT));
    echo "Created gear inventory with " . count($defaultGear) . " default items\n";
}

echo "Migration complete!\n";
