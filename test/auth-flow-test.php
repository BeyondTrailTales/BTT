<?php
/**
 * Complete Authentication Flow Test
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Check for magic links file
$magicLinks = [];
$magicFile = BASE_PATH . '/test/magic-links.json';
if (file_exists($magicFile)) {
    $magicLinks = json_decode(file_get_contents($magicFile), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Flow Test - BeyondTrailTales</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ade80;
            border-bottom: 2px solid #4ade80;
            padding-bottom: 1rem;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .test-card {
            background: #2a2a2a;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #3a3a3a;
        }
        .test-card h2 {
            color: #4ade80;
            margin-top: 0;
            font-size: 1.2rem;
        }
        .test-link {
            display: inline-block;
            background: #4ade80;
            color: #1a1a1a;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            margin: 0.25rem;
            font-weight: bold;
        }
        .test-link:hover {
            background: #22c55e;
        }
        .magic-links {
            background: #0a0a0a;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            border: 1px solid #4ade80;
        }
        .magic-link-item {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: #1a1a1a;
            border-radius: 4px;
        }
        .magic-link-item a {
            color: #4ade80;
            word-break: break-all;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        .status-working {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
        }
        .status-testing {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        .status-todo {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .checklist {
            margin-top: 1rem;
        }
        .checklist li {
            margin: 0.5rem 0;
        }
        .checklist .done {
            color: #4ade80;
            text-decoration: line-through;
        }
        .test-accounts {
            background: rgba(74, 222, 128, 0.1);
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
        .test-accounts code {
            background: #0a0a0a;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🧪 Complete Authentication Flow Test</h1>
    
    <div class="test-accounts">
        <h3>Test Accounts:</h3>
        <p>
            <strong>Admin:</strong> <code>admin</code> / <code>Admin123!</code><br>
            <strong>Test User:</strong> <code>testuser</code> / <code>Test123!</code><br>
            <strong>Demo:</strong> <code>demo</code> / <code>Demo123!</code>
        </p>
    </div>

    <div class="test-grid">
        <!-- Login/Logout Tests -->
        <div class="test-card">
            <h2>1. Login/Logout <span class="status-badge status-working">✅ Working</span></h2>
            <p>Test basic authentication flow</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/public/auth/login.php" class="test-link" target="_blank">Login Page</a>
                <a href="<?php echo BASE_URL; ?>/test/simple-login.php" class="test-link" target="_blank">Simple Login</a>
                <a href="<?php echo BASE_URL; ?>/test/ajax-login-test.php" class="test-link" target="_blank">AJAX Test</a>
            </div>
            <ul class="checklist">
                <li class="done">✅ Login with username</li>
                <li class="done">✅ Login with email</li>
                <li class="done">✅ Session persistence</li>
                <li class="done">✅ Logout functionality</li>
            </ul>
        </div>

        <!-- Registration Tests -->
        <div class="test-card">
            <h2>2. Registration <span class="status-badge status-testing">⚠️ Testing</span></h2>
            <p>Test new user registration</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/public/auth/register.php" class="test-link" target="_blank">Register Page</a>
            </div>
            <ul class="checklist">
                <li>⚪ Form validation</li>
                <li>⚪ Duplicate email check</li>
                <li>⚪ Duplicate username check</li>
                <li>⚪ Email sent (check logs)</li>
            </ul>
        </div>

        <!-- Password Reset Tests -->
        <div class="test-card">
            <h2>3. Password Reset <span class="status-badge status-testing">⚠️ Testing</span></h2>
            <p>Test password reset flow</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/public/auth/forgot-password.php" class="test-link" target="_blank">Forgot Password</a>
                <a href="<?php echo BASE_URL; ?>/public/auth/reset-password.php?token=test" class="test-link" target="_blank">Reset Page (test)</a>
            </div>
            <ul class="checklist">
                <li>⚪ Request reset email</li>
                <li>⚪ Token validation</li>
                <li>⚪ Password update</li>
                <li>⚪ Session invalidation</li>
            </ul>
        </div>

        <!-- Email Verification Tests -->
        <div class="test-card">
            <h2>4. Email Verification <span class="status-badge status-testing">⚠️ Testing</span></h2>
            <p>Test email verification</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/public/auth/verify-email.php" class="test-link" target="_blank">Verify Page (no token)</a>
                <a href="<?php echo BASE_URL; ?>/public/auth/verify-email.php?token=test" class="test-link" target="_blank">Verify Page (test)</a>
            </div>
            <ul class="checklist">
                <li>⚪ Verification link sent</li>
                <li>⚪ Token validation</li>
                <li>⚪ Account activation</li>
            </ul>
        </div>

        <!-- Protected Routes -->
        <div class="test-card">
            <h2>5. Protected Pages <span class="status-badge status-working">✅ Working</span></h2>
            <p>Test authentication requirements</p>
            <div>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="test-link" target="_blank">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/public/trips.php" class="test-link" target="_blank">Trips</a>
                <a href="<?php echo BASE_URL; ?>/public/backpacks.php" class="test-link" target="_blank">Backpacks</a>
            </div>
            <ul class="checklist">
                <li class="done">✅ Redirect to login if not authenticated</li>
                <li class="done">✅ Access granted when logged in</li>
                <li class="done">✅ Navigation shows user info</li>
            </ul>
        </div>

        <!-- CSRF Protection -->
        <div class="test-card">
            <h2>6. Security Features <span class="status-badge status-testing">⚠️ Testing</span></h2>
            <p>Test security measures</p>
            <ul class="checklist">
                <li>⚪ CSRF token validation</li>
                <li>⚪ Rate limiting (5 attempts)</li>
                <li>⚪ Password hashing (bcrypt)</li>
                <li>⚪ Session regeneration</li>
                <li>⚪ Remember me cookie</li>
            </ul>
        </div>
    </div>

    <!-- Magic Links Section -->
    <div class="test-card" style="margin-top: 2rem;">
        <h2>📧 Development Email Links</h2>
        <p>Magic links from sent emails (development mode only)</p>
        
        <?php if (!empty($magicLinks)): ?>
            <div class="magic-links">
                <?php foreach (array_reverse($magicLinks) as $entry): ?>
                    <div class="magic-link-item">
                        <strong><?php echo htmlspecialchars($entry['timestamp']); ?></strong> - 
                        <?php echo htmlspecialchars($entry['subject']); ?>
                        <?php if (!empty($entry['links'])): ?>
                            <ul>
                                <?php foreach ($entry['links'] as $type => $link): ?>
                                    <li>
                                        <?php echo ucfirst($type); ?>: 
                                        <a href="<?php echo htmlspecialchars($link); ?>" target="_blank">
                                            <?php echo htmlspecialchars($link); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #9ca3af;">No emails sent yet. Register a new user or request a password reset to see magic links here.</p>
        <?php endif; ?>
        
        <div style="margin-top: 1rem;">
            <a href="<?php echo BASE_URL; ?>/storage/logs/mail.log" class="test-link" target="_blank">View Mail Log</a>
            <a href="<?php echo BASE_URL; ?>/test/emails/" class="test-link" target="_blank">View Email Templates</a>
        </div>
    </div>

    <!-- Test Scripts -->
    <div class="test-card" style="margin-top: 2rem;">
        <h2>🔧 Test Scripts</h2>
        <p>Run these scripts to test specific functionality</p>
        
        <h3>Create Test User:</h3>
        <pre style="background: #0a0a0a; padding: 1rem; border-radius: 4px; overflow-x: auto;">
php -r "
require_once 'app/bootstrap.php';
use App\Services\AuthService;

\$result = AuthService::register(
    'test' . time() . '@example.com',
    'testuser' . time(),
    'TestPass123!'
);
print_r(\$result);
"</pre>

        <h3>Test Password Reset:</h3>
        <pre style="background: #0a0a0a; padding: 1rem; border-radius: 4px; overflow-x: auto;">
php -r "
require_once 'app/bootstrap.php';
use App\Services\AuthService;

\$result = AuthService::requestPasswordReset('admin@example.com');
print_r(\$result);
"</pre>
    </div>
</body>
</html>
