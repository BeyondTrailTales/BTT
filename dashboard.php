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
$pageTitle = 'Trailhead';
$pageDescription = 'Your adventure command center - plan trips, manage gear, and track your progress';
$pageId = 'dashboard';

// Include the unified template header
require_once __DIR__ . '/public/includes/template-header.php';
?>

<div class="dashboard-container">
<!-- Welcome Section -->
    <section class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">
                Welcome to your Trailhead, <span class="user-name"><?php echo e($_SESSION['user_name'] ?? 'Explorer'); ?></span>! 🏔️
            </h1>
            <p class="welcome-subtitle">Let's get you ready for the trails!</p>
        </div>
    </section>

<!-- Quick Start Section -->
    <section class="quick-start-section">
        <h2 class="section-title">Quick Start</h2>
        <div class="quick-start-grid">
            <a href="<?php echo route_url('trips'); ?>?action=new" class="quick-action-card">
                <div class="quick-action-icon">🗺️</div>
                <h3 class="quick-action-title">Start a Trip</h3>
                <p class="quick-action-desc">Plan your next adventure</p>
            </a>
            
            <a href="<?php echo route_url('backpacks'); ?>?action=quick-pack" class="quick-action-card">
                <div class="quick-action-icon">⚡</div>
                <h3 class="quick-action-title">Quick Pack</h3>
                <p class="quick-action-desc">Smart pack for your trip type</p>
            </a>
            
            <button class="quick-action-card" onclick="copyLastTrip()">
                <div class="quick-action-icon">📋</div>
                <h3 class="quick-action-title">Copy Last Trip</h3>
                <p class="quick-action-desc">Start from your previous adventure</p>
            </button>
            
            <a href="<?php echo route_url('backpacks'); ?>" class="quick-action-card">
                <div class="quick-action-icon">🎒</div>
                <h3 class="quick-action-title">Build Pack</h3>
                <p class="quick-action-desc">Create a custom pack</p>
            </a>
        </div>
    </section>

    <!-- Main Dashboard Grid -->
    <div class="trailhead-grid">
        
        <!-- Left Column -->
        <div class="trailhead-left">
            
            <!-- Next Trip Card -->
            <div class="card next-trip-card" id="next-trip-card">
                <div class="card-header">
                    <h2 class="card-title">Your Next Adventure</h2>
                </div>
                <div class="card-body" id="next-trip-content">
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text"></div>
                </div>
            </div>
            
            <!-- Pack Status Card -->
            <div class="card pack-status-card">
                <div class="card-header">
                    <h2 class="card-title">Pack Status</h2>
                    <a href="<?php echo route_url('backpacks'); ?>" class="card-link">View All</a>
                </div>
                <div class="card-body" id="pack-status-content">
                    <div class="pack-summary">
                        <div class="pack-metric">
                            <span class="metric-label">Base Weight</span>
                            <span class="metric-value" id="base-weight">--</span>
                        </div>
                        <div class="pack-metric">
                            <span class="metric-label">Total Items</span>
                            <span class="metric-value" id="total-items">--</span>
                        </div>
                    </div>
                    <div class="pack-actions">
                        <button class="btn btn-sm btn-secondary" onclick="window.location.href='<?php echo route_url('backpacks'); ?>?action=check'">Check Pack</button>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='<?php echo route_url('backpacks'); ?>?action=quick-pack'">Quick Pack</button>
                    </div>
                </div>
            </div>
            
            <!-- My Gear Shortcuts -->
            <div class="card gear-shortcuts-card">
                <div class="card-header">
                    <h2 class="card-title">My Gear</h2>
                    <a href="<?php echo route_url('backpacks'); ?>?view=gear-library" class="card-link">Manage</a>
                </div>
                <div class="card-body">
                    <div class="gear-quick-stats">
                        <div class="gear-stat">
                            <span class="gear-icon">📦</span>
                            <span class="gear-count" id="gear-total">--</span>
                            <span class="gear-label">Total Items</span>
                        </div>
                        <div class="gear-stat">
                            <span class="gear-icon">⭐</span>
                            <span class="gear-count" id="gear-favorites">--</span>
                            <span class="gear-label">Favorites</span>
                        </div>
                        <div class="gear-stat">
                            <span class="gear-icon">🆕</span>
                            <span class="gear-count" id="gear-recent">--</span>
                            <span class="gear-label">New This Month</span>
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-sm w-full" onclick="window.location.href='<?php echo route_url('backpacks'); ?>?view=gear-library&action=add'">Add New Gear</button>
                </div>
            </div>
            
        </div>
        
        <!-- Right Column -->
        <div class="trailhead-right">
            
            <!-- Achievements Card -->
            <div class="card achievements-card">
                <div class="card-header">
                    <h2 class="card-title">Trail Achievements</h2>
                    <div class="xp-display">
                        <span class="xp-icon">✨</span>
                        <span class="xp-value" id="total-xp">0</span> XP
                    </div>
                </div>
                <div class="card-body">
                    <div class="level-progress">
                        <div class="level-info">
                            <span class="level-label">Level</span>
                            <span class="level-value" id="user-level">1</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="level-progress" style="width: 0%"></div>
                        </div>
                        <div class="xp-needed">
                            <span id="xp-current">0</span> / <span id="xp-next">100</span> XP
                        </div>
                    </div>
                    
                    <div class="achievement-badges">
                        <div class="badge-item earned">
                            <span class="badge-icon">🥾</span>
                            <span class="badge-name">First Steps</span>
                        </div>
                        <div class="badge-item">
                            <span class="badge-icon">🏔️</span>
                            <span class="badge-name">Summit Seeker</span>
                        </div>
                        <div class="badge-item">
                            <span class="badge-icon">🌟</span>
                            <span class="badge-name">Trail Master</span>
                        </div>
                    </div>
                    
                    <div class="streak-display">
                        <span class="streak-icon">🔥</span>
                        <span class="streak-value" id="streak-days">0</span>
                        <span class="streak-label">Day Streak</span>
                    </div>
                </div>
            </div>
            
            <!-- Trail Stats -->
            <div class="card trail-stats-card">
                <h2 class="card-title">Trail Stats</h2>
                <div class="card-body">
                    <div class="stats-grid compact">
                        <div class="stat-item">
                            <span class="stat-icon">🏕️</span>
                            <span class="stat-value" id="trips-total">0</span>
                            <span class="stat-label">Trips</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-icon">📏</span>
                            <span class="stat-value" id="miles-total">0</span>
                            <span class="stat-label">Miles</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-icon">⛰️</span>
                            <span class="stat-value" id="elevation-total">0</span>
                            <span class="stat-label">Ft Climbed</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-icon">🎒</span>
                            <span class="stat-value" id="packs-total">0</span>
                            <span class="stat-label">Packs</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Recent Activity -->
    <section class="recent-activity">
        <h2 class="section-title">Recent Activity</h2>
        
        <div class="activity-grid">
            <!-- Recent Trips -->
            <div class="activity-section">
                <h3 class="activity-title">Your Adventures</h3>
                <div id="recent-trips" class="activity-list">
                    <div class="loading-state">Loading adventures...</div>
                </div>
            </div>
            
            <!-- Recent Backpacks -->
            <div class="activity-section">
                <h3 class="activity-title">Your Packs</h3>
                <div id="recent-backpacks" class="activity-list">
                    <div class="loading-state">Loading packs...</div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Include Trailhead styles -->
