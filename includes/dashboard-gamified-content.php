<!-- Skip Link for Accessibility -->
<a href="#main-content" class="skip-link">Skip to main content</a>

<!-- Dashboard Content (Navigation already included in template-header.php) -->

<!-- Inline Responsive Grid Styles -->
<style>
/* Responsive Grid Override for Dashboard Cards */
@media (min-width: 1200px) {
  .dashboard-grid {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 1.5rem !important;
  }
  .dashboard-grid > * {
    grid-area: auto !important;
    width: 100% !important;
    max-width: none !important;
  }
}

@media (min-width: 768px) and (max-width: 1199px) {
  .dashboard-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 1.5rem !important;
  }
  .dashboard-grid > * {
    grid-area: auto !important;
    width: 100% !important;
    max-width: none !important;
  }
}

@media (max-width: 767px) {
  .dashboard-grid {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 1.5rem !important;
  }
  .dashboard-grid > * {
    grid-area: auto !important;
    width: 100% !important;
    max-width: none !important;
  }
  
  .tiles-grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

/* Ensure cards stretch properly */
.card--xp {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.card__content {
  flex: 1;
}

.card__actions {
  margin-top: auto;
}
</style>

<!-- Compact Hero Section -->
<header class="hero-compact" role="banner">
    <div class="hero-content">
        <h1 class="hero-greeting">Welcome to your Adventure Dashboard</h1>
        <p class="hero-subtitle">Track progress, plan trips, and level up your outdoor experience</p>
    </div>
</header>

<!-- Main Content -->
<main id="main-content" class="dashboard-container" role="main">
    
    <!-- Quick Actions -->
    <section class="action-tiles" aria-labelledby="quick-actions-title">
        <h2 id="quick-actions-title" class="visually-hidden">Quick Actions</h2>
        <div class="tiles-grid">
            <a href="<?php echo route_url('trips'); ?>?action=new" class="action-tile">
                <span class="tile-icon">🗺️</span>
                <span class="tile-label">Plan Trip</span>
            </a>
            <a href="<?php echo route_url('backpacks'); ?>?action=quick-pack" class="action-tile">
                <span class="tile-icon">⚡</span>
                <span class="tile-label">Quick Pack</span>
            </a>
            <button class="action-tile" onclick="copyLastTrip()">
                <span class="tile-icon">📋</span>
                <span class="tile-label">Copy Trip</span>
            </button>
            <a href="<?php echo route_url('backpacks'); ?>" class="action-tile">
                <span class="tile-icon">🎒</span>
                <span class="tile-label">Build Pack</span>
            </a>
            <a href="<?php echo route_url('gear'); ?>" class="action-tile">
                <span class="tile-icon">⛺</span>
                <span class="tile-label">Add Gear</span>
            </a>
        </div>
    </section>
    
    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        
        <!-- Trips & Progress Section -->
        <section class="card card--xp" aria-labelledby="trips-title">
            <div class="card__content">
                <h2 id="trips-title" class="card__title text-gradient-forest">🏔️ Trips</h2>
                <p class="card__meta">Your adventure progress</p>
            </div>
            
            <div class="card__content">
                <!-- Trip Progress Ring -->
                <div class="stat-item">
                    <?php
                    $label = 'Trip Completion';
                    $value = 0; // Will be updated via JavaScript
                    $size = 70;
                    include __DIR__ . '/components/progress-ring.php';
                    ?>
                </div>
                
                <!-- Trip Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-icon">📅</span>
                        <span class="stat-value" id="upcoming-trips">0</span>
                        <span class="stat-label">Upcoming</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">✅</span>
                        <span class="stat-value" id="completed-trips">0</span>
                        <span class="stat-label">Completed</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">📏</span>
                        <span class="stat-value" id="total-miles">0</span>
                        <span class="stat-label">Miles</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">⛰️</span>
                        <span class="stat-value" id="elevation-gain">0</span>
                        <span class="stat-label">Ft Gained</span>
                    </div>
                </div>
                
                <!-- Next Trip Preview -->
                <div id="next-trip-preview" class="next-trip-card">
                    <span style="opacity: 0.6">Loading trips...</span>
                </div>
            </div>
            
            <footer class="card__actions">
                <a class="card__action" href="<?php echo route_url('trips'); ?>">View all trips</a>
                <a class="card__action card__action--primary" href="<?php echo route_url('trips'); ?>?action=new">Plan new trip</a>
            </footer>
        </section>
        
        <!-- Backpacks Section -->
        <section class="card card--xp" aria-labelledby="backpacks-title">
            <div class="card__content">
                <h2 id="backpacks-title" class="card__title text-gradient-forest">🎒 Backpacks</h2>
                <p class="card__meta">Gear management</p>
            </div>
            
            <div class="card__content">
                <!-- Pack Progress -->
                <h3 class="stat-label">Packing Efficiency</h3>
                <div class="meter meter--large" aria-label="Packing efficiency" role="meter" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <span id="pack-efficiency" style="--val: 0%"></span>
                </div>
                <p class="stat-detail"><span id="total-packs">0</span> packs • <span id="gear-items">0</span> total items</p>
                
                <!-- Pack Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-icon">🎒</span>
                        <span class="stat-value" id="packs-count">0</span>
                        <span class="stat-label">Packs</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">⚖️</span>
                        <span class="stat-value" id="avg-weight">0</span>
                        <span class="stat-label">Avg Weight</span>
                    </div>
                </div>
            </div>
            
            <footer class="card__actions">
                <a class="card__action" href="<?php echo route_url('backpacks'); ?>?action=check">Check Pack</a>
                <a class="card__action card__action--primary" href="<?php echo route_url('backpacks'); ?>?action=new">New Pack</a>
            </footer>
        </section>
        
        <!-- Gear Collection Section -->
        <section class="card card--xp" aria-labelledby="gear-title">
            <div class="card__content">
                <h2 id="gear-title" class="card__title text-gradient-forest">⛺ My Gear</h2>
                <p class="card__meta">Equipment inventory</p>
            </div>
            
            <div class="card__content">
                <!-- Gear Collection Progress -->
                <div class="stat-item">
                    <?php
                    $label = 'Gear Collection';
                    $value = 0; // Will be updated via JavaScript
                    $size = 70;
                    $progressId = 'gear-progress';
                    include __DIR__ . '/components/progress-ring.php';
                    ?>
                </div>
                
                <!-- Gear Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-icon">📦</span>
                        <span class="stat-value" id="gear-total">0</span>
                        <span class="stat-label">Total Items</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">⭐</span>
                        <span class="stat-value" id="gear-favorites">0</span>
                        <span class="stat-label">Favorites</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-icon">🆕</span>
                        <span class="stat-value" id="gear-recent">0</span>
                        <span class="stat-label">New Items</span>
                    </div>
                </div>
            </div>
            
            <footer class="card__actions">
                <a class="card__action card__action--primary" href="<?php echo route_url('gear'); ?>">Manage Gear</a>
            </footer>
        </section>
        
    </div>
    
    <!-- Second Row Grid -->
    <div class="dashboard-grid dashboard-grid--secondary">
        
        <!-- Achievements Section -->
        <section class="card card--xp achievements-card" aria-labelledby="achievements-title">
            <div class="card__content">
                <h2 id="achievements-title" class="card__title text-gradient-forest">🏆 Achievements</h2>
                <div class="xp-display">
                    <span id="total-xp">0</span> XP
                </div>
            </div>
            
            <div class="card__content">
                <!-- Level Progress -->
                <div class="level-progress">
                    <div class="level-info">
                        <span class="level-label">Level <span id="current-level">1</span></span>
                        <span class="level-xp"><span id="xp-current">0</span> / <span id="xp-next">100</span> XP</span>
                    </div>
                    <div class="meter meter--large" aria-label="Level progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <span id="level-progress" style="--val: 0%"></span>
                    </div>
                </div>
                
                <!-- Achievement Badges -->
                <div class="achievements-grid" id="achievements-list">
                    <!-- Badges will be loaded dynamically -->
                </div>
            </div>
            
            <footer class="card__actions">
                <a class="card__action" href="#">View all achievements</a>
            </footer>
        </section>
        
        <!-- Recent Activity Section -->
        <section class="card card--xp" aria-labelledby="activity-title">
            <div class="card__content">
                <h2 id="activity-title" class="card__title text-gradient-forest">📋 Recent Activity</h2>
                <p class="card__meta">Your latest adventures</p>
            </div>
            
            <div class="card__content">
                <div class="activity-tabs">
                    <button class="tab-button active" onclick="showActivityTab('trips')">Trips</button>
                    <button class="tab-button" onclick="showActivityTab('backpacks')">Backpacks</button>
                </div>
                
                <div id="recent-trips" class="activity-list">
                    <!-- Dynamic content loaded via JS -->
                </div>
                
                <div id="recent-backpacks" class="activity-list" style="display: none;">
                    <!-- Dynamic content loaded via JS -->
                </div>
            </div>
        </section>
        
        <!-- Skill Tree Section -->
        <section class="card card--xp" aria-labelledby="skills-title">
            <div class="card__content">
                <h2 id="skills-title" class="card__title text-gradient-forest">🗺️ Trail Mastery</h2>
                <p class="card__meta">Your skill progression</p>
            </div>
            
            <div class="card__content">
                <?php
                // Sample skill tree data - would come from database
                $skills = [
                    ['title' => 'Beginner Hiker', 'category' => 'Novice', 'state' => 'is-complete', 'progress' => 100],
                    ['title' => 'Trail Walker', 'category' => 'Intermediate', 'state' => 'is-complete', 'progress' => 100],
                    ['title' => 'Backpacker', 'category' => 'Advanced', 'state' => 'is-active', 'progress' => 45],
                    ['title' => 'Trail Master', 'category' => 'Expert', 'state' => 'is-locked', 'progress' => 0],
                ];
                include __DIR__ . '/components/skill-tree.php';
                ?>
            </div>
        </section>
        
    </div>
    
</main>

<!-- Load Global Forest Theme & Gamified Styles -->
<link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/global-forest-theme.css">
<link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-gamified-tokens.css">
<link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-gamified.css">

<style>
/* Compact Hero for Dashboard */
.hero-compact {
    background: linear-gradient(135deg, rgba(15, 56, 35, 0.8), rgba(26, 77, 53, 0.6));
    backdrop-filter: blur(10px);
    padding: 1.5rem 0;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid rgba(74, 222, 128, 0.2);
    text-align: center;
}

.hero-greeting {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    background: linear-gradient(135deg, #58cc02, #86efac);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0.25rem 0 0;
}

/* Dashboard-specific styles with proper spacing */
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--forest-canopy);
    color: white;
    padding: 8px;
    text-decoration: none;
    z-index: 100;
    border-radius: 0 0 4px 0;
}

.skip-link:focus {
    top: 0;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.dashboard-grid--secondary {
    margin-top: 2rem;
}

.xp-display {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(74, 222, 128, 0.1);
    border: 1px solid rgba(74, 222, 128, 0.3);
    border-radius: 9999px;
    color: var(--forest-mint);
    font-weight: 700;
    font-size: 1.125rem;
}

.next-trip-card {
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 0.5rem;
    margin-top: 0.5rem;
    font-size: 0.813rem;
}

.next-trip-card strong {
    color: var(--forest-mint);
}

.activity-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.tab-button {
    flex: 1;
    padding: 0.5rem 1rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 250ms ease;
}

.tab-button.active {
    background: rgba(74, 222, 128, 0.1);
    border-color: var(--forest-mint);
    color: var(--forest-mint);
}

.tab-button:hover:not(.active) {
    background: rgba(255, 255, 255, 0.05);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 0.5rem;
    transition: background 250ms ease;
}

.activity-item:hover {
    background: rgba(255, 255, 255, 0.06);
}

.activity-icon {
    font-size: 1.25rem;
}

.activity-details {
    flex: 1;
}

.activity-details strong {
    display: block;
    color: var(--text-primary);
    font-weight: 600;
}

.activity-meta {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.stat-item {
    margin-bottom: 0.75rem;
    text-align: center;
}

.stat-item .stat-label {
    display: block;
    margin-bottom: 1rem;
    font-size: 1rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.stat-detail {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.level-progress {
    margin-bottom: 2rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 0.75rem;
}

.level-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.level-label {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--forest-mint);
}

.level-xp {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.achievements-card .card__content:first-child {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Allow cards to size naturally */
.card {
    min-height: 250px;
}

.card__content {
    overflow: visible;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .tiles-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Enhanced dashboard data loading with gamified features
document.addEventListener('DOMContentLoaded', function() {
    loadGamifiedDashboardData();
    initializeComponents();
});

async function loadGamifiedDashboardData() {
    // Load all data in parallel
    Promise.all([
        loadTripsOverview(),
        loadPacksOverview(),
        loadGearStats(),
        loadAchievements(),
        loadRecentActivity()
    ]).catch(error => {
        console.error('Error loading dashboard data:', error);
    });
}

async function loadTripsOverview() {
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (response.ok) {
            const tripsData = await response.json();
            const trips = tripsData.data || tripsData || [];
            
            // Calculate stats
            const now = new Date();
            const upcomingTrips = trips.filter(trip => {
                if (trip.start_date) {
                    const startDate = new Date(trip.start_date);
                    return startDate >= now && !trip.completed;
                }
                return false;
            });
            const completedTrips = trips.filter(trip => trip.completed);
            const totalMiles = trips.reduce((sum, trip) => sum + (parseFloat(trip.distance) || 0), 0);
            const totalElevation = trips.reduce((sum, trip) => sum + (parseFloat(trip.elevation_gain) || 0), 0);
            
            // Update stats
            document.getElementById('upcoming-trips').textContent = upcomingTrips.length;
            document.getElementById('completed-trips').textContent = completedTrips.length;
            document.getElementById('total-miles').textContent = Math.round(totalMiles);
            document.getElementById('elevation-gain').textContent = totalElevation > 1000 ? 
                (totalElevation / 1000).toFixed(1) + 'k' : totalElevation;
            
            // Update progress ring
            const tripProgress = trips.length > 0 ? 
                Math.round((completedTrips.length / trips.length) * 100) : 0;
            updateProgressRing('progress-ring', tripProgress);
            
            // Update next trip preview
            const previewContainer = document.getElementById('next-trip-preview');
            if (upcomingTrips.length > 0) {
                const nextTrip = upcomingTrips.sort((a, b) => new Date(a.start_date) - new Date(b.start_date))[0];
                const daysUntil = Math.ceil((new Date(nextTrip.start_date) - now) / (1000 * 60 * 60 * 24));
                
                previewContainer.innerHTML = `
                    <strong>Next: ${escapeHtml(nextTrip.title)}</strong><br>
                    <span style="opacity: 0.7">📅 ${daysUntil} days away • ${nextTrip.distance || 0} miles</span>
                `;
            } else {
                previewContainer.innerHTML = `<span style="opacity: 0.6">No upcoming trips. <a href="${'<?php echo route_url("trips"); ?>'}?action=new">Plan one now!</a></span>`;
            }
            
            // Calculate XP based on trips
            const xp = completedTrips.length * 100 + Math.round(totalMiles * 2);
            updateXPDisplay(xp);
            
            // Update streak (simplified - would need actual date logic)
            if (completedTrips.length > 0) {
                updateStreak(Math.min(7, completedTrips.length));
            }
        }
    } catch (error) {
        console.error('Error loading trips overview:', error);
    }
}

async function loadPacksOverview() {
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (response.ok) {
            const backpacksData = await response.json();
            const backpacks = backpacksData.data || backpacksData || [];
            
            // Calculate stats
            const totalPacks = backpacks.length;
            const gearCount = backpacks.reduce((sum, pack) => sum + (pack.total_items || 0), 0);
            const avgWeight = totalPacks > 0 ? 
                backpacks.reduce((sum, pack) => sum + (pack.base_weight_g || 0), 0) / totalPacks / 1000 : 0;
            
            // Update stats
            document.getElementById('total-packs').textContent = totalPacks;
            document.getElementById('packs-count').textContent = totalPacks;
            document.getElementById('gear-items').textContent = gearCount;
            document.getElementById('avg-weight').textContent = avgWeight.toFixed(1) + ' kg';
            
            // Update efficiency meter
            const efficiency = Math.min(100, (totalPacks * 10) + (gearCount * 2));
            const efficiencyBar = document.getElementById('pack-efficiency');
            if (efficiencyBar) {
                efficiencyBar.style.setProperty('--val', efficiency + '%');
            }
        }
    } catch (error) {
        console.error('Error loading packs overview:', error);
    }
}

async function loadGearStats() {
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=gear');
        if (response.ok) {
            const gearData = await response.json();
            const gear = gearData.data || gearData || [];
            
            // Update stats
            document.getElementById('gear-total').textContent = gear.length;
            document.getElementById('gear-favorites').textContent = 
                gear.filter(g => g.is_favorite).length;
            
            // Recent items (last 7 days)
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            const recentGear = gear.filter(g => new Date(g.created_at) > weekAgo);
            document.getElementById('gear-recent').textContent = recentGear.length;
            
            // Update gear progress ring
            const gearProgress = Math.min(100, (gear.length / 50) * 100); // 50 items = 100%
            updateProgressRing('gear-progress', gearProgress);
        }
    } catch (error) {
        console.error('Error loading gear stats:', error);
    }
}

async function loadAchievements() {
    // Sample achievements (would come from API)
    const achievements = [
        {title: 'First Steps', icon: '🥾', state: 'earned', description: 'Complete your first trip'},
        {title: 'Peak Seeker', icon: '🏔️', state: 'earned', description: 'Climb 5,000ft elevation'},
        {title: 'Trail Warrior', icon: '⚔️', state: 'available', description: 'Complete 20 trips'},
        {title: 'Ultralight', icon: '🪶', state: 'locked', description: 'Pack under 10 lbs'},
        {title: 'Weather Master', icon: '🌦️', state: 'locked', description: 'Trek in all seasons'},
        {title: 'Trail Legend', icon: '👑', state: 'locked', description: 'Reach level 10'}
    ];
    
    const container = document.getElementById('achievements-list');
    if (container) {
        container.innerHTML = achievements.map(a => `
            <div class="badge badge--${a.state}" title="${escapeHtml(a.description)}">
                <span class="badge-icon">${a.icon}</span>
                <span class="badge-label">${escapeHtml(a.title)}</span>
            </div>
        `).join('');
    }
}

async function loadRecentActivity() {
    try {
        // Load recent trips
        const tripsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (tripsResponse.ok) {
            const tripsData = await tripsResponse.json();
            const trips = (tripsData.data || tripsData || []).slice(0, 3);
            
            const tripsContainer = document.getElementById('recent-trips');
            if (trips.length > 0) {
                tripsContainer.innerHTML = trips.map(trip => `
                    <div class="activity-item">
                        <span class="activity-icon">${trip.completed ? '✅' : '📅'}</span>
                        <div class="activity-details">
                            <strong>${escapeHtml(trip.title)}</strong>
                            <span class="activity-meta">${trip.distance || 0} miles • ${trip.duration || 1} days</span>
                        </div>
                    </div>
                `).join('');
            } else {
                tripsContainer.innerHTML = '<p style="opacity: 0.6">No recent trips</p>';
            }
        }
        
        // Load recent backpacks
        const packsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (packsResponse.ok) {
            const packsData = await packsResponse.json();
            const packs = (packsData.data || packsData || []).slice(0, 3);
            
            const packsContainer = document.getElementById('recent-backpacks');
            if (packs.length > 0) {
                packsContainer.innerHTML = packs.map(pack => `
                    <div class="activity-item">
                        <span class="activity-icon">🎒</span>
                        <div class="activity-details">
                            <strong>${escapeHtml(pack.name)}</strong>
                            <span class="activity-meta">${pack.total_items || 0} items • ${(pack.base_weight_g / 1000).toFixed(1)} kg</span>
                        </div>
                    </div>
                `).join('');
            } else {
                packsContainer.innerHTML = '<p style="opacity: 0.6">No backpacks created</p>';
            }
        }
    } catch (error) {
        console.error('Error loading recent activity:', error);
    }
}

function updateProgressRing(elementId, value) {
    const ring = document.querySelector(`#${elementId} .ring-progress`);
    if (ring) {
        const r = parseFloat(ring.getAttribute('r'));
        const c = 2 * Math.PI * r;
        ring.style.strokeDasharray = `${c} ${c}`;
        ring.dataset.progress = value;
        
        requestAnimationFrame(() => {
            const offset = c * (1 - value / 100);
            ring.style.strokeDashoffset = offset;
        });
        
        // Update value display
        const valueDisplay = document.querySelector(`#${elementId} .ring-value`);
        if (valueDisplay) {
            valueDisplay.textContent = `${value}%`;
        }
    }
}

function updateXPDisplay(xp) {
    document.getElementById('user-xp').textContent = xp.toLocaleString();
    document.getElementById('total-xp').textContent = xp.toLocaleString();
    
    // Calculate level (simple formula)
    const level = Math.floor(xp / 500) + 1;
    const currentLevelXP = (level - 1) * 500;
    const nextLevelXP = level * 500;
    const progressXP = xp - currentLevelXP;
    const progressPercent = (progressXP / 500) * 100;
    
    document.getElementById('user-level').textContent = level;
    document.getElementById('current-level').textContent = level;
    document.getElementById('xp-current').textContent = progressXP;
    document.getElementById('xp-next').textContent = 500;
    
    const levelProgressBar = document.getElementById('level-progress');
    if (levelProgressBar) {
        levelProgressBar.style.setProperty('--val', progressPercent + '%');
    }
}

function updateStreak(days) {
    const streakElements = document.querySelectorAll('.streak-count');
    streakElements.forEach(el => {
        el.textContent = days;
    });
}

function initializeComponents() {
    // Initialize progress rings
    initProgressRings();
    
    // Initialize meters
    initMeters();
}

function initProgressRings() {
    document.querySelectorAll('.ring-progress').forEach(circle => {
        const r = parseFloat(circle.getAttribute('r'));
        const c = 2 * Math.PI * r;
        circle.style.strokeDasharray = `${c} ${c}`;
        circle.style.strokeDashoffset = c;
    });
}

function initMeters() {
    document.querySelectorAll('.meter span').forEach(bar => {
        // Trigger animation on load
        setTimeout(() => {
            bar.style.transition = 'width 800ms var(--ease-decelerate)';
        }, 100);
    });
}

function showActivityTab(tab) {
    // Hide all tabs
    document.getElementById('recent-trips').style.display = 'none';
    document.getElementById('recent-backpacks').style.display = 'none';
    
    // Remove active class from buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    if (tab === 'trips') {
        document.getElementById('recent-trips').style.display = 'flex';
        document.querySelectorAll('.tab-button')[0].classList.add('active');
    } else {
        document.getElementById('recent-backpacks').style.display = 'flex';
        document.querySelectorAll('.tab-button')[1].classList.add('active');
    }
}

function copyLastTrip() {
    alert('Copy last trip functionality coming soon!');
}

// Escape HTML for security
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}
</script>
