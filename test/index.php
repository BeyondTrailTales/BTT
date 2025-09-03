<?php
/**
 * BeyondTrailTales - Enhanced Test Suite Dashboard
 * Central hub for all testing tools and features
 */

// Set page metadata
$pageTitle = 'Test Suite Dashboard';
$pageDescription = 'Comprehensive testing tools for BeyondTrailTales';
$pageId = 'test-index';

// Include test header (handles auth and bootstrap)
require_once __DIR__ . '/includes/header.php';
?>

<div class="test-container">
    <!-- Warning Banner -->
    <div class="warning-banner">
        <div class="warning-icon">⚠️</div>
        <div class="warning-content">
            <h2>Development Test Suite</h2>
            <p>These pages are for testing purposes only and should not be used in production.</p>
            <p>Some tests may modify data or perform actions that affect the application state.</p>
        </div>
    </div>

    <!-- Test Categories -->
    <section class="test-section">
        <h2 class="section-title">Live Testing Tools</h2>
        
        <div class="test-grid">
            <!-- API Testing -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">🔌</span>
                    API Testing
                </h3>
                <div class="test-list">
                    <a href="/BTT/test/api-tester.php" class="test-item">
                        <span class="test-name">API Endpoint Tester</span>
                        <span class="test-desc">Test live API endpoints with real data</span>
                    </a>
                </div>
            </div>
            
            <!-- Database Testing -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">💾</span>
                    Database Testing
                </h3>
                <div class="test-list">
                    <a href="/BTT/test/db-checker.php" class="test-item">
                        <span class="test-name">Database Integrity Checker</span>
                        <span class="test-desc">Check live database health and relationships</span>
                    </a>
                    <a href="/BTT/test/seed.php" class="test-item">
                        <span class="test-name">Database Seeder</span>
                        <span class="test-desc">Populate database with test data</span>
                    </a>
                </div>
            </div>
            
            <!-- Performance Testing -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">⚡</span>
                    Performance Testing
                </h3>
                <div class="test-list">
                    <a href="/BTT/test/performance.php" class="test-item">
                        <span class="test-name">Performance Benchmarks</span>
                        <span class="test-desc">Test live application speed and response times</span>
                    </a>
                </div>
            </div>
            <!-- Gamification Tests -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">🎮</span>
                    Gamification System
                </h3>
                <div class="test-list">
                    <a href="<?php echo route_url('test/gamification-test.php'); ?>" class="test-item">
                        <span class="test-name">Gamification Test Suite</span>
                        <span class="test-desc">Test XP, levels, badges, and achievements</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/gamification/complete-test.html'); ?>" class="test-item">
                        <span class="test-name">Complete Gamification Test</span>
                        <span class="test-desc">Full gamification system test page</span>
                    </a>
                </div>
            </div>

            <!-- Component Tests -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">🧩</span>
                    Component Tests
                </h3>
                <div class="test-list">
                    <a href="<?php echo route_url('test/visual-test.php'); ?>" class="test-item">
                        <span class="test-name">🎨 Visual Design Test</span>
                        <span class="test-desc">Test enhanced forms, z-index, spacing & responsiveness</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/smart-packing-demo.php'); ?>" class="test-item">
                        <span class="test-name">Smart Packing Demo</span>
                        <span class="test-desc">Test the smart packing assistant features</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/test.php'); ?>" class="test-item">
                        <span class="test-name">General Test Page</span>
                        <span class="test-desc">Basic functionality tests</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/test.html'); ?>" class="test-item">
                        <span class="test-name">Static Test Page</span>
                        <span class="test-desc">HTML/CSS/JS tests without PHP</span>
                    </a>
                </div>
            </div>

            <!-- Debug Tools -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">🔧</span>
                    Debug Tools
                </h3>
                <div class="test-list">
                    <a href="<?php echo route_url('test/debug-dashboard.php'); ?>" class="test-item">
                        <span class="test-name">Debug Dashboard</span>
                        <span class="test-desc">System information and debugging tools</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/comprehensive-test.php'); ?>" class="test-item">
                        <span class="test-name">🧪 Comprehensive Test Suite</span>
                        <span class="test-desc">Run all tests and check for errors</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/seed.php'); ?>" class="test-item">
                        <span class="test-name">Database Seeder</span>
                        <span class="test-desc">Populate database with test data</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/verify_enhancements.html'); ?>" class="test-item">
                        <span class="test-name">Enhancement Verification</span>
                        <span class="test-desc">Verify recent enhancements are working</span>
                    </a>
                </div>
            </div>

            <!-- Reports -->
            <div class="test-category">
                <h3 class="category-title">
                    <span class="category-icon">📊</span>
                    Reports & Audits
                </h3>
                <div class="test-list">
                    <a href="<?php echo route_url('test/audit/spacing-baseline.md'); ?>" class="test-item" target="_blank">
                        <span class="test-name">Spacing Baseline Audit</span>
                        <span class="test-desc">CSS spacing audit documentation</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/gamification/audit-notes.md'); ?>" class="test-item" target="_blank">
                        <span class="test-name">Gamification Audit Notes</span>
                        <span class="test-desc">Gamification system audit results</span>
                    </a>
                    
                    <a href="<?php echo route_url('test/reports/api_gap_report.md'); ?>" class="test-item" target="_blank">
                        <span class="test-name">API Gap Report</span>
                        <span class="test-desc">API implementation status report</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="test-section">
        <h2 class="section-title">Quick Actions</h2>
        
        <div class="action-buttons">
            <button onclick="clearLocalStorage()" class="btn btn-warning">
                <span class="btn-icon">🗑️</span>
                Clear Local Storage
            </button>
            
            <button onclick="resetSession()" class="btn btn-warning">
                <span class="btn-icon">🔄</span>
                Reset Session
            </button>
            
            <a href="<?php echo route_url('api'); ?>" class="btn btn-secondary" target="_blank">
                <span class="btn-icon">📡</span>
                API Endpoint
            </a>
            
            <a href="<?php echo route_url('public/ui-ux-guide.php'); ?>" class="btn btn-secondary">
                <span class="btn-icon">🎨</span>
                Design Guide
            </a>
        </div>
    </section>