<style>
    <?php include __DIR__ . '/assets/css/trailhead.css'; ?>
</style>

<script>
// Load dashboard data
document.addEventListener('DOMContentLoaded', function() {
    loadTrailheadData();
});

async function loadTrailheadData() {
    // Load next trip
    loadNextTrip();
    
    // Load pack status
    loadPackStatus();
    
    // Load gear stats
    loadGearStats();
    
    // Load achievements
    loadAchievements();
    
    // Load trail stats
    loadTrailStats();
    
    // Load recent activity (keep existing)
    loadRecentTrips();
    loadRecentBackpacks();
}

async function loadNextTrip() {
    const container = document.getElementById('next-trip-content');
    
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (response.ok) {
            const tripsData = await response.json();
            const trips = tripsData.data || tripsData || [];
            
            // Find upcoming trips
            const now = new Date();
            const upcomingTrips = trips.filter(trip => {
                if (trip.start_date) {
                    const startDate = new Date(trip.start_date);
                    return startDate >= now;
                }
                return false;
            }).sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
            
            if (upcomingTrips.length > 0) {
                const nextTrip = upcomingTrips[0];
                const startDate = new Date(nextTrip.start_date).toLocaleDateString();
                const daysUntil = Math.ceil((new Date(nextTrip.start_date) - now) / (1000 * 60 * 60 * 24));
                
                container.innerHTML = `
                    <div class="trip-info">
                        <h3 class="trip-title">${escapeHtml(nextTrip.title)}</h3>
                        <div class="trip-details">
                            <span class="trip-detail">📅 ${startDate}</span>
                            <span class="trip-detail">⏳ ${daysUntil} days away</span>
                            ${nextTrip.location ? `<span class="trip-detail">📍 ${escapeHtml(nextTrip.location)}</span>` : ''}
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="window.location.href='<?php echo route_url('trips'); ?>?id=${nextTrip.id}'">Continue Planning</button>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="no-trip-message">
                        <div class="no-trip-icon">🏔️</div>
                        <p class="no-trip-text">No upcoming adventures planned</p>
                        <button class="btn btn-primary" onclick="window.location.href='<?php echo route_url('trips'); ?>?action=new'">Plan Your Next Trip</button>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading next trip:', error);
        container.innerHTML = '<div class="error-state">Failed to load trip data</div>';
    }
}

async function loadPackStatus() {
    try {
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (response.ok) {
            const backpacksData = await response.json();
            const backpacks = backpacksData.data || backpacksData || [];
            
            if (backpacks.length > 0) {
                // Get the most recently used pack
                const latestPack = backpacks[0];
                const baseWeight = latestPack.base_weight_g ? (latestPack.base_weight_g / 1000).toFixed(1) + 'kg' : '--';
                const itemCount = latestPack.total_items || 0;
                
                document.getElementById('base-weight').textContent = baseWeight;
                document.getElementById('total-items').textContent = itemCount;
            }
        }
    } catch (error) {
        console.error('Error loading pack status:', error);
    }
}

async function loadGearStats() {
    // This would need a gear endpoint - for now using placeholder
    document.getElementById('gear-total').textContent = '42';
    document.getElementById('gear-favorites').textContent = '8';
    document.getElementById('gear-recent').textContent = '3';
}

async function loadAchievements() {
    try {
        const response = await fetch('/BTT/api/routes/gamification.php?action=status');
        if (response.ok) {
            const stats = await response.json();
            
            // Update XP and level
            document.getElementById('total-xp').textContent = stats.total_xp || 0;
            document.getElementById('user-level').textContent = stats.level || 1;
            document.getElementById('xp-current').textContent = stats.current_xp || 0;
            document.getElementById('xp-next').textContent = stats.next_level_xp || 100;
            document.getElementById('streak-days').textContent = stats.streak_days || 0;
            
            // Update progress bar
            const progress = ((stats.current_xp || 0) / (stats.next_level_xp || 100)) * 100;
            document.getElementById('level-progress').style.width = progress + '%';
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
    }
}

async function loadTrailStats() {
    try {
        // Load trips
        const tripsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (tripsResponse.ok) {
            const tripsData = await tripsResponse.json();
            const trips = tripsData.data || tripsData || [];
            document.getElementById('trips-total').textContent = trips.length;
            
            // Calculate total miles and elevation
            let totalMiles = 0;
            let totalElevation = 0;
            trips.forEach(trip => {
                if (trip.distance && trip.distance_unit === 'miles') {
                    totalMiles += parseFloat(trip.distance);
                } else if (trip.distance && trip.distance_unit === 'km') {
                    totalMiles += parseFloat(trip.distance) * 0.621371;
                }
                if (trip.elevation_gain) {
                    totalElevation += parseInt(trip.elevation_gain);
                }
            });
            
            document.getElementById('miles-total').textContent = Math.round(totalMiles);
            document.getElementById('elevation-total').textContent = totalElevation.toLocaleString();
        }
        
        // Load packs
        const packsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
        if (packsResponse.ok) {
            const packsData = await packsResponse.json();
            const packs = packsData.data || packsData || [];
            document.getElementById('packs-total').textContent = packs.length;
        }
    } catch (error) {
        console.error('Error loading trail stats:', error);
    }
}

// Quick action: Copy Last Trip
async function copyLastTrip() {
    if (typeof UX !== 'undefined' && UX.loading) {
        UX.loading.show('Finding your last adventure...');
    }
    
    try {
        // Get the most recent trip
        const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
        if (response.ok) {
            const tripsData = await response.json();
            const trips = tripsData.data || tripsData || [];
            
            if (trips.length > 0) {
                // Sort by created date or ID to get most recent
                const lastTrip = trips[trips.length - 1];
                
                // Redirect to new trip form with copied data
                const params = new URLSearchParams({
                    action: 'new',
                    copy_from: lastTrip.id,
                    title: lastTrip.title + ' (Copy)',
                    location: lastTrip.location || '',
                    trip_type: lastTrip.trip_type || ''
                });
                
                window.location.href = '<?php echo route_url('trips'); ?>?' + params.toString();
            } else {
                if (typeof UX !== 'undefined' && UX.toast) {
                    UX.toast.info('No previous trips to copy. Start fresh!');
                }
                window.location.href = '<?php echo route_url('trips'); ?>?action=new';
            }
        }
    } catch (error) {
        console.error('Error copying trip:', error);
        if (typeof UX !== 'undefined' && UX.toast) {
            UX.toast.error('Failed to copy trip. Please try again.');
        }
    } finally {
        if (typeof UX !== 'undefined' && UX.loading) {
            UX.loading.hide();
        }
    }
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
