<?php
// Reset admin password
require_once __DIR__ . '/app/bootstrap.php';

try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find admin user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin' OR email LIKE '%admin%' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Reset password to admin123
        $newHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute(['hash' => $newHash, 'id' => $admin['id']]);
        
        echo "✅ Admin password reset successfully!<br>";
        echo "Username: " . $admin['username'] . "<br>";
        echo "Email: " . $admin['email'] . "<br>";
        echo "Password: admin123<br><br>";
        echo '<a href="/BTT/public/auth/login.php">Go to Login</a>';
    } else {
        echo "❌ No admin user found";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
