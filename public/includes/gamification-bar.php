<?php
/**
 * Gamification Bar Component
 * Displays user XP, level, streak, and badges
 */

// Include the Gamification class
require_once dirname(__DIR__, 2) . '/app/classes/Gamification.php';

use BTT\Classes\Gamification;

// Get user ID from session or use default
$userId = $_SESSION['user_id'] ?? 'default';

// Initialize gamification system
$gamification = new Gamification($userId);

// Update streak
$gamification->updateStreak();

// Get user data
$userData = $gamification->getUserData();
?>

<!-- Gamification Bar -->
<div class="gamification-bar" id="gamification-bar">
    <div class="gamification-container">
        <!-- Streak Display -->
        <div class="gamification-item streak-display">
            <span class="streak-flame">🔥</span>
            <span class="streak-count"><?php echo $userData['streak_days']; ?></span>
            <span class="streak-label">Day<?php echo $userData['streak_days'] !== 1 ? 's' : ''; ?></span>
        </div>
        
        <!-- XP Bar -->
        <div class="gamification-item xp-bar-container">
            <div class="xp-info">
                <span class="xp-level">Level <?php echo $userData['level']; ?></span>
                <span class="xp-points"><?php echo $userData['xp']; ?> XP</span>
            </div>
            <div class="xp-track">
                <div class="xp-fill" style="width: <?php echo $userData['xp_progress']; ?>%">
                    <span class="xp-glow"></span>
                </div>
            </div>
            <?php if ($userData['xp_to_next_level'] > 0): ?>
            <div class="xp-next">
                <?php echo $userData['xp_to_next_level']; ?> XP to Level <?php echo $userData['level'] + 1; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>


<style>
/* Gamification Bar Styles */
.gamification-bar {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur-heavy);
    border-bottom: 1px solid var(--glass-border);
    padding: var(--space-2) 0;
    /* Removed sticky positioning */
    position: relative;
    z-index: 10;
    animation: slide-down 0.5s ease-out;
}

.gamification-container {
    max-width: var(--container-7xl);
    margin: 0 auto;
    padding: 0 var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-6);
    flex-wrap: wrap;
}

.gamification-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

/* Streak Display */
.streak-display {
    background: var(--glass-bg);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-full);
    border: 1px solid var(--forest-honey);
    animation: pulse-glow 2s ease-in-out infinite;
}

.streak-flame {
    font-size: 1.5rem;
    animation: flame-flicker 1s ease-in-out infinite;
}

@keyframes flame-flicker {
    0%, 100% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.1) rotate(-5deg); }
    75% { transform: scale(0.95) rotate(5deg); }
}

.streak-count {
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--forest-honey);
}

.streak-label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

/* XP Bar */
.xp-bar-container {
    flex: 1;
    min-width: 200px;
    max-width: 400px;
}

.xp-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--space-1);
}

.xp-level {
    font-weight: var(--font-bold);
    color: var(--forest-leaf);
}

.xp-points {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.xp-track {
    height: 20px;
    background: var(--forest-shadow);
    border-radius: var(--radius-full);
    overflow: hidden;
    position: relative;
}

.xp-fill {
    height: 100%;
    background: var(--gradient-success);
    border-radius: var(--radius-full);
    transition: width 0.5s ease-out;
    position: relative;
    overflow: hidden;
}

.xp-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: xp-shimmer 2s linear infinite;
}

@keyframes xp-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.xp-next {
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin-top: var(--space-1);
}

/* Badge Display */
.badge-display {
    background: var(--glass-bg);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-full);
    border: 1px solid var(--glass-border);
}

.badge-icon {
    font-size: 1.5rem;
}

.badge-count {
    font-weight: var(--font-bold);
    color: var(--forest-honey);
}

.badge-view-btn {
    padding: var(--space-1) var(--space-3);
    background: var(--forest-fern);
    color: var(--forest-deep);
    border: none;
    border-radius: var(--radius-full);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: var(--transition-all);
}

.badge-view-btn:hover {
    background: var(--forest-leaf);
    transform: scale(1.05);
}

/* Recent Achievement */
.recent-achievement {
    position: absolute;
    right: var(--space-4);
    top: 100%;
    margin-top: var(--space-2);
    background: var(--gradient-success);
    color: var(--forest-deep);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-glow-md);
    animation: achievement-pop 0.5s ease-out;
}

