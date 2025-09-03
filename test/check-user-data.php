<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = new PDO('sqlite:' . BASE_PATH . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== EXISTING TRIPS ===\n";
    $stmt = $db->query("SELECT id, title, user_id FROM trips");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trips as $trip) {
        echo sprintf("Trip #%d: %s (user_id: %s)\n", 
            $trip['id'], 
            $trip['title'],
            $trip['user_id'] ?: 'NULL'
        );
    }
    
    echo "\n=== EXISTING USERS ===\n";
    $stmt = $db->query("SELECT id, username, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo sprintf("User #%d: %s (%s)\n", 
            $user['id'], 
            $user['username'],
            $user['email']
        );
    }
    
    // Get first admin user
    $stmt = $db->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser) {
        echo "\n=== ASSIGNING ORPHANED DATA TO ADMIN USER ===\n";
        
        // Check for trips without user_id
        $stmt = $db->query("SELECT COUNT(*) as count FROM trips WHERE user_id IS NULL OR user_id = 0");
        $orphanedTrips = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($orphanedTrips > 0) {
            echo "Found $orphanedTrips orphaned trips. Assigning to admin user...\n";
            $stmt = $db->prepare("UPDATE trips SET user_id = ? WHERE user_id IS NULL OR user_id = 0");
            $stmt->execute([$adminUser['id']]);
            echo "Updated $orphanedTrips trips.\n";
        } else {
            echo "No orphaned trips found.\n";
        }
        
        // Check for backpacks without user_id
        $stmt = $db->query("SELECT COUNT(*) as count FROM backpacks WHERE user_id IS NULL OR user_id = 0");
        $orphanedBackpacks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($orphanedBackpacks > 0) {
            echo "Found $orphanedBackpacks orphaned backpacks. Assigning to admin user...\n";
            $stmt = $db->prepare("UPDATE backpacks SET user_id = ? WHERE user_id IS NULL OR user_id = 0");
            $stmt->execute([$adminUser['id']]);
            echo "Updated $orphanedBackpacks backpacks.\n";
        } else {
            echo "No orphaned backpacks found.\n";
        }
    } else {
        echo "\nNo admin user found. Orphaned data will remain unassigned.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
