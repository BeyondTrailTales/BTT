<?php
/**
 * Test to check what user the frontend session sees
 */

session_start();
require_once __DIR__ . '/../app/config.php';

echo "=== Frontend Session Test ===\n\n";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "✓ User is logged in\n";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'not set') . "\n";
    echo "Email: " . ($_SESSION['email'] ?? 'not set') . "\n";
    echo "Session ID: " . session_id() . "\n";
    
    // Check database for this user's backpacks
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/../storage/sqlite/database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nBackpacks for this user: " . $result['count'] . "\n";
        
        // List backpacks
        $stmt = $db->prepare("SELECT id, name, created_at FROM backpacks WHERE user_id = ? ORDER BY id");
        $stmt->execute([$_SESSION['user_id']]);
        $backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($backpacks) {
            echo "\nBackpack list:\n";
            foreach ($backpacks as $pack) {
                echo "  - ID: {$pack['id']}, Name: {$pack['name']}, Created: {$pack['created_at']}\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ No user is logged in\n";
    echo "Session ID: " . session_id() . "\n";
    
    // Check if there's any session data at all
    echo "\nAll session data:\n";
    print_r($_SESSION);
}

echo "\n=== Cookie Check ===\n";
foreach ($_COOKIE as $name => $value) {
    echo "$name: $value\n";
}
