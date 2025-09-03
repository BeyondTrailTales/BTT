<?php
/**
 * Fix sessions table issue
 */

$dbPath = dirname(__DIR__) . '/storage/sqlite/btt.db';

echo "Checking sessions table...\n\n";

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if sessions table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sessions'");
    $table = $result->fetch();
    
    if (!$table) {
        echo "Sessions table does not exist. Creating it...\n";
        
        // Create sessions table
        $sql = "
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INTEGER NULL,
                payload TEXT NOT NULL,
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                last_activity INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        $db->exec($sql);
        
        // Create index
        $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity)");
        
        echo "Sessions table created successfully!\n";
    } else {
        echo "Sessions table exists. Checking structure...\n";
        
        // Get table info
        $result = $db->query("PRAGMA table_info(sessions)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['name'] . " (" . $col['type'] . ")\n";
        }
        
        // Check for any sessions
        $count = $db->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
        echo "\nTotal sessions in table: $count\n";
        
        if ($count > 0) {
            // Clean up old sessions
            $deleted = $db->exec("DELETE FROM sessions WHERE last_activity < " . (time() - 7200));
            echo "Cleaned up $deleted old sessions\n";
        }
    }
    
    // Test write
    echo "\nTesting session write...\n";
    $testId = 'test_' . uniqid();
    $stmt = $db->prepare("INSERT INTO sessions (id, payload, last_activity, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $testId,
        'test_data',
        time(),
        '127.0.0.1',
        'Test Agent'
    ]);
    
    if ($result) {
        echo "Test write successful!\n";
        
        // Clean up test
        $db->exec("DELETE FROM sessions WHERE id = '$testId'");
        echo "Test session cleaned up\n";
    } else {
        echo "Test write failed!\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
