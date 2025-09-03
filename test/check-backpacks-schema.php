<?php
/**
 * Check and fix backpacks table schema for user scoping
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');
    
    echo "=== Checking Backpacks Table Schema ===\n\n";
    
    // Get table structure
    $stmt = $db->query("PRAGMA table_info(backpacks)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns in backpacks table:\n";
    $hasUserId = false;
    foreach ($columns as $column) {
        echo sprintf("  - %s (%s)%s%s\n", 
            $column['name'], 
            $column['type'],
            $column['notnull'] ? ' NOT NULL' : '',
            $column['dflt_value'] !== null ? ' DEFAULT ' . $column['dflt_value'] : ''
        );
        if ($column['name'] === 'user_id') {
            $hasUserId = true;
        }
    }
    
    if (!$hasUserId) {
        echo "\n❌ user_id column is missing! Adding it now...\n";
        
        // Add user_id column with default value
        $db->exec("ALTER TABLE backpacks ADD COLUMN user_id INTEGER DEFAULT 1");
        
        // Create index for better performance
        $db->exec("CREATE INDEX IF NOT EXISTS idx_backpacks_user_id ON backpacks(user_id)");
        
        echo "✅ Added user_id column and index\n";
    } else {
        echo "\n✅ user_id column exists\n";
    }
    
    // Check indexes
    echo "\n=== Indexes on backpacks table ===\n";
    $stmt = $db->query("PRAGMA index_list(backpacks)");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexes as $index) {
        echo "  - " . $index['name'] . "\n";
    }
    
    // Check current data
    echo "\n=== Current Backpacks Data ===\n";
    $stmt = $db->query("SELECT id, name, user_id, created_at FROM backpacks ORDER BY created_at DESC LIMIT 10");
    $backpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($backpacks)) {
        echo "No backpacks found in database.\n";
    } else {
        echo "Found " . count($backpacks) . " backpack(s):\n";
        foreach ($backpacks as $bp) {
            echo sprintf("  - ID: %d, Name: %s, User: %d, Created: %s\n",
                $bp['id'],
                $bp['name'],
                $bp['user_id'] ?? 'NULL',
                $bp['created_at'] ?? 'Unknown'
            );
        }
    }
    
    // Check if we need to update NULL user_ids
    $stmt = $db->query("SELECT COUNT(*) as count FROM backpacks WHERE user_id IS NULL OR user_id = 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "\n⚠️ Found {$result['count']} backpack(s) without proper user_id\n";
        
        // Get a valid user ID to assign orphaned backpacks
        $stmt = $db->query("SELECT id FROM users ORDER BY id LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "Assigning orphaned backpacks to user ID: {$user['id']}\n";
            $stmt = $db->prepare("UPDATE backpacks SET user_id = :user_id WHERE user_id IS NULL OR user_id = 0");
            $stmt->execute(['user_id' => $user['id']]);
            echo "✅ Updated orphaned backpacks\n";
        } else {
            echo "❌ No users found in database. Please create a user first.\n";
        }
    }
    
    // Test query that will be used by the API
    echo "\n=== Testing User-Scoped Query ===\n";
    
    // Get first user for testing
    $stmt = $db->query("SELECT id, username FROM users ORDER BY id LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        echo "Testing with user: {$testUser['username']} (ID: {$testUser['id']})\n";
        
        $stmt = $db->prepare("
            SELECT 
                b.*,
                COUNT(t.id) as trip_count
            FROM backpacks b
            LEFT JOIN trips t ON b.id = t.backpack_id AND t.user_id = :user_id
            WHERE b.user_id = :user_id
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute(['user_id' => $testUser['id']]);
        $userBackpacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "User has " . count($userBackpacks) . " backpack(s)\n";
        foreach ($userBackpacks as $bp) {
            echo "  - {$bp['name']} (trips: {$bp['trip_count']})\n";
        }
    }
    
    echo "\n=== Schema Check Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
