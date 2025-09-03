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
require_once __DIR__ . '/includes/template-header.php';
?>

<body class="dashboard-page">
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

<!-- Quick Actions Bar - Horizontal -->
    <section class="quick-actions-bar">
        <div class="quick-actions-container">
            <a href="<?php echo route_url('trips'); ?>?action=new" class="quick-action-item">
                <span class="quick-icon">🗺️</span>
                <span class="quick-label">Plan Trip</span>
            </a>
            <a href="<?php echo route_url('backpacks'); ?>?action=quick-pack" class="quick-action-item">
                <span class="quick-icon">⚡</span>
                <span class="quick-label">Quick Pack</span>
            </a>
            <button class="quick-action-item" onclick="copyLastTrip()">
                <span class="quick-icon">📋</span>
                <span class="quick-label">Copy Trip</span>
            </button>
            <a href="<?php echo route_url('backpacks'); ?>" class="quick-action-item">
                <span class="quick-icon">🎒</span>
                <span class="quick-label">Build Pack</span>
            </a>
            <a href="<?php echo route_url('gear'); ?>" class="quick-action-item">
                <span class="quick-icon">⛺</span>
                <span class="quick-label">Add Gear</span>
            </a>
        </div>
    </section>

    <!-- Main Dashboard Grid - Truly Horizontal Layout -->
    <div class="dashboard-horizontal-grid">
        
        <!-- Top Row: Trips, Backpacks, and Gear in one line -->
        <div class="primary-row">
            
            <!-- Trips Compact Card -->
            <div class="compact-card trips-card">
                <div class="compact-header">
                    <span class="card-icon">🏔️</span>
                    <h3>Trips</h3>
                    <a href="<?php echo route_url('trips'); ?>" class="link-arrow">→</a>
                </div>
                <div class="compact-stats">
                    <div class="stat">
                        <span class="stat-num" id="upcoming-trips">0</span>
                        <span class="stat-lbl">Upcoming</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="completed-trips">0</span>
                        <span class="stat-lbl">Done</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="total-miles">0</span>
                        <span class="stat-lbl">Miles</span>
                    </div>
                </div>
                <div id="next-trip-mini" class="mini-preview">
                    <!-- Dynamic content -->
                </div>
            </div>
            
            <!-- Backpacks Compact Card -->
            <div class="compact-card packs-card">
                <div class="compact-header">
                    <span class="card-icon">🎒</span>
                    <h3>Backpacks</h3>
                    <a href="<?php echo route_url('backpacks'); ?>" class="link-arrow">→</a>
                </div>
                <div class="compact-stats">
                    <div class="stat">
                        <span class="stat-num" id="total-packs">0</span>
                        <span class="stat-lbl">Packs</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="gear-items">0</span>
                        <span class="stat-lbl">Gear</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="avg-weight">0</span>
                        <span class="stat-lbl">Avg kg</span>
                    </div>
                </div>
                <div class="mini-actions">
                    <button class="mini-btn" onclick="window.location.href='<?php echo route_url('backpacks'); ?>?action=check'">Check</button>
                    <button class="mini-btn primary" onclick="window.location.href='<?php echo route_url('backpacks'); ?>?action=new'">New</button>
                </div>
            </div>
            
            <!-- My Gear Compact Card -->
            <div class="compact-card gear-card">
                <div class="compact-header">
                    <span class="card-icon">⛺</span>
                    <h3>My Gear</h3>
                    <a href="<?php echo route_url('gear'); ?>" class="link-arrow">→</a>
                </div>
                <div class="compact-stats">
                    <div class="stat">
                        <span class="stat-num" id="gear-total">42</span>
                        <span class="stat-lbl">Items</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="gear-favorites">8</span>
                        <span class="stat-lbl">Favs</span>
                    </div>
                    <div class="stat">
                        <span class="stat-num" id="gear-recent">3</span>
                        <span class="stat-lbl">New</span>
                    </div>
                </div>
                <button class="mini-btn w-full" onclick="window.location.href='<?php echo route_url('gear'); ?>'">Add Gear</button>
            </div>
            
            <!-- Trail Stats Compact -->
            <div class="compact-card stats-card">
                <div class="compact-header">
                    <span class="card-icon">📊</span>
                    <h3>Trail Stats</h3>
                </div>
                <div class="stats-horizontal">
                    <div class="stat-h">
                        <span class="h-icon">🏕️</span>
                        <span class="h-num" id="trips-total">0</span>
                        <span class="h-lbl">Trips</span>
                    </div>
                    <div class="stat-h">
                        <span class="h-icon">📏</span>
                        <span class="h-num" id="miles-total">0</span>
                        <span class="h-lbl">Miles</span>
                    </div>
                    <div class="stat-h">
                        <span class="h-icon">⛰️</span>
                        <span class="h-num" id="elevation-total">0</span>
                        <span class="h-lbl">Ft</span>
                    </div>
                    <div class="stat-h">
                        <span class="h-icon">🎒</span>
                        <span class="h-num" id="packs-total">0</span>
                        <span class="h-lbl">Packs</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row: Achievements and Activity Side by Side -->
        <div class="secondary-row">
            
            <!-- Achievements Horizontal -->
            <div class="achievements-horizontal">
                <div class="ach-header">
                    <h3>🏆 Achievements</h3>
                    <div class="xp-badge">
                        <span id="total-xp">0</span> XP
                    </div>
                </div>
                <div class="ach-content">
                    <div class="level-bar">
                        <span class="lvl">Lvl <span id="user-level">1</span></span>
                        <div class="progress-inline">
                            <div class="progress-fill" id="level-progress" style="width: 0%"></div>
                        </div>
                        <span class="xp-text"><span id="xp-current">0</span>/<span id="xp-next">100</span></span>
                    </div>
                    <div class="badges-row">
                        <div class="badge-mini earned" title="First Steps">🥾</div>
                        <div class="badge-mini" title="Summit Seeker">🏔️</div>
                        <div class="badge-mini" title="Trail Master">🌟</div>
                        <div class="streak-mini">
                            🔥 <span id="streak-days">0</span>
                        </div>
                    </div>
                </div>
            </div>
    </div>

            <!-- Recent Activity Horizontal -->
            <div class="activity-horizontal">
                <div class="activity-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="activity-tabs">
                    <div class="tab-section">
                        <h4>Your Adventures</h4>
                        <div id="recent-trips" class="activity-compact">
                            <!-- Dynamic content -->
                        </div>
                    </div>
                    <div class="tab-section">
                        <h4>Your Packs</h4>
                        <div id="recent-backpacks" class="activity-compact">
                            <!-- Dynamic content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Include Dashboard styles -->
