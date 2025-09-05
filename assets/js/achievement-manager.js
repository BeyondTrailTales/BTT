/**
 * Achievement Manager
 * 
 * Manages achievement checking, display, and tracking for the entire application.
 * Handles queue management, popup display, and confetti celebrations.
 */

class AchievementManager {
    constructor() {
        this.queue = [];
        this.isShowing = false;
        this.shownAchievements = new Set();
        this.checkInterval = 30000; // Check every 30 seconds
        this.userId = window.BTT_USER_ID || null;
        this.apiBase = '/BTT/api/routes/achievements.php';
        this.popupTimeout = null;
        this.progressCache = new Map();
        
        this.init();
    }

    init() {
        this.apiAvailable = true;
        
        if (!this.userId) {
            console.info('AchievementManager: Waiting for user authentication');
            // Still set up the infrastructure
        }

        // Load shown achievements from localStorage
        this.loadShownAchievements();
        
        // Check for unshown achievements on init (only if user ID exists)
        if (this.userId) {
            // Delay initial check to avoid startup congestion
            setTimeout(() => {
                if (this.apiAvailable) {
                    this.checkForAchievements();
                }
            }, 2000);
        }
        
        // Set up periodic checking (only if user ID exists)
        if (this.userId) {
            this.checkTimer = setInterval(() => {
                if (this.apiAvailable) {
                    this.checkForAchievements();
                }
            }, this.checkInterval);
        }
        
        // Listen for custom achievement events
        document.addEventListener('btt:achievement', (e) => {
            this.handleAchievementEvent(e.detail);
        });
        
        // Add CSS if not already present
        this.injectStyles();
    }

    loadShownAchievements() {
        const stored = localStorage.getItem(`btt_shown_achievements_${this.userId}`);
        if (stored) {
            try {
                const ids = JSON.parse(stored);
                ids.forEach(id => this.shownAchievements.add(id));
            } catch (e) {
                console.error('Failed to load shown achievements:', e);
            }
        }
    }

    saveShownAchievements() {
        localStorage.setItem(
            `btt_shown_achievements_${this.userId}`,
            JSON.stringify(Array.from(this.shownAchievements))
        );
    }

    async checkForAchievements() {
        try {
            const response = await fetch(`${this.apiBase}?action=unshown`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Achievement API returned non-JSON response');
                return;
            }
            
            const data = await response.json();
            if (data.achievements && data.achievements.length > 0) {
                this.queueAchievements(data.achievements);
            }
        } catch (error) {
            // Only log errors, don't break the page
            console.warn('Achievement check failed:', error.message);
            
            // If it's a parsing error, the API might be down
            if (error instanceof SyntaxError) {
                console.warn('Achievement API appears to be returning HTML errors. Disabling checks for this session.');
                this.apiAvailable = false;
            }
        }
    }

