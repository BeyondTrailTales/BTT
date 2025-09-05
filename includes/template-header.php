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

// Feature flag for UX refresh - can be toggled via session or cookie
$uxRefreshEnabled = $_SESSION['ux_refresh'] ?? $_COOKIE['ux_refresh'] ?? true; // Default to true for development
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - BeyondTrailTales</title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    
    <!-- Duolingo Forest Theme System -->
    <link rel="stylesheet" href="<?php echo asset_url('css/theme/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/theme/components.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/theme/navigation.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/theme/pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/theme/animations.css'); ?>">
    
    <!-- Clean Unified Design System - Global Application -->
    <link rel="stylesheet" href="<?php echo asset_url('css/btt-unified-clean.css'); ?>">
    
    <?php if ($isTestPage): ?>
    <meta name="robots" content="noindex,nofollow">
    <?php endif; ?>
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo page_meta('canonical'); ?>">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#2d5a3d">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    
    <!-- Page-specific styles -->
    <?php if (isset($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
        <link rel="stylesheet" href="<?php echo asset_url($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Navigation JavaScript -->
    <script src="<?php echo asset_url('js/duolingo-forest-nav.js'); ?>" defer></script>
</head>
<?php 
// Get user level/XP data for gamification (mock data for now)
$userLevel = 5;
$userXP = 340;
$userXPToNext = 500;
$xpPercentage = ($userXP / $userXPToNext) * 100;

// Build body classes
$bodyClasses = ['duolingo-forest-theme', 'btt-app'];
if ($uxRefreshEnabled) {
    $bodyClasses[] = 'ux-refresh';
}
?>
<body data-page="<?php echo e($pageId); ?>" class="<?php echo implode(' ', $bodyClasses); ?>">
    <!-- Skip Navigation Link for Accessibility -->
    <a href="#main-content" class="skip-navigation">Skip to main content</a>
    
    <!-- Floating Forest Leaves Background -->
    <div class="floating-leaves" aria-hidden="true">
        <div class="leaf" style="left: 10%; animation-delay: 0s;">🍃</div>
        <div class="leaf" style="left: 20%; animation-delay: 3s;">🌿</div>
        <div class="leaf" style="left: 35%; animation-delay: 7s;">🍃</div>
        <div class="leaf" style="left: 50%; animation-delay: 12s;">🌱</div>
        <div class="leaf" style="left: 65%; animation-delay: 5s;">🍃</div>
        <div class="leaf" style="left: 80%; animation-delay: 9s;">🌿</div>
        <div class="leaf" style="left: 90%; animation-delay: 15s;">🍃</div>
    </div>
    
    <div class="page-wrapper">
        <!-- Gamified Forest Navigation -->
        <nav class="forest-nav" role="navigation" aria-label="Main navigation">
            <div class="nav-container">
                <!-- Brand/Logo -->
                <a href="<?php echo route_url(); ?>" class="nav-brand" aria-label="BeyondTrailTales Home">
                    <span class="nav-brand-icon">🌲</span>
                    <span class="nav-brand-text">BeyondTrailTales</span>
                </a>
                
                <!-- Section Navigation -->
                <ul class="nav-sections">
                    <li class="nav-section-item">
                        <a href="<?php echo route_url('dashboard'); ?>" 
                           class="nav-section-link <?php echo active_class('dashboard'); ?>" 
                           data-section="dashboard"
                           aria-current="<?php echo aria_current('dashboard'); ?>">
                            <span class="nav-section-icon">🏔️</span>
                            <span>Trailhead</span>
                        </a>
                    </li>
                    <li class="nav-section-item">
                        <a href="<?php echo route_url('trips'); ?>" 
                           class="nav-section-link <?php echo active_class('trips'); ?>"
                           data-section="adventures" 
                           aria-current="<?php echo aria_current('trips'); ?>">
                            <span class="nav-section-icon">🗺️</span>
                            <span>Adventures</span>
                        </a>
                    </li>
                    <li class="nav-section-item">
                        <a href="<?php echo route_url('backpacks'); ?>" 
                           class="nav-section-link <?php echo active_class('backpacks'); ?>"
                           data-section="backpacks" 
                           aria-current="<?php echo aria_current('backpacks'); ?>">
                            <span class="nav-section-icon">🎒</span>
                            <span>Backpacks</span>
                        </a>
                    </li>
                    <li class="nav-section-item">
                        <a href="<?php echo route_url('gear'); ?>" 
                           class="nav-section-link <?php echo active_class('gear'); ?>"
                           data-section="gear" 
                           aria-current="<?php echo aria_current('gear'); ?>">
                            <span class="nav-section-icon">📦</span>
                            <span>Gear</span>
                        </a>
                    </li>
                </ul>
                
                <!-- User Gamification & Quick Actions -->
                <div class="nav-user-gamified">
                    <?php if ($currentUser): ?>
                        <!-- User Level Display -->
                        <div class="user-level-display">
                            <div class="user-avatar-nav">
                                <?php echo strtoupper(substr($currentUser['username'] ?? $currentUser['email'], 0, 1)); ?>
                            </div>
                            <div class="user-level-info">
                                <div class="user-level-number">Level <?php echo $userLevel; ?></div>
                                <div class="user-xp-mini">
                                    <div class="user-xp-fill" style="width: <?php echo $xpPercentage; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions Dropdown -->
                        <div class="nav-quick-actions">
                            <button class="quick-actions-trigger" aria-expanded="false" aria-controls="quick-actions-menu">
                                <span class="quick-actions-icon">⚡</span>
                                <span>Quick</span>
                            </button>
                            <div class="quick-actions-menu" id="quick-actions-menu">
                                <div class="quick-actions-grid">
                                    <a href="<?php echo route_url('trips'); ?>?action=new" class="quick-action-item" data-section="adventures">
                                        <div class="quick-action-icon">🗺️</div>
                                        <div class="quick-action-text">Plan Trip</div>
                                    </a>
                                    <a href="<?php echo route_url('backpacks'); ?>?action=new" class="quick-action-item" data-section="backpacks">
                                        <div class="quick-action-icon">🎒</div>
                                        <div class="quick-action-text">New Pack</div>
                                    </a>
                                    <a href="<?php echo route_url('gear'); ?>?action=add" class="quick-action-item" data-section="gear">
                                        <div class="quick-action-icon">➕</div>
                                        <div class="quick-action-text">Add Gear</div>
                                    </a>
                                    <a href="<?php echo route_url('dashboard'); ?>?view=stats" class="quick-action-item" data-section="dashboard">
                                        <div class="quick-action-icon">📊</div>
                                        <div class="quick-action-text">Stats</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu Dropdown -->
                        <div class="dropdown">
                            <button class="dropdown-trigger" aria-expanded="false" aria-controls="user-dropdown">
                                <span><?php echo htmlspecialchars($currentUser['username'] ?? 'User'); ?></span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <div class="dropdown-menu" id="user-dropdown">
                                <a href="<?php echo route_url('profile'); ?>" class="dropdown-item">
                                    👤 Profile
                                </a>
                                <a href="<?php echo route_url('settings'); ?>" class="dropdown-item">
                                    ⚙️ Settings
                                </a>
                                <a href="<?php echo route_url('dashboard'); ?>?view=achievements" class="dropdown-item">
                                    🏆 Achievements
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="post" action="<?php echo BTT_API_URL; ?>/?route=auth&id=logout" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <button type="submit" class="dropdown-item">
                                        🚪 Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Authentication Links -->
                        <div class="nav-auth-links">
                            <a href="<?php echo route_url('public/auth/login.php'); ?>" class="btn btn-secondary">
                                Login
                            </a>
                            <a href="<?php echo route_url('public/auth/register.php'); ?>" class="btn btn-primary">
                                Start Adventure
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-nav-toggle" 
                        aria-label="Toggle navigation menu" 
                        aria-expanded="false"
                        aria-controls="mobile-nav">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12h18M3 6h18M3 18h18"/>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation Menu -->
            <div id="mobile-nav" class="mobile-nav-menu">
                <div class="mobile-nav-content">
                    <nav class="mobile-nav-sections">
                        <a href="<?php echo route_url('dashboard'); ?>" 
                           class="mobile-nav-link <?php echo active_class('dashboard'); ?>" 
                           data-section="dashboard">
                            <span class="mobile-nav-icon">🏔️</span>
                            <span>Trailhead</span>
                        </a>
                        <a href="<?php echo route_url('trips'); ?>" 
                           class="mobile-nav-link <?php echo active_class('trips'); ?>"
                           data-section="adventures">
                            <span class="mobile-nav-icon">🗺️</span>
                            <span>Adventures</span>
                        </a>
                        <a href="<?php echo route_url('backpacks'); ?>" 
                           class="mobile-nav-link <?php echo active_class('backpacks'); ?>"
                           data-section="backpacks">
                            <span class="mobile-nav-icon">🎒</span>
                            <span>Backpacks</span>
                        </a>
                        <a href="<?php echo route_url('gear'); ?>" 
                           class="mobile-nav-link <?php echo active_class('gear'); ?>"
                           data-section="gear">
                            <span class="mobile-nav-icon">📦</span>
                            <span>Gear</span>
                        </a>
                    </nav>
                    
                    <?php if ($currentUser): ?>
                        <div class="mobile-user-section">
                            <div class="mobile-user-info">
                                <div class="mobile-user-avatar">
                                    <?php echo strtoupper(substr($currentUser['username'] ?? $currentUser['email'], 0, 1)); ?>
                                </div>
                                <div class="mobile-user-details">
                                    <h3 class="mobile-user-name"><?php echo htmlspecialchars($currentUser['username'] ?? 'User'); ?></h3>
                                    <p class="mobile-user-level">Level <?php echo $userLevel; ?> • <?php echo $userXP; ?>/<?php echo $userXPToNext; ?> XP</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <!-- Main Page Content -->
        <main id="main-content" class="page-content" role="main">
            <div class="page-container">
                <!-- Page content will be inserted here -->
