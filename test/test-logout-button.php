<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Check if user is logged in
$currentUser = AuthService::getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BTT - Logout Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        .status { padding: 20px; border-radius: 8px; margin: 20px 0; }
        .logged-in { background: #d4edda; color: #155724; }
        .logged-out { background: #f8d7da; color: #721c24; }
        .btn { 
            padding: 12px 30px; 
            margin: 10px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-logout { background: #dc3545; color: white; }
        .btn-logout:hover { background: #c82333; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>BTT - Logout Test</h1>
    
    <?php if ($currentUser): ?>
        <div class="status logged-in">
            <h2>✅ You are logged in</h2>
            <p>Username: <?= htmlspecialchars($currentUser['username'] ?? 'Unknown') ?></p>
            <p>Email: <?= htmlspecialchars($currentUser['email'] ?? 'Unknown') ?></p>
            <p>Session ID: <?= session_id() ?></p>
        </div>
        
        <h3>Test Logout Methods:</h3>
        
        <!-- Method 1: Direct GET request -->
        <p>
            <a href="<?= BTT_API_URL ?>/?route=auth&id=logout" class="btn btn-logout">
                Logout (Direct GET)
            </a>
        </p>
        
        <!-- Method 2: Form POST -->
        <p>
            <form method="post" action="<?= BTT_API_URL ?>/?route=auth&id=logout" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-logout">Logout (Form POST)</button>
            </form>
        </p>
        
        <!-- Method 3: AJAX POST -->
        <p>
            <button onclick="ajaxLogout()" class="btn btn-logout">Logout (AJAX)</button>
        </p>
        
    <?php else: ?>
        <div class="status logged-out">
            <h2>❌ You are not logged in</h2>
        </div>
        
        <p>
            <a href="<?= BTT_PUBLIC_URL ?>/auth/login.php" class="btn btn-primary">
                Go to Login Page
            </a>
        </p>
    <?php endif; ?>
    
    <hr style="margin: 40px 0;">
    
    <h3>Quick Links:</h3>
    <p>
        <a href="<?= BTT_PUBLIC_URL ?>/trips.php" class="btn btn-primary">Trips Page</a>
        <a href="<?= BTT_PUBLIC_URL ?>/backpacks.php" class="btn btn-primary">Backpacks Page</a>
        <a href="<?= BTT_PUBLIC_URL ?>/dashboard.php" class="btn btn-primary">Dashboard</a>
    </p>
    
    <script>
    function ajaxLogout() {
        if (!confirm('Are you sure you want to logout via AJAX?')) return;
        
        fetch('<?= BTT_API_URL ?>/?route=auth&id=logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Logout successful!');
                window.location.href = data.redirect || '<?= BTT_PUBLIC_URL ?>/';
            } else {
                alert('Logout failed: ' + (data.error || data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Network error: ' + error.message);
        });
    }
    </script>
</body>
</html>
