<?php
/**
 * BeyondTrailTales - Forgot Password Page
 * 
 * ADA-compliant password reset request form
 */

// Load bootstrap
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

// If already logged in, redirect
if (is_authenticated()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Page metadata
$pageId = 'auth-forgot-password';
$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your BeyondTrailTales account password';

// Include header
require_once BASE_PATH . '/public/includes/template-header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Logo/Title -->
        <div class="auth-header">
            <h1 class="auth-title">
                <span aria-hidden="true">🔐</span>
                Forgot Your Password?
            </h1>
            <p class="auth-subtitle">Enter your email to receive a password reset link</p>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" role="alert" aria-live="polite" aria-atomic="true"></div>

        <!-- Reset Request Form -->
        <form id="forgot-form" class="auth-form" novalidate>
            <?php echo csrf_field(); ?>
            
            <!-- Email Field -->
            <div class="form-group">
                <label for="email" class="form-label">
                    Email Address
                    <span class="required" aria-label="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control"
                    required
                    aria-required="true"
                    aria-describedby="email-error email-help"
                    autocomplete="email"
                    autofocus
                />
                <span id="email-error" class="error-text" role="alert"></span>
                <span id="email-help" class="help-text">
                    Enter the email associated with your account
                </span>
            </div>

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="btn btn-primary btn-block"
                id="submit-btn"
            >
                <span class="btn-text">Send Reset Link</span>
                <span class="btn-loader" style="display:none;">
                    <span class="spinner"></span>
                    Sending...
                </span>
            </button>

            <!-- Back to Login -->
            <div class="auth-footer">
                <p>
                    Remember your password? 
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

.help-text {
    display: block;
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

/* Other styles inherited from login page */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgot-form');
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous errors
        clearErrors();
        clearAlert();
        
        // Validate email
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            showError('email', 'Email address is required');
            return;
        }
        
        if (!isValidEmail(email)) {
            showError('email', 'Please enter a valid email address');
            return;
        }
        
        // Show loading state
        setLoading(true);
        
        try {
            // Get CSRF token
            const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
            
            // Submit request
            const response = await fetch('<?php echo BTT_API_URL; ?>/?route=auth&id=request-password-reset', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    email: email,
                    csrf_token: csrfToken
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('Check your email! If an account exists with that email, we\'ve sent a password reset link.', 'success');
                form.reset();
            } else {
                showAlert(result.message || 'Unable to process request. Please try again.', 'error');
            }
            
            setLoading(false);
            
        } catch (error) {
            console.error('Password reset error:', error);
            showAlert('An error occurred. Please try again.', 'error');
            setLoading(false);
        }
    });
    
    // Helper functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
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
            form.querySelectorAll('input').forEach(el => el.disabled = true);
        } else {
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
            submitBtn.disabled = false;
            form.querySelectorAll('input').forEach(el => el.disabled = false);
        }
    }
});
</script>

<?php require_once BASE_PATH . '/public/includes/template-footer.php'; ?>
