<?php
/**
 * CSRF Token Diagnostic Page
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\Csrf;

$pageTitle = 'CSRF Token Diagnostic';
$pageId = 'test-csrf';

// Get current token
$currentToken = csrf_token();
$sessionId = session_id();
$sessionData = $_SESSION;

// Include header
require_once '../public/includes/header.php';
?>

<div class="test-container">
    <h1>CSRF Token Diagnostic</h1>
    
    <div class="diagnostic-section">
        <h2>Session Information</h2>
        <table class="diagnostic-table">
            <tr>
                <th>Session ID</th>
                <td><code><?php echo htmlspecialchars($sessionId); ?></code></td>
            </tr>
            <tr>
                <th>Session Status</th>
                <td><?php 
                    $status = session_status();
                    echo $status == PHP_SESSION_NONE ? 'None' : 
                         ($status == PHP_SESSION_ACTIVE ? 'Active' : 'Disabled');
                ?></td>
            </tr>
            <tr>
                <th>Session Save Path</th>
                <td><code><?php echo htmlspecialchars(session_save_path() ?: 'Default'); ?></code></td>
            </tr>
        </table>
    </div>
    
    <div class="diagnostic-section">
        <h2>CSRF Token Information</h2>
        <table class="diagnostic-table">
            <tr>
                <th>Current Token</th>
                <td><code><?php echo htmlspecialchars($currentToken ?: 'No token'); ?></code></td>
            </tr>
            <tr>
                <th>Token from Session</th>
                <td><code><?php echo htmlspecialchars($_SESSION['csrf_token'] ?? 'Not in session'); ?></code></td>
            </tr>
            <tr>
                <th>Token Expiry</th>
                <td><?php 
                    if (isset($_SESSION['csrf_token_expiry'])) {
                        $expiry = $_SESSION['csrf_token_expiry'];
                        $remaining = $expiry - time();
                        echo date('Y-m-d H:i:s', $expiry) . ' (' . $remaining . ' seconds remaining)';
                    } else {
                        echo 'Not set';
                    }
                ?></td>
            </tr>
        </table>
    </div>
    
    <div class="diagnostic-section">
        <h2>Form Token Test</h2>
        <p>This is how the token appears in a form:</p>
        <form method="post" action="#" class="test-form">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary">Test Submit (Check Console)</button>
        </form>
        
        <div id="form-token-display" class="code-display">
            <p>Hidden field HTML:</p>
            <code><?php echo htmlspecialchars(csrf_field()); ?></code>
        </div>
    </div>
    
    <div class="diagnostic-section">
        <h2>Logout Form Analysis</h2>
        <?php
        $logoutForm = document.querySelector('.logout-form');
        ?>
        <p>Check the logout form in the navigation dropdown:</p>
        <ol>
            <li>Open browser developer tools (F12)</li>
            <li>Go to Console tab</li>
            <li>Click on your username to open dropdown</li>
            <li>Inspect the logout form</li>
            <li>Check if csrf_token input has a value</li>
        </ol>
    </div>
    
    <div class="diagnostic-section">
        <h2>Session Data</h2>
        <div class="code-display">
            <pre><?php 
                $safeSession = $sessionData;
                // Remove sensitive data
                if (isset($safeSession['password'])) unset($safeSession['password']);
                echo htmlspecialchars(print_r($safeSession, true)); 
            ?></pre>
        </div>
    </div>
    
    <div class="diagnostic-section">
        <h2>Test Actions</h2>
        <button onclick="testTokenGeneration()" class="btn">Generate New Token</button>
        <button onclick="testTokenValidation()" class="btn">Test Token Validation</button>
        <button onclick="clearToken()" class="btn btn-warning">Clear Token</button>
    </div>
    
    <div id="test-output" class="test-output"></div>
</div>

<style>
.test-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
}

.diagnostic-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.diagnostic-section h2 {
    margin-top: 0;
    color: #4caf50;
}

.diagnostic-table {
    width: 100%;
    border-collapse: collapse;
}

.diagnostic-table th,
.diagnostic-table td {
    padding: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: left;
}

.diagnostic-table th {
    width: 200px;
    color: #9e9e9e;
}

.diagnostic-table code {
    background: rgba(0, 0, 0, 0.3);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.875rem;
    word-break: break-all;
}

.code-display {
    background: #1a1a1a;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.code-display pre {
    margin: 0;
    color: #4caf50;
    font-size: 0.875rem;
    overflow-x: auto;
}

.test-form {
    margin: 1rem 0;
}

.btn {
    background: #4caf50;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 0.5rem;
}

.btn:hover {
    background: #45a049;
}

.btn-warning {
    background: #ff9800;
}

.btn-warning:hover {
    background: #e68900;
}

.test-output {
    margin-top: 2rem;
    padding: 1rem;
    background: rgba(33, 150, 243, 0.1);
    border: 1px solid rgba(33, 150, 243, 0.3);
    border-radius: 8px;
    min-height: 50px;
    display: none;
}

.test-output.show {
    display: block;
}
</style>

<script>
function testTokenGeneration() {
    fetch('<?php echo BTT_API_URL; ?>?route=test&action=generate-csrf', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        showOutput('Token Generation Test: ' + JSON.stringify(data, null, 2));
        setTimeout(() => location.reload(), 2000);
    })
    .catch(error => {
        showOutput('Error: ' + error.message, 'error');
    });
}

function testTokenValidation() {
    const token = document.querySelector('input[name="csrf_token"]')?.value || '';
    
    fetch('<?php echo BTT_API_URL; ?>?route=test&action=validate-csrf', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: token
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        showOutput('Token Validation Test: ' + JSON.stringify(data, null, 2));
    })
    .catch(error => {
        showOutput('Error: ' + error.message, 'error');
    });
}

function clearToken() {
    if (confirm('This will clear your CSRF token. Continue?')) {
        fetch('<?php echo BTT_API_URL; ?>?route=test&action=clear-csrf', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            showOutput('Token cleared. Refreshing page...');
            setTimeout(() => location.reload(), 1500);
        })
        .catch(error => {
            showOutput('Error: ' + error.message, 'error');
        });
    }
}

function showOutput(message, type = 'info') {
    const output = document.getElementById('test-output');
    output.textContent = message;
    output.className = 'test-output show ' + type;
}

// Log current state
console.log('CSRF Diagnostic Page Loaded');
console.log('Current token from form:', document.querySelector('input[name="csrf_token"]')?.value);
console.log('Session ID:', '<?php echo $sessionId; ?>');

// Check logout form token
document.addEventListener('DOMContentLoaded', function() {
    const logoutForm = document.querySelector('.logout-form');
    if (logoutForm) {
        const logoutToken = logoutForm.querySelector('[name="csrf_token"]')?.value;
        console.log('Logout form CSRF token:', logoutToken || 'NOT FOUND');
    }
});
</script>

<?php require_once '../public/includes/footer.php'; ?>
