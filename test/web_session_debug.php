<?php
/**
 * Web-based session debug
 * Access this via browser to check authentication and session
 */
session_start();
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/classes/Database.php';
require_once dirname(__DIR__) . '/app/services/AuthService.php';

// Initialize services
Database::init(BTT_STORAGE . '/sqlite/btt.db', !defined('BTT_DB_TYPE') || BTT_DB_TYPE === 'sqlite');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug - BTT</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a1a;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        pre {
            background: #000;
            padding: 10px;
            border: 1px solid #0f0;
            overflow: auto;
        }
        h2 {
            color: #0ff;
            border-bottom: 2px solid #0ff;
            padding-bottom: 5px;
        }
        .warning {
            color: #ff0;
        }
        .error {
            color: #f00;
        }
        .success {
            color: #0f0;
        }
    </style>
</head>
<body>
    <h1>BTT Session Debug</h1>
    
    <h2>1. Session Info</h2>
    <pre>
Session Name: <?php echo session_name(); ?>

Session ID: <?php echo session_id(); ?>

Session Status: <?php echo session_status(); ?> (0=DISABLED, 1=NONE, 2=ACTIVE)
    </pre>
    
    <h2>2. Session Data</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>3. Authentication Check</h2>
    <pre>
AuthService::isAuthenticated(): <?php echo AuthService::isAuthenticated() ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>'; ?>

<?php if (AuthService::isAuthenticated()): ?>
Current User:
<?php 
    $user = AuthService::getCurrentUser();
    if ($user) {
        echo "  ID: " . $user['id'] . "\n";
        echo "  Username: " . $user['username'] . "\n";
        echo "  Email: " . $user['email'] . "\n";
        echo "  Role: " . $user['role'] . "\n";
    }
?>
<?php else: ?>
<span class="warning">Not authenticated</span>
<?php endif; ?>
    </pre>
    
    <h2>4. Cookie Check</h2>
    <pre>
Session Cookie: <?php echo isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : '<span class="error">Not set</span>'; ?>

All Cookies:
<?php print_r($_COOKIE); ?>
    </pre>
    
    <h2>5. Database Trips</h2>
    <pre>
<?php
$db = Database::getInstance();
if (AuthService::isAuthenticated()) {
    $user = AuthService::getCurrentUser();
    $sql = "SELECT COUNT(*) as count FROM trips WHERE user_id = :user_id";
    $result = $db->fetchOne($sql, ['user_id' => $user['id']]);
    echo "Trips for current user (ID: " . $user['id'] . "): " . $result['count'] . "\n\n";
    
    $sql = "SELECT id, title, user_id FROM trips WHERE user_id = :user_id LIMIT 5";
    $trips = $db->fetchAll($sql, ['user_id' => $user['id']]);
    echo "Recent trips:\n";
    foreach ($trips as $trip) {
        echo "  - [" . $trip['id'] . "] " . $trip['title'] . " (User #" . $trip['user_id'] . ")\n";
    }
} else {
    echo '<span class="warning">Cannot query user trips - not authenticated</span>';
}
?>
    </pre>
    
    <h2>6. All Trips in Database</h2>
    <pre>
<?php
$sql = "SELECT user_id, COUNT(*) as count FROM trips GROUP BY user_id";
$results = $db->fetchAll($sql);
echo "Trips grouped by user:\n";
foreach ($results as $row) {
    echo "  User #" . $row['user_id'] . ": " . $row['count'] . " trips\n";
}
?>
    </pre>
    
    <h2>7. Test Actions</h2>
    <p>
        <?php if (!AuthService::isAuthenticated()): ?>
        <a href="../auth.php?action=login" style="color: #0ff;">Go to Login Page</a>
        <?php else: ?>
        <a href="../trips.php" style="color: #0ff;">Go to Trips Page</a> | 
        <a href="../auth.php?action=logout" style="color: #ff0;">Logout</a>
        <?php endif; ?>
    </p>
</body>
</html>
