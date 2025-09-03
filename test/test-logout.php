<?php
/**
 * Test logout functionality and redirect to homepage
 */
/**
 * Test page for logout functionality
 */
$pageTitle = 'Test Logout Functionality';
$pageId = 'test-logout';

// Include the header which should have the navigation
require_once '../public/includes/header.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<div class="test-container">
    <h1>Logout Functionality Test</h1>
    
    <?php if ($isLoggedIn): ?>
        <div class="alert alert-success">
            <h2>✅ You are currently logged in</h2>
            <p>User ID: <?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></p>
            <p>Username: <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'N/A'); ?></p>
            <p>Email: <?php echo htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['email'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="test-instructions">
            <h2>Test Instructions:</h2>
            <ol>
                <li>Click on your username in the navigation bar to open the dropdown menu</li>
                <li>Click on "Logout" in the dropdown menu</li>
                <li>You should see a loading state briefly</li>
                <li>You should be redirected to the login page</li>
            </ol>
        </div>
        
        <div class="console-monitor">
            <h2>Console Output:</h2>
            <div id="console-output" style="background: #1a1a1a; color: #4caf50; padding: 1rem; border-radius: 8px; font-family: monospace; min-height: 100px;">
                <p>Monitoring console for errors...</p>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <h2>⚠️ You are not logged in</h2>
            <p>Please <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/login.php">login</a> first to test the logout functionality.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.test-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
    color: #4caf50;
}

.alert-warning {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
}

.test-instructions {
    background: rgba(33, 150, 243, 0.05);
    border: 1px solid rgba(33, 150, 243, 0.2);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.test-instructions h2 {
    color: #2196f3;
    margin-top: 0;
}

.test-instructions ol {
    margin: 0;
    padding-left: 1.5rem;
}

.test-instructions li {
    margin-bottom: 0.5rem;
    color: #e0e0e0;
}

.console-monitor {
    margin-top: 2rem;
}

.console-monitor h2 {
    color: #9e9e9e;
}
</style>

<script>
// Monitor console for errors
(function() {
    const outputDiv = document.getElementById('console-output');
    if (!outputDiv) return;
    
    // Clear initial message
    outputDiv.innerHTML = '';
    
    // Override console methods to display in our div
    const originalLog = console.log;
    const originalError = console.error;
    const originalWarn = console.warn;
    
    function addToOutput(message, type) {
        const timestamp = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.style.marginBottom = '0.5rem';
        
        const colors = {
            log: '#4caf50',
            error: '#f44336',
            warn: '#ff9800'
        };
        
        entry.style.color = colors[type] || '#4caf50';
        entry.textContent = `[${timestamp}] [${type.toUpperCase()}] ${message}`;
        outputDiv.appendChild(entry);
        
        // Keep only last 10 entries
        while (outputDiv.children.length > 10) {
            outputDiv.removeChild(outputDiv.firstChild);
        }
    }
    
    console.log = function(...args) {
        addToOutput(args.join(' '), 'log');
        originalLog.apply(console, args);
    };
    
    console.error = function(...args) {
        addToOutput(args.join(' '), 'error');
        originalError.apply(console, args);
    };
    
    console.warn = function(...args) {
        addToOutput(args.join(' '), 'warn');
        originalWarn.apply(console, args);
    };
    
    // Log initial state
    console.log('Logout test page loaded');
    console.log('Navigation script loaded: ' + (typeof BTTNav !== 'undefined' ? 'Yes' : 'No'));
    console.log('User dropdown found: ' + (document.querySelector('.nav-user-button') ? 'Yes' : 'No'));
    console.log('Logout form found: ' + (document.querySelector('.logout-form') ? 'Yes' : 'No'));
})();
</script>

<?php
// Include footer
require_once '../public/includes/footer.php';
?>