    async triggerAchievementCheck(context) {
        if (!this.apiAvailable) return null;
        
        try {
            const response = await fetch(`${this.apiBase}?action=check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ context })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Achievement API returned non-JSON response');
                return null;
            }
            
            const data = await response.json();
            
            // Handle earned achievements
            if (data.earned && data.earned.length > 0) {
                this.queueAchievements(data.earned);
            }
            
            // Handle progress updates
            if (data.progress && data.progress.length > 0) {
                this.updateProgressNotifications(data.progress);
            }
            
            return data;
        } catch (error) {
            console.warn('Achievement trigger failed:', error.message);
            
            // Disable API if it's consistently failing
            if (error instanceof SyntaxError) {
                this.apiAvailable = false;
            }
            
            return null;
        }
    }

    queueAchievements(achievements) {
        achievements.forEach(achievement => {
            // Skip if already shown (double-check)
            if (!this.shownAchievements.has(achievement.id)) {
                this.queue.push(achievement);
            }
        });
        
        // Start showing if not already
        if (!this.isShowing && this.queue.length > 0) {
            this.showNextAchievement();
        }
    }

    showNextAchievement() {
        if (this.queue.length === 0) {
            this.isShowing = false;
            return;
        }
        
        const achievement = this.queue.shift();
        this.isShowing = true;
        this.displayAchievement(achievement);
    }

    displayAchievement(achievement) {
        // Temporarily disabled achievement popups for better UX during development
        console.log('🏆 Achievement earned:', achievement.name, achievement.description);
        
        // Add to shown set
        this.shownAchievements.add(achievement.id);
        this.saveShownAchievements();
        
        // Mark as shown in backend
        this.markAsShown([achievement.id]);
        
        // Auto-hide after 5 seconds
        this.popupTimeout = setTimeout(() => {
            this.hideAchievement(popup);
        }, 5000);
        
        // Add click to dismiss
        popup.addEventListener('click', () => {
            clearTimeout(this.popupTimeout);
            this.hideAchievement(popup);
        });
    }

    createAchievementPopup(achievement) {
        const popup = document.createElement('div');
        popup.className = `achievement-popup ${achievement.rarity || 'common'}`;
        popup.innerHTML = `
            <div class="achievement-content">
                <div class="achievement-icon-wrapper">
                    <span class="achievement-icon">${achievement.icon || '🏆'}</span>
                    <div class="achievement-rarity-badge"></div>
                </div>
                <div class="achievement-details">
                    <h3 class="achievement-title">Achievement Unlocked!</h3>
                    <h4 class="achievement-name">${achievement.name}</h4>
                    <p class="achievement-description">${achievement.description}</p>
                    ${achievement.xp_reward > 0 ? `<div class="achievement-reward">+${achievement.xp_reward} XP</div>` : ''}
                </div>
                <button class="achievement-close">&times;</button>
            </div>
            <div class="achievement-progress-bar">
                <div class="achievement-progress-fill"></div>
            </div>
        `;
        
        // Add close button functionality
        const closeBtn = popup.querySelector('.achievement-close');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            clearTimeout(this.popupTimeout);
            this.hideAchievement(popup);
        });
        
        return popup;
    }

    hideAchievement(popup) {
        popup.classList.add('hide');
        
        setTimeout(() => {
            if (popup.parentNode) {
                popup.parentNode.removeChild(popup);
            }
            // Show next achievement if any
            this.showNextAchievement();
        }, 300);
    }

    triggerCelebration(rarity) {
        if (window.confetti) {
            const preset = window.confetti.constructor.presets[rarity] || window.confetti.constructor.presets.common;
            window.confetti.fire(preset);
        }
    }

    async markAsShown(achievementIds) {
        try {
            await fetch(`${this.apiBase}?action=shown`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ achievement_ids: achievementIds })
            });
        } catch (error) {
            console.error('Failed to mark achievements as shown:', error);
        }
    }

    updateProgressNotifications(progressUpdates) {
        progressUpdates.forEach(progress => {
            // Check if this is a significant milestone
            const milestones = [25, 50, 75, 90];
            const previousProgress = this.progressCache.get(progress.code) || 0;
            
            milestones.forEach(milestone => {
                if (previousProgress < milestone && progress.percentage >= milestone) {
                    this.showProgressNotification(progress, milestone);
                }
            });
            
            // Update cache
            this.progressCache.set(progress.code, progress.percentage);
        });
    }

    showProgressNotification(progress, milestone) {
        const notification = document.createElement('div');
        notification.className = 'achievement-progress-notification';
        notification.innerHTML = `
            <div class="progress-content">
                <div class="progress-icon">📈</div>
                <div class="progress-text">
                    <strong>${progress.name}</strong>
                    <span>${milestone}% Complete! (${progress.current}/${progress.target})</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    handleAchievementEvent(detail) {
        if (detail.action) {
            this.triggerAchievementCheck(detail);
        }
    }

    // Helper method for other parts of the app to trigger checks
    static trigger(action, data = {}) {
        const event = new CustomEvent('btt:achievement', {
            detail: { action, ...data }
        });
        document.dispatchEvent(event);
    }

    injectStyles() {
        if (document.getElementById('achievement-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'achievement-styles';
        style.textContent = `
            .achievement-popup {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 400px;
                background: linear-gradient(135deg, #1a1a2e, #16213e);
                border: 2px solid var(--achievement-border-color);
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 
                            0 0 80px var(--achievement-glow-color);
                transform: translateX(500px) scale(0.8);
                opacity: 0;
                transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                z-index: 10000;
                cursor: pointer;
            }
            
            .achievement-popup.show {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
            
            .achievement-popup.hide {
                transform: translateX(500px) scale(0.8);
                opacity: 0;
            }
            
            .achievement-popup.common {
                --achievement-border-color: #6b7280;
                --achievement-glow-color: rgba(107, 114, 128, 0.5);
            }
            
            .achievement-popup.rare {
                --achievement-border-color: #3b82f6;
                --achievement-glow-color: rgba(59, 130, 246, 0.6);
            }
            
            .achievement-popup.epic {
                --achievement-border-color: #8b5cf6;
                --achievement-glow-color: rgba(139, 92, 246, 0.7);
            }
            
            .achievement-popup.legendary {
                --achievement-border-color: #f59e0b;
                --achievement-glow-color: rgba(245, 158, 11, 0.8);
                animation: legendary-pulse 2s ease-in-out infinite;
            }
            
            @keyframes legendary-pulse {
                0%, 100% { transform: translateX(0) scale(1); }
                50% { transform: translateX(0) scale(1.02); }
            }
            
            .achievement-content {
                padding: 1.5rem;
                display: flex;
                gap: 1rem;
                align-items: center;
                position: relative;
            }
            
            .achievement-icon-wrapper {
                position: relative;
                width: 80px;
                height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .achievement-icon {
                font-size: 3rem;
                filter: drop-shadow(0 0 10px var(--achievement-glow-color));
            }
            
            .achievement-rarity-badge {
                position: absolute;
                inset: 0;
                border: 3px solid var(--achievement-border-color);
                border-radius: 50%;
                animation: rotate 10s linear infinite;
            }
            
            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .achievement-details {
                flex: 1;
                color: white;
            }
            
            .achievement-title {
                margin: 0 0 0.25rem;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                opacity: 0.8;
            }
            
            .achievement-name {
                margin: 0 0 0.5rem;
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--achievement-border-color);
            }
            
            .achievement-description {
                margin: 0 0 0.5rem;
                font-size: 0.875rem;
                opacity: 0.9;
            }
            
            .achievement-reward {
                font-size: 1rem;
                font-weight: 600;
                color: #fbbf24;
                text-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
            }
            
            .achievement-close {
                position: absolute;
                top: 0.5rem;
                right: 0.5rem;
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                opacity: 0.5;
                transition: opacity 0.2s;
                padding: 0.25rem;
                line-height: 1;
            }
            
            .achievement-close:hover {
                opacity: 1;
            }
            
            .achievement-progress-bar {
                height: 4px;
                background: rgba(255, 255, 255, 0.1);
                position: relative;
                overflow: hidden;
            }
            
            .achievement-progress-fill {
                height: 100%;
                background: var(--achievement-border-color);
                animation: progress-fill 5s linear;
                transform-origin: left;
            }
            
            @keyframes progress-fill {
                from { transform: scaleX(0); }
                to { transform: scaleX(1); }
            }
            
            .achievement-progress-notification {
                position: fixed;
                bottom: 20px;
                left: 20px;
                background: rgba(26, 26, 46, 0.95);
                border: 1px solid #374151;
                border-radius: 8px;
                padding: 1rem;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 9998;
            }
            
            .achievement-progress-notification.show {
                transform: translateY(0);
                opacity: 1;
            }
            
            .achievement-progress-notification.hide {
                transform: translateY(100px);
                opacity: 0;
            }
            
            .progress-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                color: white;
            }
            
            .progress-icon {
                font-size: 1.5rem;
            }
            
            .progress-text strong {
                display: block;
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }
            
            .progress-text span {
                font-size: 0.75rem;
                opacity: 0.8;
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Always initialize, but it will wait for user ID
        window.achievementManager = new AchievementManager();
    });
} else {
    // Always initialize, but it will wait for user ID
    window.achievementManager = new AchievementManager();
}

// Add global error boundary for achievements
window.addEventListener('error', (event) => {
    if (event.filename && event.filename.includes('achievement')) {
        console.warn('Achievement system error caught:', event.message);
        event.preventDefault();
    }
});