<link rel="stylesheet" href="/BTT/assets/css/dashboard-compact.css">

<script>
// Load dashboard data
document.addEventListener('DOMContentLoaded', function() {
    loadTrailheadData();
});

async function loadTrailheadData() {
    // Load overview data
    loadTripsOverview();
    loadPacksOverview();
    
    // Load gear stats
    loadGearStats();
    
    // Load achievements
    loadAchievements();
    
    // Load trail stats
    loadTrailStats();
    
    // Load recent activity
    loadRecentTrips();
    loadRecentBackpacks();
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
            
            // Update stats
            document.getElementById('upcoming-trips').textContent = upcomingTrips.length;
            document.getElementById('completed-trips').textContent = completedTrips.length;
            document.getElementById('total-miles').textContent = Math.round(totalMiles);
            
            // Show next trip mini preview
            const previewContainer = document.getElementById('next-trip-mini');
            if (upcomingTrips.length > 0) {
                const nextTrip = upcomingTrips.sort((a, b) => new Date(a.start_date) - new Date(b.start_date))[0];
                const daysUntil = Math.ceil((new Date(nextTrip.start_date) - now) / (1000 * 60 * 60 * 24));
                
                previewContainer.innerHTML = `
                    <strong>${escapeHtml(nextTrip.title)}</strong><br>
                    <span style="opacity: 0.7">📅 ${daysUntil} days away</span>
                `;
            } else {
                previewContainer.innerHTML = `<span style="opacity: 0.6">No upcoming trips</span>`;
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
            document.getElementById('gear-items').textContent = gearCount;
            document.getElementById('avg-weight').textContent = avgWeight.toFixed(1) + 'kg';
        }
    } catch (error) {
        console.error('Error loading packs overview:', error);
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
