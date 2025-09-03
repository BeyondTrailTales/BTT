<?php
/**
 * BeyondTrailTales - Unified Template Header
 * 
 * This header provides:
 * - Forest theme design system
 * - Responsive navigation
 * - ADA compliance with skip links
 * - Integrated gamification bar
 * 
 * Page variables (set before including):
 * - $pageTitle: Page title (defaults to BTT_APP_NAME)
 * - $pageDescription: Meta description
 * - $pageId: Body data-page attribute
 */

// Ensure bootstrap is loaded
if (!defined('BASE_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';
}

// Load authentication service
use App\Services\AuthService;

// Get current user if logged in
$currentUser = null;
if (AuthService::isAuthenticated()) {
    $currentUser = AuthService::getCurrentUser();
}

// Set defaults for page variables
$pageTitle = $pageTitle ?? page_meta('title', BTT_APP_NAME);
$pageDescription = $pageDescription ?? page_meta('description', BTT_APP_DESCRIPTION);
$pageId = $pageId ?? page_meta('id', 'home');
$isTestPage = is_test_page();
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - BeyondTrailTales</title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    
    <?php if ($isTestPage): ?>
    <meta name="robots" content="noindex,nofollow">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo page_meta('canonical'); ?>">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#0a2818">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Forest Theme CSS - Load in correct order -->
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-tokens.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-base.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-components.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-theme.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-animations.css'); ?>">
    <!-- Enhanced Forest CSS for Professional Polish -->
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-enhanced.css'); ?>">
    <!-- Premium Forest Tokens and Components -->
    <link rel="stylesheet" href="<?php echo asset_url('css/forest-premium.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/buttons-premium.css'); ?>">
    <!-- Authentication Navigation Styles -->
    <link rel="stylesheet" href="<?php echo asset_url('css/auth-nav.css'); ?>">
    <!-- Dropdown Arrow Fix - Must load after forest-enhanced.css -->
    <link rel="stylesheet" href="<?php echo asset_url('css/dropdown-fix.css'); ?>">
    <!-- Loading Animations and Skeleton Screens -->
    <!-- <link rel="stylesheet" href="<?php echo asset_url('css/skeleton-loader.css'); ?>"> -->
    <!-- Premium Card System -->
    <!-- <link rel="stylesheet" href="<?php echo asset_url('css/card-system.css'); ?>"> -->
    
    <!-- Page-specific styles -->
    <?php if (isset($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?php echo asset_url($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* CSS Variable Compatibility Layer */
        :root {
            /* Map premium variables to token variables for consistency */
            --forest-mint: #4ade80;  /* Ensure this is always defined */
            --forest-leaf: #22c55e;
            --forest-glow: #86efac;
            --forest-canopy: var(--forest-moss, #2d5a3d);
            --surface-primary: var(--bg-surface, rgba(10, 40, 24, 0.95));
            --surface-secondary: rgba(15, 50, 30, 0.9);
            --glass-bg: var(--bg-overlay, rgba(10, 40, 24, 0.85));
            --glass-bg-hover: rgba(74, 222, 128, 0.08);
            --glass-border: rgba(74, 222, 128, 0.2);
            --glass-blur: blur(10px);
            --glass-blur-heavy: blur(20px);
            --text-inverse: var(--forest-deep, #0a2818);
            --duration-standard: 250ms;
            --duration-quick: 150ms;
            --ease-standard: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --transition-all: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-full: 9999px;
        }
        
        /* Global Forest Theme Application */
        body {
            background: var(--forest-deep, #0a2818);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(76, 175, 80, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 195, 74, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(46, 125, 50, 0.08) 0%, transparent 50%);
            background-attachment: fixed;
            color: var(--text-primary, #f8f9fa);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Forest Overlay Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 35px,
                    rgba(76, 175, 80, 0.02) 35px,
                    rgba(76, 175, 80, 0.02) 70px
                );
            pointer-events: none;
            z-index: 0;
        }
        
        /* Ensure content is above the pattern */
        .main-wrapper {
            position: relative;
            z-index: 1;
        }
        
        /* Unified Navigation Styles */
        .unified-nav {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur-heavy);
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 10; /* Lowered significantly to stay behind all modals and popups */
            transition: var(--transition-all);
        }
        
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--space-4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 60px;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            text-decoration: none;
            color: var(--forest-mint);
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            transition: var(--transition-all);
        }
        
        .nav-brand:hover {
            color: var(--forest-leaf);
            transform: translateY(-1px);
        }
        
        .nav-brand-icon {
            font-size: 1.5rem;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) var(--space-3);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: var(--transition-all);
            font-weight: var(--font-medium);
            position: relative;
        }
        
        .nav-link:hover {
            background: var(--glass-bg-hover);
            color: var(--forest-mint);
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            color: var(--forest-leaf);
            background: rgba(var(--forest-leaf-rgb), 0.1);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: var(--space-3);
            right: var(--space-3);
            height: 2px;
            background: var(--gradient-success);
            border-radius: var(--radius-full);
        }
        
        .nav-icon {
            font-size: 1.2rem;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--text-primary);
            padding: var(--space-2);
            cursor: pointer;
            border-radius: var(--radius-lg);
            transition: var(--transition-all);
        }
        
        .mobile-menu-toggle:hover {
            background: var(--glass-bg-hover);
        }
        
        .mobile-menu-toggle:focus {
            outline: 2px solid var(--forest-mint);
            outline-offset: 2px;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            background: var(--forest-canopy);
            backdrop-filter: var(--glass-blur-heavy);
            border-bottom: 1px solid var(--glass-border);
            padding: var(--space-4);
            box-shadow: var(--shadow-lg);
            z-index: 9; /* Lower than trip form panel and other modals */
            max-height: calc(100vh - 60px);
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        .mobile-menu .nav-menu {
            flex-direction: column;
            align-items: stretch;
            gap: var(--space-1);
        }
        
        .mobile-menu .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: var(--space-3) var(--space-4);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                gap: var(--space-1);
            }
            
            .desktop-nav {
                display: none;
            }
        }
        
        /* Skip Link */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--forest-leaf);
            color: var(--forest-deep);
            padding: var(--space-2) var(--space-4);
            text-decoration: none;
            border-radius: 0 0 var(--radius-lg) 0;
            font-weight: var(--font-bold);
            z-index: 15; /* Above nav but below modals */
            transition: top 0.3s;
        }
        
        .skip-link:focus {
            top: 0;
            outline: 2px solid var(--forest-mint);
            outline-offset: 2px;
        }
        
        /* Main Content Wrapper */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: var(--space-6) 0;
            position: relative;
            z-index: 1;
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }
    </style>
