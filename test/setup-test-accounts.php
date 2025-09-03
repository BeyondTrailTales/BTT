<?php
/**
 * Setup Test Accounts
 * Creates test accounts for development
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

echo "Test Account Setup\n";
echo "==================\n\n";

// Check existing users
$db = new PDO('sqlite:' . dirname(__DIR__) . '/storage/sqlite/btt.db');
$stmt = $db->query('SELECT id, email, username FROM users ORDER BY id');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Existing users:\n";
if (empty($users)) {
    echo "  No users found\n";
} else {
    foreach ($users as $user) {
        echo "  - {$user['username']} ({$user['email']})\n";
    }
}

echo "\n";

// Test accounts to create
$testAccounts = [
    [
        'email' => 'admin@btt.local',
        'username' => 'admin',
        'password' => 'Admin123!',
        'description' => 'Admin account'
    ],
    [
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => 'Test123!',
        'description' => 'Test user account'
    ],
    [
        'email' => 'demo@example.com',
        'username' => 'demo',
        'password' => 'Demo123!',
        'description' => 'Demo account'
    ]
];

echo "Creating/Updating test accounts:\n";
echo "---------------------------------\n";

foreach ($testAccounts as $account) {
    echo "\n{$account['description']}:\n";
    echo "  Email: {$account['email']}\n";
    echo "  Username: {$account['username']}\n";
    echo "  Password: {$account['password']}\n";
    
    // Check if user exists
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email OR username = :username');
    $stmt->execute(['email' => $account['email'], 'username' => $account['username']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update password for existing user
        echo "  Status: User exists, updating password...\n";
        $passwordHash = password_hash($account['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash, email_verified_at = CURRENT_TIMESTAMP WHERE email = :email OR username = :username');
        $stmt->execute([
            'hash' => $passwordHash,
            'email' => $account['email'],
            'username' => $account['username']
        ]);
        echo "  ✓ Password updated\n";
    } else {
        // Create new user
        echo "  Status: Creating new user...\n";
        $result = AuthService::register($account['email'], $account['username'], $account['password']);
        if ($result['success']) {
            echo "  ✓ User created (ID: {$result['user_id']})\n";
        } else {
            echo "  ✗ Failed: {$result['message']}\n";
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $field => $error) {
                    echo "    - {$field}: {$error}\n";
                }
            }
        }
    }
}

echo "\n";
echo "========================================\n";
echo "You can now login with any of these accounts!\n";
echo "\nRecommended test account:\n";
echo "  Username: admin\n";
echo "  Password: Admin123!\n";
echo "\nLogin at: http://localhost/BTT/public/auth/login.php\n";
?>
