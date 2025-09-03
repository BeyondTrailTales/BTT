<?php
/**
 * Setup Users - Create test users for BTT
 */
require_once __DIR__ . '/app/bootstrap.php';
use App\Services\AuthService;

// Security check - only run from command line or localhost
if (php_sapi_name() !== 'cli' && (!isset($_SERVER['HTTP_HOST']) || !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']))) {
    die('Access denied');
}

$messages = [];
$errors = [];

try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if users table exists
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if (!$tableCheck) {
        $errors[] = "Users table doesn't exist. Run migrations first.";
    } else {
        // Check existing users
        $existingUsers = $db->query("SELECT id, email, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $messages[] = "Existing users: " . count($existingUsers);
        
        // Create admin user if doesn't exist
        $adminEmail = 'admin@example.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $adminEmail]);
        
        if (!$stmt->fetch()) {
            // Register admin user
            $result = AuthService::register($adminEmail, 'admin', 'admin123');
            if ($result['success']) {
                $messages[] = "✅ Admin user created: admin@example.com / admin123";
                
                // Set as verified
                $db->exec("UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE email = '$adminEmail'");
                $messages[] = "✅ Admin email verified";
            } else {
                $errors[] = "Failed to create admin: " . json_encode($result);
            }
        } else {
            $messages[] = "ℹ️ Admin user already exists";
            
            // Reset password for admin
            if (isset($_GET['reset'])) {
                $passwordHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
                $stmt->execute(['hash' => $passwordHash, 'email' => $adminEmail]);
                $messages[] = "✅ Admin password reset to: admin123";
            }
        }
        
        // Create regular test user
        $testEmail = 'user@example.com';
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $testEmail]);
        
        if (!$stmt->fetch()) {
            $result = AuthService::register($testEmail, 'testuser', 'test123');
            if ($result['success']) {
                $messages[] = "✅ Test user created: user@example.com / test123";
                
                // Set as verified
                $db->exec("UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE email = '$testEmail'");
                $messages[] = "✅ Test user email verified";
            } else {
                $errors[] = "Failed to create test user: " . json_encode($result);
            }
        } else {
            $messages[] = "ℹ️ Test user already exists";
        }
        
        // Add sample data for admin
        if (isset($_GET['sample'])) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $adminEmail]);
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($adminUser) {
                $userId = $adminUser['id'];
                
                // Check if admin has backpacks
                $backpackCount = $db->query("SELECT COUNT(*) FROM backpacks WHERE user_id = $userId")->fetchColumn();
                
                if ($backpackCount == 0) {
                    // Add sample backpacks
                    $db->exec("
                        INSERT INTO backpacks (name, description, base_weight, user_id, created_at, updated_at)
                        VALUES 
                        ('Day Hike Pack', 'Light pack for day trips', 1.2, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
                        ('Weekend Pack', 'Medium pack for 2-3 day trips', 2.5, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
                        ('Thru-Hike Pack', 'Ultralight setup for long trails', 3.8, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $messages[] = "✅ Added 3 sample backpacks for admin";
                }
                
                // Check if admin has trips
                $tripCount = $db->query("SELECT COUNT(*) FROM trips WHERE user_id = $userId")->fetchColumn();
                
                if ($tripCount == 0) {
                    // Get a backpack ID
                    $backpackId = $db->query("SELECT id FROM backpacks WHERE user_id = $userId LIMIT 1")->fetchColumn();
                    
                    // Add sample trips
                    $db->exec("
                        INSERT INTO trips (title, location, start_date, end_date, distance, elevation_gain, difficulty, trip_type, status, backpack_id, user_id, created_at, updated_at)
                        VALUES 
                        ('PCT Section Hike', 'Pacific Crest Trail - Section J', '2024-07-15', '2024-07-20', 75.5, 12000, 'hard', 'backpacking', 'completed', $backpackId, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
                        ('Mount Whitney', 'Sierra Nevada, CA', '2024-08-10', '2024-08-12', 22, 6100, 'hard', 'backpacking', 'planned', $backpackId, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
                        ('Yosemite Valley Loop', 'Yosemite National Park', '2024-06-01', '2024-06-01', 7.2, 800, 'easy', 'day_hike', 'completed', NULL, $userId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $messages[] = "✅ Added 3 sample trips for admin";
                }
            }
        }
        
        // Show all users
        $allUsers = $db->query("SELECT id, email, username, email_verified_at, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $messages[] = "\n<strong>All Users in Database:</strong>";
        foreach ($allUsers as $user) {
            $verified = $user['email_verified_at'] ? '✅' : '❌';
            $messages[] = "• {$user['username']} ({$user['email']}) $verified ID: {$user['id']}";
        }
    }
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Users - BTT</title>
    <style>
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 20px; }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 6px;
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success { 
            background: #d4edda; 
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .btn:hover { opacity: 0.9; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; }
        pre { 
            background: #f4f5f7; 
            padding: 15px; 
            border-radius: 6px; 
            overflow-x: auto; 
        }
        .login-box {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 BTT User Setup</h1>
        
        <?php foreach ($errors as $error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($messages as $message): ?>
            <div class="message"><?= $message ?></div>
        <?php endforeach; ?>
        
        <div class="login-box">
            <h3>📝 Test Credentials:</h3>
            <pre>
Admin User:
  Email: admin@example.com
  Password: admin123

Test User:
  Email: user@example.com  
  Password: test123
            </pre>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Actions:</h3>
            <a href="?reset=1" class="btn btn-warning">Reset Admin Password</a>
            <a href="?sample=1" class="btn btn-success">Add Sample Data</a>
            <a href="<?= BTT_PUBLIC_URL ?>/auth/login.php" class="btn">Go to Login</a>
            <a href="<?= BTT_PUBLIC_URL ?>/fix-all-issues.php" class="btn">System Test Page</a>
        </div>
    </div>
</body>
</html>
