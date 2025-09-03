<?php
/**
 * BeyondTrailTales - Reset Password Page
 * 
 * ADA-compliant password reset form
 */

// Load bootstrap
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

// Get token from query string
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: ' . BASE_URL . '/public/auth/forgot-password.php');
    exit;
}

// If already logged in, redirect
if (is_authenticated()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Page metadata
$pageId = 'auth-reset-password';
$pageTitle = 'Reset Password';
$pageDescription = 'Set a new password for your BeyondTrailTales account';

// Include header
require_once BASE_PATH . '/public/includes/template-header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Logo/Title -->
        <div class="auth-header">
            <h1 class="auth-title">
                <span aria-hidden="true">🔐</span>
                Set New Password
            </h1>
            <p class="auth-subtitle">Choose a strong password for your account</p>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" role="alert" aria-live="polite" aria-atomic="true"></div>

        <!-- Reset Form -->
        <form id="reset-form" class="auth-form" novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">
                    New Password
                    <span class="required" aria-label="required">*</span>
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control"
                        required
                        aria-required="true"
                        aria-describedby="password-error password-help"
                        autocomplete="new-password"
                        autofocus
                    />
                    <button 
                        type="button" 
                        class="password-toggle"
                        aria-label="Toggle password visibility"
                        data-target="password"
                    >
                        <span class="show-icon" aria-hidden="true">👁️</span>
                        <span class="hide-icon" aria-hidden="true" style="display:none;">🙈</span>
                    </button>
                </div>
                <span id="password-error" class="error-text" role="alert"></span>
                <ul id="password-help" class="help-text password-requirements">
                    <li id="req-length">At least 8 characters</li>
                    <li id="req-letter">Contains letters</li>
                    <li id="req-number">Contains numbers</li>
                </ul>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="password-confirm" class="form-label">
                    Confirm New Password
                    <span class="required" aria-label="required">*</span>
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password-confirm" 
                        name="password_confirm" 
                        class="form-control"
                        required
                        aria-required="true"
                        aria-describedby="password-confirm-error"
                        autocomplete="new-password"
                    />
                    <button 
                        type="button" 
                        class="password-toggle"
                        aria-label="Toggle password visibility"
                        data-target="password-confirm"
                    >
                        <span class="show-icon" aria-hidden="true">👁️</span>
                        <span class="hide-icon" aria-hidden="true" style="display:none;">🙈</span>
                    </button>
                </div>
                <span id="password-confirm-error" class="error-text" role="alert"></span>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="btn btn-primary btn-block"
                id="submit-btn"
            >
                <span class="btn-text">Reset Password</span>
                <span class="btn-loader" style="display:none;">
                    <span class="spinner"></span>
                    Resetting...
                </span>
            </button>

            <!-- Back to Login -->
            <div class="auth-footer">
                <p>
                    <a href="<?php echo BASE_URL; ?>/public/auth/login.php" class="link">
                        Back to Login
                    </a>
                </p>
            </div>
        </form>
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

.password-requirements {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0 0;
}

.password-requirements li {
    padding: 0.25rem 0;
    padding-left: 1.5rem;
    position: relative;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.password-requirements li::before {
    content: '✗';
    position: absolute;
    left: 0;
    color: var(--danger);
}

.password-requirements li.met::before {
    content: '✓';
    color: var(--forest-mint);
}

.password-requirements li.met {
    color: var(--forest-mint);
}

.password-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.25rem;
}

/* Other styles inherited from login page */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reset-form');
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    // Password visibility toggle
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const showIcon = this.querySelector('.show-icon');
            const hideIcon = this.querySelector('.hide-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                showIcon.style.display = 'none';
                hideIcon.style.display = 'inline';
                this.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                showIcon.style.display = 'inline';
                hideIcon.style.display = 'none';
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
    
    // Password strength validation
    const passwordInput = document.getElementById('password');
    passwordInput.addEventListener('input', function() {
        const value = this.value;
        
        // Check requirements
        const hasLength = value.length >= 8;
        const hasLetter = /[a-zA-Z]/.test(value);
        const hasNumber = /\d/.test(value);
        
        // Update UI
        document.getElementById('req-length').classList.toggle('met', hasLength);
        document.getElementById('req-letter').classList.toggle('met', hasLetter);
        document.getElementById('req-number').classList.toggle('met', hasNumber);
        
        // Clear error if typing
        if (this.classList.contains('error')) {
            clearFieldError('password');
        }
    });
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        clearAlert();
        
        // Get values
        const token = document.getElementById('token').value;
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password-confirm').value;
        
        // Validate
        let hasErrors = false;
        
        if (!password) {
            showError('password', 'Password is required');
            hasErrors = true;
        } else if (password.length < 8) {
            showError('password', 'Password must be at least 8 characters');
            hasErrors = true;
        }
        
        if (!passwordConfirm) {
            showError('password-confirm', 'Please confirm your password');
            hasErrors = true;
        } else if (password !== passwordConfirm) {
            showError('password-confirm', 'Passwords do not match');
            hasErrors = true;
        }
        
        if (hasErrors) {
            return;
        }
        
        // Show loading state
        setLoading(true);
        
        try {
            // Get CSRF token
            const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            
            // Submit request
            const response = await fetch('<?php echo BTT_API_URL; ?>/?route=auth&id=reset-password', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    token: token,
                    password: password,
                    password_confirm: passwordConfirm,
                    csrf_token: csrfToken
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('Password reset successfully! Redirecting to login...', 'success');
                setTimeout(() => {
                    window.location.href = '<?php echo BASE_URL; ?>/public/auth/login.php';
                }, 2000);
            } else {
                showAlert(result.message || 'Password reset failed. The link may be expired.', 'error');
                setLoading(false);
            }
            
        } catch (error) {
            console.error('Password reset error:', error);
            showAlert('An error occurred. Please try again.', 'error');
            setLoading(false);
        }
    });
    
    // Helper functions
    function showError(field, message) {
        const input = document.getElementById(field);
        const errorElement = document.getElementById(field + '-error');
        
        if (input && errorElement) {
            input.setAttribute('aria-invalid', 'true');
            input.classList.add('error');
            errorElement.textContent = message;
            errorElement.setAttribute('aria-live', 'polite');
        }
    }
    
    function clearFieldError(field) {
        const input = document.getElementById(field);
        const errorElement = document.getElementById(field + '-error');
        
        if (input && errorElement) {
            input.setAttribute('aria-invalid', 'false');
            input.classList.remove('error');
            errorElement.textContent = '';
        }
    }
    
    function clearErrors() {
        document.querySelectorAll('.form-control').forEach(input => {
            input.setAttribute('aria-invalid', 'false');
            input.classList.remove('error');
        });
        
        document.querySelectorAll('.error-text').forEach(error => {
            error.textContent = '';
            error.removeAttribute('aria-live');
        });
    }
    
    function showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        alert.setAttribute('role', 'alert');
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alert);
    }
    
    function clearAlert() {
        alertContainer.innerHTML = '';
    }
    
    function setLoading(loading) {
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        if (loading) {
            btnText.style.display = 'none';
            btnLoader.style.display = 'flex';
            submitBtn.disabled = true;
            form.querySelectorAll('input, button').forEach(el => {
                if (el.type !== 'hidden') el.disabled = true;
            });
        } else {
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            submitBtn.disabled = false;
            form.querySelectorAll('input, button').forEach(el => {
                el.disabled = false;
            });
        }
    }
});
</script>

<?php require_once BASE_PATH . '/public/includes/template-footer.php'; ?>