</div>

<style>
/* Test Page Styles */
.test-container {
    padding: var(--space-4) 0;
    max-width: 1200px;
    margin: 0 auto;
}

/* Warning Banner */
.warning-banner {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(239, 68, 68, 0.1));
    border: 2px solid var(--forest-honey);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-8);
    display: flex;
    gap: var(--space-4);
    align-items: flex-start;
}

.warning-icon {
    font-size: 3rem;
    flex-shrink: 0;
}

.warning-content h2 {
    color: var(--forest-honey);
    margin-bottom: var(--space-2);
}

.warning-content p {
    color: var(--text-secondary);
    margin-bottom: var(--space-2);
}

.warning-content p:last-child {
    margin-bottom: 0;
}

/* Test Sections */
.test-section {
    margin-bottom: var(--space-8);
}

.section-title {
    font-size: var(--text-2xl);
    color: var(--forest-leaf);
    margin-bottom: var(--space-4);
}

/* Test Grid */
.test-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-6);
}

.test-category {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
}

.category-title {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-lg);
    color: var(--forest-mint);
    margin-bottom: var(--space-3);
    padding-bottom: var(--space-2);
    border-bottom: 1px solid var(--glass-border);
}

.category-icon {
    font-size: 1.5rem;
}

.test-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.test-item {
    display: block;
    padding: var(--space-3);
    background: var(--forest-canopy);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: var(--transition-all);
    border: 1px solid transparent;
}

.test-item:hover {
    background: var(--glass-bg-hover);
    border-color: var(--forest-fern);
    transform: translateX(4px);
}

.test-name {
    display: block;
    color: var(--text-primary);
    font-weight: var(--font-semibold);
    margin-bottom: var(--space-1);
}

.test-desc {
    display: block;
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
}

.btn-warning {
    background: linear-gradient(135deg, var(--forest-honey), var(--forest-berry));
    color: var(--forest-deep);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow-md);
}

/* Responsive */
@media (max-width: 768px) {
    .test-grid {
        grid-template-columns: 1fr;
    }
    
    .warning-banner {
        flex-direction: column;
        text-align: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}
</style>

<script>
function clearLocalStorage() {
    if (confirm('This will clear all local storage data. Continue?')) {
        localStorage.clear();
        alert('Local storage cleared successfully!');
        location.reload();
    }
}

function resetSession() {
    if (confirm('This will reset your session data. Continue?')) {
        fetch('<?php echo route_url("api"); ?>?action=reset-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(() => {
            alert('Session reset successfully!');
            location.reload();
        }).catch(error => {
            alert('Error resetting session: ' + error.message);
        });
    }
}

// Add warning to page title
document.addEventListener('DOMContentLoaded', function() {
    // Flash warning in console
    console.warn('%c⚠️ TEST ENVIRONMENT ⚠️', 'font-size: 20px; color: orange; font-weight: bold;');
    console.warn('This is a test page. Do not use in production!');
});
</script>

<?php
// Include test footer
require_once __DIR__ . '/includes/footer.php';
?>
