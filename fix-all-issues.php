<?php
/**
 * Fix All Issues - Comprehensive test and fix page
 */
require_once __DIR__ . '/app/bootstrap.php';
use App\Services\AuthService;

// Check if user is logged in
$currentUser = AuthService::getCurrentUser();
$isLoggedIn = AuthService::isAuthenticated();

// Handle test login
if (isset($_POST['test_login'])) {
    // Try different admin emails
    $result = AuthService::login('admin@btt.local', 'admin123');
    if (!$result['success']) {
        // Try with username
        $result = AuthService::login('admin', 'admin123');
    }
    if ($result['success']) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = $result['message'];
    }
}

// Handle direct logout (fallback)
if (isset($_GET['logout'])) {
    AuthService::logout();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BTT - Fix All Issues</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: white; margin-bottom: 30px; }
        .card { 
            background: white; 
            border-radius: 12px; 
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status { 
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        button, .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        button:hover, .btn:hover { opacity: 0.9; }
        pre { 
            background: #f4f5f7; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto;
            font-size: 12px;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-content a:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 BTT System Fix & Test Page</h1>
        
        <!-- Authentication Status -->
        <div class="card">
            <h2>🔐 Authentication Status</h2>
            <?php if ($isLoggedIn): ?>
                <div class="status success">
                    <strong>✅ Logged In</strong><br>
                    User: <?= htmlspecialchars($currentUser['username']) ?><br>
                    Email: <?= htmlspecialchars($currentUser['email']) ?><br>
                    ID: <?= htmlspecialchars($currentUser['id']) ?>
                </div>
                
                <!-- Working Dropdown Menu -->
                <div class="dropdown">
                    <button onclick="toggleDropdown()" class="btn btn-primary">User Menu ▼</button>
                    <div id="userDropdown" class="dropdown-content">
                        <a href="#">Profile</a>
                        <a href="#">Settings</a>
                        <hr style="margin: 0;">
                        <a href="?logout=1" style="color: red;">Logout</a>
                    </div>
                </div>
                
                <a href="?logout=1" class="btn btn-danger">Direct Logout</a>
            <?php else: ?>
                <div class="status warning">
                    <strong>⚠️ Not Logged In</strong>
                </div>
                <form method="post" style="display: inline;">
                    <button type="submit" name="test_login" class="btn btn-success">
                        Quick Login as Admin
                    </button>
                </form>
                <a href="<?= BTT_PUBLIC_URL ?>/auth/login.php" class="btn btn-primary">Go to Login</a>
                <?php if (isset($loginError)): ?>
                    <div class="status error">Login Error: <?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- API Test -->
        <div class="card">
            <h2>🚀 API Tests</h2>
            <div id="api-tests">
                <button onclick="testAPI('/health')" class="btn btn-primary">Test Health</button>
                <button onclick="testAPI('/trips')" class="btn btn-primary">Test Trips</button>
                <button onclick="testAPI('/backpacks')" class="btn btn-primary">Test Backpacks</button>
                <?php if ($isLoggedIn): ?>
                    <button onclick="testLogout()" class="btn btn-warning">Test API Logout</button>
                <?php endif; ?>
            </div>
            <div id="api-results"></div>
        </div>
        
        <!-- Quick Navigation -->
        <div class="card">
            <h2>🔗 Quick Links</h2>
            <a href="<?= BTT_PUBLIC_URL ?>/index.php" class="btn btn-primary">Dashboard</a>
            <a href="<?= BTT_PUBLIC_URL ?>/trips.php" class="btn btn-primary">Trips</a>
            <a href="<?= BTT_PUBLIC_URL ?>/backpacks.php" class="btn btn-primary">Backpacks</a>
            <a href="<?= BTT_PUBLIC_URL ?>/auth/login.php" class="btn btn-warning">Login Page</a>
            <a href="<?= BTT_PUBLIC_URL ?>/auth/register.php" class="btn btn-warning">Register Page</a>
        </div>
        
        <!-- Database Check -->
        <div class="card">
            <h2>🗄️ Database Status</h2>
            <?php
            try {
                $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check tables
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                echo '<div class="status success">✅ Database Connected</div>';
                echo '<div class="status info">Tables: ' . implode(', ', $tables) . '</div>';
                
                if ($isLoggedIn) {
                    // Count user's data
                    $userId = $currentUser['id'];
                    
                    $tripCount = $db->query("SELECT COUNT(*) FROM trips WHERE user_id = $userId")->fetchColumn();
                    $backpackCount = $db->query("SELECT COUNT(*) FROM backpacks WHERE user_id = $userId")->fetchColumn();
                    
                    echo '<div class="status info">Your Trips: ' . $tripCount . '</div>';
                    echo '<div class="status info">Your Backpacks: ' . $backpackCount . '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
    
    <script>
    // Simple dropdown toggle
    function toggleDropdown() {
        document.getElementById("userDropdown").classList.toggle("show");
    }
    
    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.btn-primary')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].classList.remove('show');
            }
        }
    }
    
    // Test API endpoints
    function testAPI(endpoint) {
        const resultsDiv = document.getElementById('api-results');
        resultsDiv.innerHTML = '<div class="status info">Testing ' + endpoint + '...</div>';
        
        fetch('<?= BTT_API_URL ?>/?route=' + endpoint.substring(1), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const statusText = response.status + ' ' + response.statusText;
            return response.json().then(data => ({
                status: response.status,
                statusText: statusText,
                data: data
            }));
        })
        .then(result => {
            let statusClass = result.status === 200 ? 'success' : 
                            result.status === 401 ? 'warning' : 'error';
            resultsDiv.innerHTML = `
                <div class="status ${statusClass}">
                    ${endpoint}: ${result.statusText}
                </div>
                <pre>${JSON.stringify(result.data, null, 2)}</pre>
            `;
        })
        .catch(error => {
            resultsDiv.innerHTML = `
                <div class="status error">
                    ${endpoint}: Network Error - ${error.message}
                </div>
            `;
        });
    }
    
    // Test logout via API
    function testLogout() {
        if (!confirm('Test logout via API?')) return;
        
        fetch('<?= BTT_API_URL ?>/?route=auth&id=logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            alert('Logout response: ' + JSON.stringify(data));
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('Logout error: ' + error.message);
        });
    }
    
    <?php if ($isLoggedIn): ?>
    // Auto-test on load
    window.addEventListener('DOMContentLoaded', function() {
        testAPI('/trips');
    });
    <?php endif; ?>
    </script>
</body>
</html>
