<?php
session_start();
header('Content-Type: text/plain');

echo "=== MANUAL LOGIN TEST ===\n";

require_once __DIR__ . '/app/config.php';

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check for any user (without password column first)
    $stmt = $db->query("SELECT * FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found user:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        
        echo "\nBEFORE login session:\n";
        print_r($_SESSION);
        
        // Manually set session like the auth system would
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        
        echo "\nAFTER login session:\n";
        print_r($_SESSION);
        
        echo "\n✅ Manual login successful!\n";
        echo "You should now be able to access http://localhost/BTT/debug-photo-removal.php\n";
        echo "Or try http://localhost/BTT/trips.php\n";
        
    } else {
        echo "❌ No users found in database\n";
        
        // Create a test user (determine columns first)
        echo "\nChecking users table schema...\n";
        $stmt = $db->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasPassword = false;
        $columnNames = [];
        foreach ($columns as $col) {
            $columnNames[] = $col['name'];
            if ($col['name'] === 'password') $hasPassword = true;
        }
        
        echo "Available columns: " . implode(', ', $columnNames) . "\n";
        
        echo "\nCreating test user...\n";
        if ($hasPassword) {
            $password_hash = password_hash('test123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, datetime('now'))");
            $result = $stmt->execute(['testuser', 'test@example.com', $password_hash]);
        } else {
            // Just create with basic fields
            $stmt = $db->prepare("INSERT INTO users (username, email, created_at) VALUES (?, ?, datetime('now'))");
            $result = $stmt->execute(['testuser', 'test@example.com']);
        }
        
        if ($result) {
            $user_id = $db->lastInsertId();
            echo "✅ Created test user with ID: $user_id\n";
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = 'testuser';
            $_SESSION['email'] = 'test@example.com';
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();
            
            echo "✅ Logged in as test user\n";
            echo "Login: testuser / test123\n";
        } else {
            echo "❌ Failed to create test user\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>