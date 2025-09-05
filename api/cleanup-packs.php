<?php
/**
 * Cleanup duplicate and stuck backpacks
 */

// Include required files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Response.php';

// Require authentication
use App\Services\AuthService;
if (!AuthService::isAuthenticated()) {
    die('Authentication required');
}

$user = AuthService::getCurrentUser();
$db = Database::getInstance();

echo "<h2>Backpack Cleanup Utility</h2>";
echo "<pre>";

try {
    // 1. List all backpacks for current user
    echo "Fetching backpacks for user ID: " . $user['id'] . "\n";
    $sql = "SELECT id, name, description, created_at, total_items FROM backpacks WHERE user_id = :user_id ORDER BY created_at DESC";
    $packs = $db->fetchAll($sql, ['user_id' => $user['id']]);
    
    echo "Found " . count($packs) . " backpacks:\n\n";
    
    // 2. Display all packs
    foreach ($packs as $pack) {
        echo "ID: {$pack['id']} | Name: {$pack['name']} | Created: {$pack['created_at']} | Items: {$pack['total_items']}\n";
    }
    
    // 3. Find duplicates (same name, created within 5 seconds of each other)
    echo "\n\nChecking for duplicates...\n";
    $duplicates = [];
    
    for ($i = 0; $i < count($packs) - 1; $i++) {
        for ($j = $i + 1; $j < count($packs); $j++) {
            if ($packs[$i]['name'] === $packs[$j]['name']) {
                $time1 = strtotime($packs[$i]['created_at']);
                $time2 = strtotime($packs[$j]['created_at']);
                
                if (abs($time1 - $time2) < 5) { // Within 5 seconds
                    $duplicates[] = $packs[$j]['id'];
                    echo "Found duplicate: Pack ID {$packs[$j]['id']} is a duplicate of {$packs[$i]['id']}\n";
                }
            }
        }
    }
    
    // 4. Delete duplicates if found
    if (count($duplicates) > 0) {
        echo "\n\nDeleting " . count($duplicates) . " duplicate packs...\n";
        
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            foreach ($duplicates as $dupId) {
                // Delete associated items first
                $db->delete('backpack_gear', 'backpack_id = :id', ['id' => $dupId]);
                
                // Delete the backpack
                $deleted = $db->delete('backpacks', 'id = :id AND user_id = :user_id', [
                    'id' => $dupId,
                    'user_id' => $user['id']
                ]);
                
                echo "Deleted pack ID: $dupId\n";
            }
            echo "\n<strong>Cleanup complete!</strong>\n";
        } else {
            echo "\n<strong>To delete these duplicates, add '?confirm=yes' to the URL</strong>\n";
        }
    } else {
        echo "\nNo duplicates found.\n";
    }
    
    // 5. Look for specific problematic packs
    if (isset($_GET['delete_id'])) {
        $deleteId = intval($_GET['delete_id']);
        echo "\n\nAttempting to delete pack ID: $deleteId\n";
        
        if (isset($_GET['force']) && $_GET['force'] === 'yes') {
            // Force delete - remove all references first
            $db->query("DELETE FROM backpack_gear WHERE backpack_id = :id", ['id' => $deleteId]);
            $db->query("DELETE FROM backpacks WHERE id = :id AND user_id = :user_id", [
                'id' => $deleteId,
                'user_id' => $user['id']
            ]);
            echo "Force deleted pack ID: $deleteId\n";
        } else {
            echo "To force delete, add '&force=yes' to the URL\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<hr>";
echo "<h3>Options:</h3>";
echo "<ul>";
echo "<li><a href='?confirm=yes'>Delete all duplicates</a></li>";
echo "<li>To delete a specific pack: Add ?delete_id=XX to the URL</li>";
echo "<li>To force delete: Add &force=yes after delete_id</li>";
echo "</ul>";
?>
