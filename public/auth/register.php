<?php
/**
 * BeyondTrailTales Registration Page
 * 
 * User registration with accessibility and security features
 */

// Load bootstrap and configuration
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

// Redirect if already logged in
if (is_authenticated()) {
    header('Location: ' . BTT_PUBLIC_URL . '/dashboard.php');
    exit;
}

// Page metadata
$pageTitle = 'Sign Up - BeyondTrailTales';
$pageId = 'register';
$pageDescription = 'Create your BeyondTrailTales account to start planning trips';
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-tokens.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-base.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-components.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-animations.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/auth-nav.css">
    
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #0f0f0f, #1a1a1a);
        }
        
        .auth-card {
            background: var(--color-surface-raised);
            border-radius: 1rem;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            display: block;
            text-decoration: none;
        }
        
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 1rem 0 0.5rem;
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--color-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 0.5rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .form-input.error {
            border-color: var(--color-error);
        }
        
        .form-error {
            color: var(--color-error);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .password-requirements {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 0.5rem;
            font-size: 0.75rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            margin: 0.25rem 0;
        }
        
        .requirement.met {
            color: var(--color-success);
        }
        
        .requirement-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        
        .form-checkbox {
            display: flex;
            align-items: start;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .form-checkbox input {
            margin-top: 0.25rem;
        }
        
        .form-checkbox label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .form-checkbox a {
            color: var(--color-primary);
            text-decoration: none;
        }
        
        .form-checkbox a:hover {
            text-decoration: underline;
        }
        
        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-submit:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .auth-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: rgba(239, 83, 80, 0.1);
            color: var(--color-error);
            border: 1px solid rgba(239, 83, 80, 0.2);
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--color-success);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body data-page="<?php echo htmlspecialchars($pageId); ?>">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?php echo BTT_PUBLIC_URL; ?>" class="auth-logo">BeyondTrailTales</a>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join the adventure and start planning your trips</p>
            </div>
            
            <form id="registerForm" method="post" action="<?php echo BTT_API_URL; ?>/?route=auth&id=register">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                
                <div id="alertContainer"></div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        required 
                        autocomplete="email"
                        aria-describedby="email-error"
                        placeholder="you@example.com"
                    >
                    <div id="email-error" class="form-error" role="alert"></div>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        required 
                        autocomplete="username"
                        aria-describedby="username-error"
                        placeholder="Choose a username"
                        minlength="3"
                        maxlength="50"
                    >
                    <div id="username-error" class="form-error" role="alert"></div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required 
                        autocomplete="new-password"
                        aria-describedby="password-error password-requirements"
                        placeholder="Create a strong password"
                        minlength="8"
                    >
                    <div id="password-error" class="form-error" role="alert"></div>
                    <div id="password-requirements" class="password-requirements">
                        <div class="requirement" data-req="length">
                            <svg class="requirement-icon" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="8" cy="8" r="3"/>
                            </svg>
                            At least 8 characters
                        </div>
                        <div class="requirement" data-req="uppercase">
                            <svg class="requirement-icon" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="8" cy="8" r="3"/>
                            </svg>
                            One uppercase letter
                        </div>
                        <div class="requirement" data-req="lowercase">
                            <svg class="requirement-icon" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="8" cy="8" r="3"/>
                            </svg>
                            One lowercase letter
                        </div>
                        <div class="requirement" data-req="number">
                            <svg class="requirement-icon" viewBox="0 0 16 16" fill="currentColor">
                                <circle cx="8" cy="8" r="3"/>
                            </svg>
                            One number
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-input" 
                        required 
                        autocomplete="new-password"
                        aria-describedby="password-confirm-error"
                        placeholder="Confirm your password"
                    >
                    <div id="password-confirm-error" class="form-error" role="alert"></div>
                </div>
                
                <div class="form-checkbox">
                    <input 
                        type="checkbox" 
                        id="terms" 
                        name="terms" 
                        required
                        aria-describedby="terms-error"
                    >
                    <label for="terms">
                        I agree to the <a href="/terms" target="_blank">Terms of Service</a> 
                        and <a href="/privacy" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                <div id="terms-error" class="form-error" role="alert"></div>
                
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnText">Create Account</span>
                    <span id="btnLoader" class="loading-spinner" style="display: none;"></span>
                </button>
            </form>
            
            <div class="auth-footer">
                Already have an account? <a href="<?php echo BTT_PUBLIC_URL; ?>/auth/login.php">Sign In</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const alertContainer = document.getElementById('alertContainer');
            
            // Password validation
            const passwordInput = document.getElementById('password');
            const passwordConfirmInput = document.getElementById('password_confirm');
            const requirements = {
                length: (pwd) => pwd.length >= 8,
                uppercase: (pwd) => /[A-Z]/.test(pwd),
                lowercase: (pwd) => /[a-z]/.test(pwd),
                number: (pwd) => /[0-9]/.test(pwd)
            };
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                Object.keys(requirements).forEach(req => {
                    const element = document.querySelector(`[data-req="${req}"]`);
                    if (requirements[req](password)) {
                        element.classList.add('met');
                        element.querySelector('svg').innerHTML = '<path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
                    } else {
                        element.classList.remove('met');
                        element.querySelector('svg').innerHTML = '<circle cx="8" cy="8" r="3"/>';
                    }
                });
            });
            
            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Clear previous errors
                document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
                document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
                alertContainer.innerHTML = '';
                
                // Validate passwords match
                if (passwordInput.value !== passwordConfirmInput.value) {
                    passwordConfirmInput.classList.add('error');
                    document.getElementById('password-confirm-error').textContent = 'Passwords do not match';
                    return;
                }
                
                // Validate password requirements
                const password = passwordInput.value;
                if (!Object.values(requirements).every(req => req(password))) {
                    passwordInput.classList.add('error');
                    document.getElementById('password-error').textContent = 'Password does not meet all requirements';
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                btnText.textContent = 'Creating account...';
                btnLoader.style.display = 'inline-block';
                
                try {
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData);
                    
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alertContainer.innerHTML = `
                            <div class="alert alert-success" role="alert">
                                Account created successfully! Redirecting to login...
                            </div>
                        `;
                        
                        setTimeout(() => {
                            window.location.href = '<?php echo BTT_PUBLIC_URL; ?>/auth/login.php?registered=1';
                        }, 2000);
                    } else {
                        // Show general error message
                        if (result.error) {
                            alertContainer.innerHTML = `
                                <div class="alert alert-error" role="alert">
                                    ${result.error}
                                </div>
                            `;
                        }
                        
                        // Show field-specific errors
                        if (result.errors) {
                            Object.keys(result.errors).forEach(field => {
                                const input = document.getElementById(field);
                                const error = document.getElementById(`${field}-error`);
                                if (input && error) {
                                    input.classList.add('error');
                                    error.textContent = result.errors[field];
                                }
                            });
                        }
                        
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.textContent = 'Create Account';
                        btnLoader.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Registration error:', error);
                    alertContainer.innerHTML = `
                        <div class="alert alert-error" role="alert">
                            An error occurred. Please try again later.
                        </div>
                    `;
                    
                    submitBtn.disabled = false;
                    btnText.textContent = 'Create Account';
                    btnLoader.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
