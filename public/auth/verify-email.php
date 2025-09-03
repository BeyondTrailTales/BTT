<?php
/**
 * BeyondTrailTales - Email Verification Page
 * 
 * Handles email verification from link
 */

// Load bootstrap
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';
use App\Services\AuthService;

// Get token from query string
$token = $_GET['token'] ?? '';
$verified = false;
$message = '';
$messageType = 'info';

if (!empty($token)) {
    // Attempt to verify the email
    $result = AuthService::verifyEmail($token);
    
    if ($result['success']) {
        $verified = true;
        $message = $result['message'] ?? 'Email verified successfully! You can now log in.';
        $messageType = 'success';
    } else {
        $message = $result['message'] ?? 'Invalid or expired verification token.';
        $messageType = 'error';
    }
} else {
    $message = 'No verification token provided.';
    $messageType = 'error';
}

// Page metadata
$pageId = 'auth-verify-email';
$pageTitle = 'Email Verification';
$pageDescription = 'Verify your BeyondTrailTales account email';

// Include header
require_once BASE_PATH . '/public/includes/template-header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Logo/Title -->
        <div class="auth-header">
            <h1 class="auth-title">
                <?php if ($verified): ?>
                    <span aria-hidden="true">✅</span>
                    Email Verified!
                <?php else: ?>
                    <span aria-hidden="true">📧</span>
                    Email Verification
                <?php endif; ?>
            </h1>
        </div>

        <!-- Message -->
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <!-- Actions -->
        <div class="auth-actions">
            <?php if ($verified): ?>
                <a href="<?php echo BASE_URL; ?>/public/auth/login.php" class="btn btn-primary btn-block">
                    Go to Login
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/public/auth/register.php" class="btn btn-primary btn-block">
                    Back to Registration
                </a>
                <div class="auth-footer">
                    <p>
                        Didn't receive the email?
                        <a href="<?php echo BASE_URL; ?>/public/auth/resend-verification.php" class="link">
                            Resend verification email
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Reuse styles from login page */
.auth-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-card {
    background: var(--surface-primary);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 2.5rem;
    max-width: 420px;
    width: 100%;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-title {
    color: var(--forest-mint);
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    text-align: center;
}

.alert-success {
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid rgba(74, 222, 128, 0.3);
    color: var(--forest-mint);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
}

.alert-info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #3b82f6;
}

.auth-actions {
    margin-top: 1.5rem;
}

.btn-block {
    width: 100%;
    display: block;
    text-align: center;
}

.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--glass-border);
    color: var(--text-secondary);
}
</style>

<?php require_once BASE_PATH . '/public/includes/template-footer.php'; ?>
