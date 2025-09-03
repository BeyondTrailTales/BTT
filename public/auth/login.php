<?php
/**
 * BeyondTrailTales - Login Page
 * 
 * ADA-compliant login form with remember me functionality
 */

// Load bootstrap
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

// If already logged in, redirect
if (is_authenticated()) {
    $returnTo = $_GET['returnTo'] ?? BASE_URL . '/public/trips.php';
    header('Location: ' . $returnTo);
    exit;
}

// Page metadata
$pageId = 'auth-login';
$pageTitle = 'Login';
$pageDescription = 'Sign in to your BeyondTrailTales account';

// Include header
require_once BASE_PATH . '/public/includes/template-header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Logo/Title -->
        <div class="auth-header">
            <h1 class="auth-title">
                <span aria-hidden="true">🏔️</span>
                Welcome Back
            </h1>
            <p class="auth-subtitle">Sign in to continue your adventure</p>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" role="alert" aria-live="polite" aria-atomic="true"></div>

        <!-- Login Form -->
        <form id="login-form" class="auth-form" novalidate>
            <?php echo csrf_field(); ?>
            
            <!-- Email/Username Field -->
            <div class="form-group">
                <label for="login" class="form-label">
                    Email or Username
                    <span class="required" aria-label="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    class="form-control"
                    required
                    aria-required="true"
                    aria-describedby="login-error"
                    autocomplete="username"
                    autofocus
                />
                <span id="login-error" class="error-text" role="alert"></span>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">
                    Password
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
                        aria-describedby="password-error"
                        autocomplete="current-password"
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
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="form-row space-between">
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                        class="form-check-input"
                    />
                    <label for="remember" class="form-check-label">
                        Remember me for 30 days
                    </label>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/auth/forgot-password.php" class="link">
                    Forgot password?
                </a>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="btn btn-primary btn-block"
                id="submit-btn"
            >
                <span class="btn-text">Sign In</span>
                <span class="btn-loader" style="display:none;">
                    <span class="spinner"></span>
                    Signing in...
                </span>
            </button>

            <!-- Register Link -->
            <div class="auth-footer">
                <p>
                    Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>/public/auth/register.php" class="link">
                        Create one now
                    </a>
                </p>
            </div>
        </form>

        <!-- Test Account Notice (Development Only) -->
        <?php if (BTT_DEBUG): ?>
        <div class="dev-notice" role="note">
            <strong>Test Accounts:</strong><br>
            admin / Admin123!<br>
            testuser / Test123!<br>
            demo / Demo123!
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Authentication Styles */
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

.auth-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
}

.auth-form {
    margin-top: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.required {
    color: var(--danger);
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
    color: var(--text-primary);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--forest-mint);
    box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.1);
    background: rgba(0, 0, 0, 0.4);
}

.form-control:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-control[aria-invalid="true"] {
    border-color: var(--danger);
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
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: var(--forest-mint);
}

.password-toggle:focus {
    outline: 2px solid var(--forest-mint);
    outline-offset: 2px;
    border-radius: 0.25rem;
}

.error-text {
    display: block;
    color: var(--danger);
    font-size: 0.875rem;
    margin-top: 0.25rem;
    min-height: 1.2em;
}

.form-row {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.form-row.space-between {
    justify-content: space-between;
}

.form-check {
    display: flex;
    align-items: center;
}

.form-check-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-right: 0.5rem;
    cursor: pointer;
}

.form-check-input:focus {
    outline: 2px solid var(--forest-mint);
    outline-offset: 2px;
}

.form-check-label {
    color: var(--text-primary);
    cursor: pointer;
    user-select: none;
}

.link {
    color: var(--forest-mint);
    text-decoration: none;
    transition: color 0.3s ease;
}

.link:hover {
    color: var(--forest-light);
    text-decoration: underline;
}

.link:focus {
    outline: 2px solid var(--forest-mint);
    outline-offset: 2px;
    border-radius: 0.25rem;
}

.btn-block {
    width: 100%;
}

.btn-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--glass-border);
    color: var(--text-secondary);
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
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

.dev-notice {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(251, 191, 36, 0.1);
    border: 1px solid rgba(251, 191, 36, 0.3);
    border-radius: 0.5rem;
    color: #fbbf24;
    font-size: 0.875rem;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 480px) {
    .auth-card {
        padding: 1.5rem;
    }
    
    .auth-title {
        font-size: 1.5rem;
    }
    
    .form-row.space-between {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

/* Focus Visible for Keyboard Navigation */
*:focus-visible {
    outline: 2px solid var(--forest-mint);
    outline-offset: 2px;
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
    .form-control {
        border-width: 2px;
    }
    
    .alert {
        border-width: 2px;
    }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<script>
console.log('Login page script loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing login form');
    const form = document.getElementById('login-form');
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
    
    // Form submission
    if (!form) {
        console.error('Login form not found!');
        return;
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Clear previous errors
        clearErrors();
        clearAlert();
        
        // Validate form
        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('password').value;
        const remember = document.getElementById('remember').checked;
        
        console.log('Login attempt for:', login);
        
        let hasErrors = false;
        
        if (!login) {
            showError('login', 'Email or username is required');
            hasErrors = true;
        }
        
        if (!password) {
            showError('password', 'Password is required');
            hasErrors = true;
        }
        
        if (hasErrors) {
            console.log('Validation errors, stopping');
            return;
        }
        
        // Show loading state
        setLoading(true);
        
        try {
            // Get CSRF token - for now we'll skip it since it's disabled
            const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            
            console.log('Sending login request to API...');
            
            // Submit login request
            const response = await fetch('<?php echo BTT_API_URL; ?>/?route=auth&id=login', {
                method: 'POST',
                credentials: 'include', // Include cookies
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    login: login,
                    password: password,
                    remember: remember,
                    csrf_token: csrfToken
                })
            });
            
            console.log('Response status:', response.status);
            
            const result = await response.json();
            console.log('API response:', result);
            
            if (result.success) {
                showAlert('Login successful! Redirecting...', 'success');
                console.log('Login successful, redirecting...');
                
                // Update CSRF token if provided (check both locations)
                const newCsrfToken = result.csrf_token || (result.data && result.data.csrf_token);
                if (newCsrfToken) {
                    window.csrfToken = newCsrfToken;
                }
                
                // Redirect after short delay
                setTimeout(() => {
                    const returnTo = new URLSearchParams(window.location.search).get('returnTo');
                    const redirectUrl = returnTo || '<?php echo BASE_URL; ?>/dashboard';
                    console.log('Redirecting to:', redirectUrl);
                    window.location.href = redirectUrl;
                }, 500);
            } else {
                console.log('Login failed:', result.message);
                // Show error message
                if (result.errors) {
                    // Field-specific errors
                    Object.keys(result.errors).forEach(field => {
                        showError(field, result.errors[field]);
                    });
                } else {
                    // General error
                    showAlert(result.message || 'Login failed. Please try again.', 'error');
                }
                setLoading(false);
            }
        } catch (error) {
            console.error('Login error:', error);
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
            
            // Announce error for screen readers
            errorElement.setAttribute('aria-live', 'polite');
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
        
        // Auto-dismiss after 5 seconds for non-success messages
        if (type !== 'success') {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
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
                if (el !== submitBtn) el.disabled = true;
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
    
    // Handle Enter key in form fields
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });
});
</script>

<?php require_once BASE_PATH . '/public/includes/template-footer.php'; ?>
