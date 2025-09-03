<?php
/**
 * Test Suite Header
 * Forest-themed, ADA-compliant layout
 */

// Bootstrap application
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

// Check for production environment
if (defined('APP_ENV') && APP_ENV === 'production') {
    http_response_code(403);
    die('Test suite is disabled in production environment');
}

// Initialize test logger
require_once dirname(__DIR__) . '/tools/logger.php';
$logger = \BTT\Test\Tools\TestLogger::getInstance();

// Get current page info
$pageTitle = $pageTitle ?? 'Test Suite';
$pageDescription = $pageDescription ?? 'BeyondTrailTales Test Suite';
$pageId = $pageId ?? 'test-page';

// Get storage info
require_once dirname(__DIR__) . '/tools/storage.php';
$storage = \BTT\Test\Tools\Storage::getInstance();
$storageInfo = $storage->getStorageInfo();
?>
<!DOCTYPE html>
<html lang="en" data-theme="forest">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - BTT Test Suite</title>
    
    <!-- Forest Design System -->
    <link rel="stylesheet" href="/BTT/assets/css/forest-tokens.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-base.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-components.css">
    <link rel="stylesheet" href="/BTT/assets/css/forest-animations.css">
    
    <!-- Test Suite Styles -->
    <link rel="stylesheet" href="/BTT/test/assets/css/test.css">
    
    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="forest-theme test-suite" data-page-id="<?php echo htmlspecialchars($pageId); ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Test Suite Header -->
    <header class="test-header" role="banner">
        <div class="container">
            <div class="header-content">
                <div class="header-brand">
                    <a href="/BTT/test/" class="brand-link" aria-label="BTT Test Suite Home">
                        <span class="brand-icon" aria-hidden="true">🧪</span>
                        <span class="brand-text">BTT Test Suite</span>
                    </a>
                </div>
                
                <nav class="header-nav" role="navigation" aria-label="Test suite navigation">
                    <ul class="nav-list">
                        <li><a href="/BTT/test/" <?php echo $pageId === 'test-index' ? 'aria-current="page"' : ''; ?>>Dashboard</a></li>
                        <li><a href="/BTT/test/api-tester.php" <?php echo $pageId === 'api-tester' ? 'aria-current="page"' : ''; ?>>API Tester</a></li>
                        <li><a href="/BTT/test/ui-showcase.php" <?php echo $pageId === 'ui-showcase' ? 'aria-current="page"' : ''; ?>>UI Components</a></li>
                        <li><a href="/BTT/test/db-checker.php" <?php echo $pageId === 'db-checker' ? 'aria-current="page"' : ''; ?>>Database</a></li>
                        <li><a href="/BTT/test/gamification-test.php" <?php echo $pageId === 'gamification-test' ? 'aria-current="page"' : ''; ?>>Gamification</a></li>
                        <li><a href="/BTT/test/performance.php" <?php echo $pageId === 'performance' ? 'aria-current="page"' : ''; ?>>Performance</a></li>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <!-- Storage Driver Indicator -->
                    <div class="storage-indicator" aria-label="Storage driver: <?php echo $storageInfo['driver']; ?>">
                        <span class="indicator-icon" aria-hidden="true">💾</span>
                        <span class="indicator-text"><?php echo strtoupper($storageInfo['driver']); ?></span>
                    </div>
                    
                    <!-- Theme Toggle -->
                    <button 
                        class="btn-icon theme-toggle" 
                        onclick="toggleTheme()"
                        aria-label="Toggle theme"
                        title="Toggle theme">
                        <span class="icon-light" aria-hidden="true">☀️</span>
                        <span class="icon-dark" aria-hidden="true">🌙</span>
                    </button>
                    
                    <!-- Back to App -->
                    <a href="/BTT/" class="btn btn-secondary btn-sm" aria-label="Back to main application">
                        <span aria-hidden="true">←</span> Back to App
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Environment Banner -->
    <?php if (defined('BTT_DEBUG') && BTT_DEBUG): ?>
    <div class="env-banner" role="status" aria-live="polite">
        <div class="container">
            <div class="env-content">
                <span class="env-badge">🔧 Development Mode</span>
                <span class="env-info">
                    Storage: <strong><?php echo $storageInfo['driver']; ?></strong> | 
                    Path: <code><?php echo htmlspecialchars($storageInfo['path']); ?></code> | 
                    Tables: <strong><?php echo count($storageInfo['tables']); ?></strong>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Area -->
    <main id="main-content" class="test-main" role="main">
        <div class="container">
