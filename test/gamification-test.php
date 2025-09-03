<?php
/**
 * Gamification System Test Page
 * Tests all gamification features
 */

require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/classes/Gamification.php';

use BTT\Classes\Gamification;

$pageId = 'gamification-test';
$pageTitle = 'Gamification Test - BeyondTrailTales';
$pageDescription = 'Test gamification features';

// Initialize gamification
$gamification = new Gamification('test_user');

// Handle test actions
$action = $_GET['action'] ?? '';
$message = '';

switch($action) {
    case 'reset':
        $gamification->resetUserData();
        $message = 'User data reset successfully!';
        break;
        
    case 'create_backpack':
        $result = $gamification->awardXP('create_backpack');
        $badges = $gamification->updateStats('backpack_count');
        $message = "Created backpack! Earned {$result['xp_gained']} XP.";
        if (!empty($badges)) {
            $message .= " New badge: {$badges[0]['name']}!";
        }
        break;
        
    case 'create_trip':
        $result = $gamification->awardXP('create_trip');
        $badges = $gamification->updateStats('trip_count');
        $message = "Created trip! Earned {$result['xp_gained']} XP.";
        if (!empty($badges)) {
            $message .= " New badge: {$badges[0]['name']}!";
        }
        break;
        
    case 'add_items':
        for ($i = 0; $i < 10; $i++) {
            $gamification->awardXP('add_item');
        }
        $badges = $gamification->updateStats('total_items_packed', 10);
        $message = "Added 10 items! Earned 100 XP.";
        if (!empty($badges)) {
            $message .= " New badge: {$badges[0]['name']}!";
        }
        break;
        
    case 'daily_visit':
        $streak = $gamification->updateStreak();
        $message = "Daily visit recorded! Current streak: {$streak} days.";
        break;
}

// Get current user data
$userData = $gamification->getUserData();

require_once dirname(__DIR__) . '/public/includes/header.php';
?>

<div class="test-container" style="padding: var(--space-8) 0;">
    <h1 class="text-center" style="margin-bottom: var(--space-8);">
        <span class="text-gradient">Gamification Test Suite</span>
    </h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom: var(--space-4);">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <!-- Current Status -->
    <div class="card-forest" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2>Current Status</h2>
        </div>
        <div class="card-body">
            <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--space-4);">
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">Level</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-leaf);">
                        <?php echo $userData['level']; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">Total XP</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-mint);">
                        <?php echo $userData['xp']; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">Progress</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-fern);">
                        <?php echo $userData['xp_progress']; ?>%
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">Streak</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-honey);">
                        <?php echo $userData['streak_days']; ?> 🔥
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">Badges</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-berry);">
                        <?php echo count($userData['badges']); ?> 🏆
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label" style="color: var(--text-muted); font-size: var(--text-sm);">XP to Next</div>
                    <div class="status-value" style="font-size: var(--text-2xl); font-weight: var(--font-bold); color: var(--forest-lake);">
                        <?php echo $userData['xp_to_next_level']; ?>
                    </div>
                </div>
            </div>
            
            <!-- XP Progress Bar -->
            <div style="margin-top: var(--space-6);">
                <div class="xp-bar-test">
                    <div class="xp-info" style="display: flex; justify-content: space-between; margin-bottom: var(--space-2);">
                        <span>Level <?php echo $userData['level']; ?></span>
                        <span><?php echo $userData['xp']; ?> / <?php echo ($userData['next_level_threshold'] ?? 'MAX'); ?> XP</span>
                    </div>
                    <div class="xp-track" style="height: 30px; background: var(--forest-shadow); border-radius: var(--radius-full); overflow: hidden;">
                        <div class="xp-fill" style="width: <?php echo $userData['xp_progress']; ?>%; height: 100%; background: var(--gradient-success); transition: width 0.5s ease-out;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Test Actions -->
    <div class="card-forest" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2>Test Actions</h2>
        </div>
        <div class="card-body">
            <div class="action-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-3);">
                <a href="?action=create_backpack" class="btn btn-primary">
                    <span class="btn-icon">🎒</span>
                    Create Backpack (+50 XP)
                </a>
                <a href="?action=create_trip" class="btn btn-primary">
                    <span class="btn-icon">🗺️</span>
                    Create Trip (+75 XP)
                </a>
                <a href="?action=add_items" class="btn btn-primary">
                    <span class="btn-icon">📦</span>
                    Add 10 Items (+100 XP)
                </a>
                <a href="?action=daily_visit" class="btn btn-primary">
                    <span class="btn-icon">🔥</span>
                    Daily Visit (+25 XP)
                </a>
                <a href="?action=reset" class="btn btn-danger" onclick="return confirm('Reset all gamification data?')">
                    <span class="btn-icon">🔄</span>
                    Reset All Data
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="card-forest" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2>Statistics</h2>
        </div>
        <div class="card-body">
            <div class="stats-list">
                <?php foreach ($userData['stats'] as $stat => $value): ?>
                <div class="stat-row" style="display: flex; justify-content: space-between; padding: var(--space-2) 0; border-bottom: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary);"><?php echo ucwords(str_replace('_', ' ', $stat)); ?></span>
                    <span style="font-weight: var(--font-bold); color: var(--forest-mint);"><?php echo $value; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Badges -->
    <div class="card-forest">
        <div class="card-header">
            <h2>Badges (<?php echo count($userData['badges']); ?>/<?php echo count($gamification->getAllBadges()); ?>)</h2>
        </div>
        <div class="card-body">
            <div class="badge-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: var(--space-3);">
                <?php foreach ($gamification->getAllBadges() as $badge): ?>
                <div class="badge-test-item <?php echo $badge['earned'] ? 'earned' : 'locked'; ?>" 
                     style="padding: var(--space-3); 
                            background: var(--glass-bg); 
                            border: 2px solid <?php echo $badge['earned'] ? 'var(--forest-leaf)' : 'var(--glass-border)'; ?>; 
                            border-radius: var(--radius-lg); 
                            text-align: center;
                            opacity: <?php echo $badge['earned'] ? '1' : '0.5'; ?>;">
                    <div style="font-size: 2.5rem; margin-bottom: var(--space-2);"><?php echo $badge['icon']; ?></div>
                    <div style="font-weight: var(--font-bold); color: var(--forest-mint); margin-bottom: var(--space-1);">
                        <?php echo $badge['name']; ?>
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-secondary);">
                        <?php echo $badge['description']; ?>
                    </div>
                    <div style="margin-top: var(--space-2); font-size: var(--text-xs);">
                        <?php echo $badge['earned'] ? '✅ Earned' : '🔒 Locked'; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- API Test -->
    <div class="card-forest" style="margin-top: var(--space-6);">
        <div class="card-header">
            <h2>API Test</h2>
        </div>
        <div class="card-body">
            <button onclick="testAPI()" class="btn btn-primary">Test Gamification API</button>
            <div id="api-result" style="margin-top: var(--space-3); padding: var(--space-3); background: var(--forest-shadow); border-radius: var(--radius-lg); display: none;">
                <pre style="margin: 0; color: var(--forest-mint);"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function testAPI() {
    fetch('/BTT/api/routes/gamification.php?action=status')
        .then(res => res.json())
        .then(data => {
            const resultDiv = document.getElementById('api-result');
            resultDiv.style.display = 'block';
            resultDiv.querySelector('pre').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            alert('API Error: ' + error.message);
        });
}
</script>

<?php require_once dirname(__DIR__) . '/public/includes/footer.php'; ?>