@keyframes achievement-pop {
    0% { transform: scale(0) translateY(-20px); opacity: 0; }
    50% { transform: scale(1.1) translateY(0); }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

.achievement-icon {
    font-size: 1.5rem;
    margin-right: var(--space-2);
}

.achievement-text {
    font-weight: var(--font-bold);
}

/* Badge Modal */
.gamification-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: var(--z-modal);
    animation: fade-in 0.3s ease-out;
}

.gamification-modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-modal-content {
    background: var(--bg-surface);
    backdrop-filter: var(--glass-blur-heavy);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-2xl);
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    animation: modal-slide-up 0.3s ease-out;
}

@keyframes modal-slide-up {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.badge-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: var(--space-4);
    padding: var(--space-4);
}

.badge-item {
    background: var(--glass-bg);
    border: 2px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
    text-align: center;
    transition: var(--transition-all);
    position: relative;
    overflow: hidden;
}

.badge-item.earned {
    border-color: var(--forest-leaf);
    background: linear-gradient(135deg, rgba(92, 184, 92, 0.1), rgba(139, 195, 74, 0.1));
}

.badge-item.locked {
    opacity: 0.6;
    filter: grayscale(0.5);
}

.badge-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.badge-icon-large {
    font-size: 3rem;
    margin-bottom: var(--space-2);
}

.badge-name {
    font-weight: var(--font-bold);
    color: var(--forest-mint);
    margin-bottom: var(--space-1);
}

.badge-description {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-2);
}

.badge-status {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-full);
    background: var(--glass-bg);
}

/* Animations */
@keyframes slide-down {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 10px rgba(255, 193, 7, 0.3); }
    50% { box-shadow: 0 0 20px rgba(255, 193, 7, 0.5); }
}

/* Responsive */
@media (max-width: 768px) {
    .gamification-container {
        gap: var(--space-3);
    }
    
    .xp-bar-container {
        order: -1;
        flex-basis: 100%;
    }
    
    .recent-achievement {
        position: static;
        margin-top: var(--space-2);
        width: 100%;
    }
    
    .badge-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>

<script>
// Gamification JavaScript
// Listen for XP updates
window.addEventListener('xp-earned', function(e) {
    const { xp_gained, total_xp, level_up, new_level } = e.detail;
    
    // Show XP notification
    showXPNotification(xp_gained);
    
    // Update XP bar
    updateXPBar();
    
    // Handle level up
    if (level_up) {
        showLevelUpModal(new_level);
    }
});

function showXPNotification(xp) {
    const notification = document.createElement('div');
    notification.className = 'xp-notification';
    notification.innerHTML = `+${xp} XP`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function updateXPBar() {
    // Fetch updated gamification status
    fetch('/BTT/api/routes/gamification.php?action=status')
        .then(res => res.json())
        .then(data => {
            // Update XP display
            document.querySelector('.xp-points').textContent = data.xp + ' XP';
            document.querySelector('.xp-level').textContent = 'Level ' + data.level;
            document.querySelector('.xp-fill').style.width = data.xp_progress + '%';
            
            if (data.xp_to_next_level > 0) {
                document.querySelector('.xp-next').textContent = 
                    data.xp_to_next_level + ' XP to Level ' + (data.level + 1);
            }
            
        });
}

function showLevelUpModal(level) {
    // Create level up modal
    const modal = document.createElement('div');
    modal.className = 'level-up-modal';
    modal.innerHTML = `
        <div class="level-up-content">
            <div class="level-up-icon">🎉</div>
            <h2 class="level-up-title">Level Up!</h2>
            <p class="level-up-text">You've reached Level ${level}!</p>
            <button class="btn btn-primary" onclick="this.closest('.level-up-modal').remove()">Awesome!</button>
        </div>
    `;
    document.body.appendChild(modal);
}

// Auto-hide recent achievement after 5 seconds
const recentAchievement = document.getElementById('recent-achievement');
if (recentAchievement) {
    setTimeout(() => {
        recentAchievement.style.animation = 'fade-out 0.5s ease-out';
        setTimeout(() => {
            recentAchievement.remove();
        }, 500);
    }, 5000);
}
</script>