</head>
<body data-page="<?php echo e($pageId); ?>" class="forest-theme">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <div class="main-wrapper">
        <!-- Unified Navigation -->
        <nav class="unified-nav" role="navigation" aria-label="Main navigation">
            <div class="nav-container">
                <a href="<?php echo route_url(); ?>" class="nav-brand" aria-label="BeyondTrailTales Home">
                    <span class="nav-brand-icon">🌲</span>
                    <span>BeyondTrailTales</span>
                </a>
                
                <!-- Desktop Navigation -->
                <ul class="nav-menu desktop-nav">
                    <li class="nav-item">
                        <a href="<?php echo route_url('trips'); ?>" 
                           class="nav-link <?php echo active_class('trips'); ?>"
                           aria-current="<?php echo aria_current('trips'); ?>">
                            <span class="nav-icon">🗺️</span>
                            <span>Trips</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo route_url('backpacks'); ?>" 
                           class="nav-link <?php echo active_class('backpacks'); ?>"
                           aria-current="<?php echo aria_current('backpacks'); ?>">
                            <span class="nav-icon">🎒</span>
                            <span>Backpacks</span>
                        </a>
                    </li>
                </ul>
                
                <!-- Authentication Navigation -->
                <?php if ($currentUser): ?>
                    <!-- User is logged in -->
                    <div class="nav-user-menu">
                        <button class="nav-user-button" aria-expanded="false" aria-controls="user-menu">
                            <span class="nav-user-name"><?php echo htmlspecialchars($currentUser['username'] ?? $currentUser['email']); ?></span>
                            <svg class="icon-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <div class="nav-dropdown" id="user-menu" hidden>
                            <a href="<?php echo route_url('profile'); ?>" class="dropdown-link">Profile</a>
                            <a href="<?php echo route_url('settings'); ?>" class="dropdown-link">Settings</a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?php echo BTT_API_URL; ?>/?route=auth&id=logout" class="logout-form">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <button type="submit" class="dropdown-link logout-btn">Logout</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <div class="nav-auth-links">
                        <a class="nav-link<?php echo active_class('login'); ?>" href="<?php echo route_url('public/auth/login.php'); ?>">Login</a>
                        <a class="nav-link nav-link-primary<?php echo active_class('register'); ?>" href="<?php echo route_url('public/auth/register.php'); ?>">Sign Up</a>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" 
                        aria-label="Toggle navigation menu" 
                        aria-expanded="false"
                        aria-controls="mobile-nav-menu"
                        onclick="toggleMobileMenu(this)">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12h18M3 6h18M3 18h18" />
                    </svg>
                    <span>Menu</span>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-nav-menu" class="mobile-menu" role="region" aria-hidden="true">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?php echo route_url('trips'); ?>" 
                           class="nav-link <?php echo active_class('trips'); ?>"
                           aria-current="<?php echo aria_current('trips'); ?>">
                            <span class="nav-icon">🗺️</span>
                            <span>Trips</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo route_url('backpacks'); ?>" 
                           class="nav-link <?php echo active_class('backpacks'); ?>"
                           aria-current="<?php echo aria_current('backpacks'); ?>">
                            <span class="nav-icon">🎒</span>
                            <span>Backpacks</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main id="main-content" class="main-content" role="main">
            <div class="container">
                <!-- Page content will be inserted here -->
