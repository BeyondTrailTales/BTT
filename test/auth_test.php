<?php
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    setcookie('BTTSESSID', '', time() - 3600, '/');
    header('Location: auth_test.php?message=logged_out');
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $_SESSION['user_id'] = $_POST['user_id'];
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['email'] = $_POST['email'] ?? 'test@example.com';
    $_SESSION['authenticated'] = true;
    header('Location: auth_test.php?message=logged_in');
    exit();
}

$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['authenticated'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BTT Authentication Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .logged-in {
            background: #155724;
            border: 1px solid #4CAF50;
        }
        .logged-out {
            background: #721c24;
            border: 1px solid #f5c6cb;
        }
        form {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            background: #333;
            color: #fff;
            border: 1px solid #555;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            margin: 10px 5px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .danger {
            background: #f44336;
        }
        .danger:hover {
            background: #da190b;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
            margin: 0 10px;
        }
        a:hover {
            text-decoration: underline;
        }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
        .message {
            padding: 10px;
            background: #333;
            border-left: 4px solid #4CAF50;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>BTT Authentication Test</h1>
    
    <?php if (isset($_GET['message'])): ?>
    <div class="message">
        <?php 
        if ($_GET['message'] === 'logged_out') echo '✓ Successfully logged out';
        if ($_GET['message'] === 'logged_in') echo '✓ Successfully logged in';
        ?>
    </div>
    <?php endif; ?>
    
    <div class="status <?php echo $isLoggedIn ? 'logged-in' : 'logged-out'; ?>">
        <h2>Current Status: <?php echo $isLoggedIn ? 'LOGGED IN' : 'LOGGED OUT'; ?></h2>
        <?php if ($isLoggedIn): ?>
            <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
            <p>Username: <?php echo $_SESSION['username'] ?? 'N/A'; ?></p>
            <p>Email: <?php echo $_SESSION['email'] ?? 'N/A'; ?></p>
        <?php endif; ?>
    </div>
    
    <h3>Session Data:</h3>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <?php if ($isLoggedIn): ?>
        <h2>Logout Options</h2>
        <button onclick="window.location.href='auth_test.php?action=logout'" class="danger">
            Logout (Test Script)
        </button>
        <button onclick="window.location.href='/BTT/logout.php'" class="danger">
            Logout (Main Script)
        </button>
        <button onclick="window.location.href='/BTT/auth.php?action=logout'" class="danger">
            Logout (Auth Page)
        </button>
    <?php else: ?>
        <h2>Quick Login (For Testing)</h2>
        <form method="POST">
            <label>User ID:</label>
            <select name="user_id" required>
                <option value="1">User 1 (ID: 1)</option>
                <option value="2">User 2 (ID: 2)</option>
                <option value="3">User 3 (ID: 3)</option>
            </select>
            
            <label>Username:</label>
            <input type="text" name="username" value="testuser" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="test@example.com" required>
            
            <button type="submit" name="login">Login</button>
        </form>
        
        <h2>Or Use Main Login</h2>
        <a href="/BTT/auth.php?action=login">Go to Main Login Page</a>
    <?php endif; ?>
    
    <hr style="margin: 40px 0; border-color: #555;">
    
    <h2>Navigation</h2>
    <a href="/BTT/">Home</a>
    <a href="/BTT/trips.php">Trips</a>
    <a href="/BTT/backpacks.php">Backpacks</a>
    <a href="/BTT/test/diagnose_trips_issue.php">Diagnose Trips</a>
    <a href="/BTT/test/check_trips_page.html">Check Trips Page</a>
</body>
</html>
