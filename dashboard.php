<?php
/**
 * BeyondTrailTales - Unified Dashboard
 * Main landing page with overview of trips, backpacks, and achievements
 */

// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication for dashboard
require_auth();

// Set page metadata
$pageTitle = 'Dashboard';
$pageDescription = 'Your adventure hub - manage trips, backpacks, and track achievements';
$pageId = 'dashboard';

// Include the unified template header
require_once __DIR__ . '/public/includes/template-header.php';
?>

<div class="dashboard-container">
    <!-- Welcome Section -->
    <section class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">
                Welcome back, <span class="user-name"><?php echo e($_SESSION['user_name'] ?? 'Adventurer'); ?></span>! 🌲
            </h1>
            <p class="welcome-subtitle">Ready for your next adventure?</p>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="quick-actions">
        <div class="action-cards">
            <a href="<?php echo route_url('trips'); ?>" class="action-card">
                <div class="action-icon">🗺️</div>
                <h3>Plan New Trip</h3>
                <p>Start planning your next adventure</p>
            </a>
            
            <a href="<?php echo route_url('backpacks'); ?>" class="action-card">
                <div class="action-icon">🎒</div>
                <h3>Create Backpack</h3>
                <p>Configure gear for your journey</p>
            </a>
            
            <a href="<?php echo route_url('test'); ?>" class="action-card">
                <div class="action-icon">🧪</div>
                <h3>Test Features</h3>
                <p>Try out gamification systems</p>
            </a>
        </div>
    </section>

    <!-- Statistics Overview -->
    <section class="stats-overview">
        <h2 class="section-title">Your Adventure Stats</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏕️</div>
                <div class="stat-value" id="trips-count">0</div>
                <div class="stat-label">Trips Planned</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🎒</div>
                <div class="stat-value" id="backpacks-count">0</div>
                <div class="stat-label">Backpacks Created</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value" id="level-display">1</div>
                <div class="stat-label">Current Level</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-value" id="streak-display">0</div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>
    </section>

    <!-- Recent Activity -->
    <section class="recent-activity">
        <h2 class="section-title">Recent Activity</h2>
        
        <div class="activity-grid">
            <!-- Recent Trips -->
            <div class="activity-section">
                <h3 class="activity-title">Recent Trips</h3>
                <div id="recent-trips" class="activity-list">
                    <div class="loading-state">Loading trips...</div>
                </div>
            </div>
            
            <!-- Recent Backpacks -->
            <div class="activity-section">
                <h3 class="activity-title">Recent Backpacks</h3>
                <div id="recent-backpacks" class="activity-list">
                    <div class="loading-state">Loading backpacks...</div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Dashboard specific styles are handled by forest-theme.css -->

<script>
// Load dashboard data
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

async function loadDashboardData() {
    // Load statistics
    loadStatistics();
    
    // Load recent trips
    loadRecentTrips();
    
    // Load recent backpacks
    loadRecentBackpacks();
}

async function loadStatistics() {
    try {
        // Load trips count
        const tripsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (tripsResponse.ok) {
            const tripsData = await tripsResponse.json();
            const trips = tripsData.data || [];
            document.getElementById('trips-count').textContent = trips.length || 0;
        }
        
        // Load backpacks count
        const backpacksResponse = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (backpacksResponse.ok) {
            const backpacksData = await backpacksResponse.json();
            const backpacks = backpacksData.data || [];
            document.getElementById('backpacks-count').textContent = backpacks.length || 0;
        }
        
        // Load gamification stats
        const gamificationResponse = await fetch('/BTT/api/routes/gamification.php?action=status');
        if (gamificationResponse.ok) {
            const stats = await gamificationResponse.json();
            document.getElementById('level-display').textContent = stats.level || 1;
            document.getElementById('streak-display').textContent = stats.streak_days || 0;
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

async function loadRecentTrips() {
    const container = document.getElementById('recent-trips');
    
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (response.ok) {
            const tripsData = await response.json();
            const trips = tripsData.data || [];
            
            if (trips && trips.length > 0) {
                // Show only the 3 most recent trips
                const recentTrips = trips.slice(0, 3);
                
                let html = '';
                recentTrips.forEach(trip => {
                    const startDate = trip.start_date ? new Date(trip.start_date).toLocaleDateString() : 'Not set';
                    html += `
                        <div class="activity-item">
                            <div>
                                <div class="activity-item-title">${escapeHtml(trip.title)}</div>
                                <div class="activity-item-meta">📅 ${startDate}</div>
                            </div>
                            <a href="<?php echo route_url('trips'); ?>" class="btn btn-sm btn-secondary">View</a>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🗺️</div>
                        <p>No trips yet</p>
                        <a href="<?php echo route_url('trips'); ?>" class="btn btn-primary btn-sm">Plan Your First Trip</a>
                    </div>
                `;
            }
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">Failed to load trips</div>';
    }
}

async function loadRecentBackpacks() {
    const container = document.getElementById('recent-backpacks');
    
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (response.ok) {
            const backpacksData = await response.json();
            const backpacks = backpacksData.data || [];
            
            if (backpacks && backpacks.length > 0) {
                // Show only the 3 most recent backpacks
                const recentBackpacks = backpacks.slice(0, 3);
                
                let html = '';
                recentBackpacks.forEach(backpack => {
                    const weight = backpack.base_weight || 0;
                    html += `
                        <div class="activity-item">
                            <div>
                                <div class="activity-item-title">${escapeHtml(backpack.name)}</div>
                                <div class="activity-item-meta">⚖️ ${weight} kg</div>
                            </div>
                            <a href="<?php echo route_url('backpacks'); ?>" class="btn btn-sm btn-secondary">View</a>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🎒</div>
                        <p>No backpacks yet</p>
                        <a href="<?php echo route_url('backpacks'); ?>" class="btn btn-primary btn-sm">Create Your First Backpack</a>
                    </div>
                `;
            }
        }
    } catch (error) {
        container.innerHTML = '<div class="error-state">Failed to load backpacks</div>';
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

<?php
// Include the unified template footer
require_once __DIR__ . '/public/includes/template-footer.php';
?>
