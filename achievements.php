<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

$pageTitle = 'Achievements';
$currentPage = 'achievements';

include 'includes/template-header.php';
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">🏆 Achievements</h1>
            <p class="page-subtitle">Track your progress and earn rewards</p>
        </div>
    </div>

    <!-- Achievement Stats -->
    <div class="achievement-stats-bar">
        <div class="stat-item">
            <span class="stat-value" id="total-earned">0</span>
            <span class="stat-label">Earned</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="total-available">0</span>
            <span class="stat-label">Available</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="completion-rate">0%</span>
            <span class="stat-label">Completion</span>
        </div>
        <div class="stat-item">
            <span class="stat-value" id="total-xp">0</span>
            <span class="stat-label">Total XP</span>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="achievement-tabs">
        <button class="achievement-tab active" data-filter="all">All</button>
        <button class="achievement-tab" data-filter="earned">Earned</button>
        <button class="achievement-tab" data-filter="trips">Trips</button>
        <button class="achievement-tab" data-filter="backpacks">Backpacks</button>
        <button class="achievement-tab" data-filter="gear">Gear</button>
        <button class="achievement-tab" data-filter="milestones">Milestones</button>
    </div>

    <!-- Achievement Grid -->
    <div class="achievement-grid" id="achievement-grid">
        <div class="loading-spinner">Loading achievements...</div>
    </div>
</div>

<style>
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.achievement-stats-bar {
    display: flex;
    gap: 2rem;
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-item {
    flex: 1;
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.achievement-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.achievement-tab {
    padding: 0.75rem 1.5rem;
    background: var(--card-bg);
    border: 2px solid transparent;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.achievement-tab:hover {
    border-color: var(--primary-color);
}

.achievement-tab.active {
    background: var(--primary-color);
    color: white;
}

.achievement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.achievement-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    transition: all 0.3s;
    cursor: pointer;
    border: 2px solid transparent;
}

.achievement-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.achievement-card.earned {
    border-color: var(--success-color);
    background: linear-gradient(to bottom right, var(--card-bg), rgba(76, 175, 80, 0.1));
}

.achievement-card.locked {
    opacity: 0.6;
}

.achievement-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.achievement-icon {
    font-size: 3rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 12px;
}

.achievement-card.earned .achievement-icon {
    background: rgba(76, 175, 80, 0.1);
}

.achievement-info {
    flex: 1;
}

.achievement-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
}

.achievement-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0;
}

.achievement-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.achievement-xp {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-color);
}

.achievement-date {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.achievement-rarity {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.achievement-rarity.common {
    background: #e0e0e0;
    color: #616161;
}

.achievement-rarity.rare {
    background: #bbdefb;
    color: #1565c0;
}

.achievement-rarity.epic {
    background: #ce93d8;
    color: #6a1b9a;
}

.achievement-rarity.legendary {
    background: #ffcc80;
    color: #e65100;
}

.loading-spinner {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.no-achievements {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('achievement-grid');
    const tabs = document.querySelectorAll('.achievement-tab');
    let allAchievements = [];
    let currentFilter = 'all';

    // Load achievements
    async function loadAchievements() {
        try {
            const response = await fetch('/BTT/api/routes/achievements.php?action=gallery');
            if (!response.ok) throw new Error('Failed to load achievements');
            
            const data = await response.json();
            
            // Update stats
            document.getElementById('total-earned').textContent = data.stats.total_earned;
            document.getElementById('total-available').textContent = data.stats.total_available;
            document.getElementById('completion-rate').textContent = data.stats.completion_percentage + '%';
            
            // Calculate total XP from earned achievements
            const totalXP = data.earned.reduce((sum, ach) => sum + (ach.xp_reward || 0), 0);
            document.getElementById('total-xp').textContent = totalXP.toLocaleString();
            
            // Store all achievements
            allAchievements = [...data.earned, ...data.available];
            
            // Display achievements
            displayAchievements();
            
        } catch (error) {
            console.error('Error loading achievements:', error);
            grid.innerHTML = '<div class="loading-spinner">Failed to load achievements</div>';
        }
    }

    // Display achievements based on filter
    function displayAchievements() {
        let filteredAchievements = allAchievements;
        
        // Apply filter
        switch (currentFilter) {
            case 'earned':
                filteredAchievements = allAchievements.filter(a => a.earned);
                break;
            case 'trips':
            case 'backpacks':
            case 'gear':
            case 'milestones':
                filteredAchievements = allAchievements.filter(a => a.category === currentFilter);
                break;
        }
        
        // Sort earned achievements first
        filteredAchievements.sort((a, b) => {
            if (a.earned && !b.earned) return -1;
            if (!a.earned && b.earned) return 1;
            return a.display_order - b.display_order;
        });
        
        // Generate HTML
        if (filteredAchievements.length === 0) {
            grid.innerHTML = '<div class="no-achievements">No achievements found</div>';
            return;
        }
        
        grid.innerHTML = filteredAchievements.map(achievement => `
            <div class="achievement-card ${achievement.earned ? 'earned' : 'locked'} ${achievement.rarity || 'common'}" 
                 data-category="${achievement.category}">
                <div class="achievement-rarity ${achievement.rarity || 'common'}">${achievement.rarity || 'common'}</div>
                <div class="achievement-header">
                    <div class="achievement-icon">${achievement.icon || '🏆'}</div>
                    <div class="achievement-info">
                        <h3 class="achievement-name">${achievement.name}</h3>
                        <p class="achievement-description">${achievement.description}</p>
                    </div>
                </div>
                <div class="achievement-footer">
                    <span class="achievement-xp">${achievement.xp_reward} XP</span>
                    ${achievement.earned ? 
                        `<span class="achievement-date">Earned ${formatDate(achievement.earned_at)}</span>` : 
                        '<span class="achievement-date">Not earned</span>'
                    }
                </div>
            </div>
        `).join('');
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        
        return date.toLocaleDateString();
    }

    // Tab click handlers
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            displayAchievements();
        });
    });

    // Load achievements on page load
    loadAchievements();
});
</script>

<?php include 'includes/template-footer.php'; ?>