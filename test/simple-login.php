<?php
/**
 * Simple Login Page - Traditional Form POST
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = AuthService::login($login, $password, false);
    
    if ($result['success']) {
        // Login successful - redirect to dashboard
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    } else {
        $error = $result['message'] ?? 'Login failed';
    }
}

// Check if already logged in
if (AuthService::isAuthenticated()) {
    $user = AuthService::getCurrentUser();
    $success = "You're already logged in as " . $user['username'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - BeyondTrailTales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-box {
            background: #2a2a2a;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #4ade80;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4ade80;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            background: #1a1a1a;
            border: 1px solid #4ade80;
            border-radius: 4px;
            color: #e0e0e0;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(74, 222, 128, 0.2);
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #4ade80;
            color: #1a1a1a;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #22c55e;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .success {
            background: rgba(74, 222, 128, 0.1);
            color: #4ade80;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
        .info {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #3a3a3a;
            text-align: center;
            font-size: 0.875rem;
            color: #9ca3af;
        }
        .test-accounts {
            background: rgba(74, 222, 128, 0.05);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid rgba(74, 222, 128, 0.2);
        }
        .test-accounts h3 {
            color: #4ade80;
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .test-accounts p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
        .links {
            margin-top: 1rem;
            text-align: center;
        }
        .links a {
            color: #4ade80;
            text-decoration: none;
            margin: 0 0.5rem;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🌲 BeyondTrailTales Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="test-accounts">
            <h3>Test Accounts:</h3>
            <p><strong>Admin:</strong> admin / Admin123!</p>
            <p><strong>Test:</strong> testuser / Test123!</p>
            <p><strong>Demo:</strong> demo / Demo123!</p>
        </div>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="login">Username or Email</label>
                <input type="text" id="login" name="login" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="links">
            <a href="<?php echo BASE_URL; ?>/">Home</a>
            <a href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/public/auth/register.php">Register</a>
        </div>
        
        <div class="info">
            <p>This is a simple login form that uses traditional form POST.</p>
            <p>No JavaScript required - just PHP session handling.</p>
        </div>
    </div>
</body>
</html>
