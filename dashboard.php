<?php
/**
 * BeyondTrailTales - Dashboard (Trailhead)
 * 
 * Gamified dashboard using the new Duolingo Forest theme system
 * with engaging animations and interactive elements
 */

// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication for dashboard
require_auth();

// Set page metadata
$pageTitle = 'Trailhead';
$pageDescription = 'Your adventure command center - plan trips, manage gear, and track your progress';
$pageId = 'dashboard';

// Add modern unified CSS for enhanced UI/UX
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/btt-unified-modern.css';
$pageStyles[] = 'css/dashboard-clean.css?v=' . time(); // Clean dashboard design with cache bust
$pageStyles[] = 'css/nav-scrollbar-fix.css?v=' . time(); // Fix navigation scrollbar

// Force no-cache headers to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include Database class
require_once __DIR__ . '/api/classes/Database.php';

// Get database connection
$db = Database::getInstance();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Fetch real dashboard data from the database
$dashboardStats = [];

try {
    // Debug: Log user_id to check if it's correct
    error_log("Dashboard loading for user_id: " . $user_id);
    
    // Get trip statistics - fetchOne returns array, get first value
    $trips_count = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ?", [$user_id]);
    $dashboardStats['trips_count'] = $trips_count ? $trips_count['count'] : 0;
    error_log("Trips count: " . $dashboardStats['trips_count']);
    
    $completed_trips = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ? AND completed = 1", [$user_id]);
    $dashboardStats['completed_trips'] = $completed_trips ? $completed_trips['count'] : 0;
    
    $total_miles = $db->fetchOne("SELECT COALESCE(SUM(distance), 0) as miles FROM trips WHERE user_id = ? AND completed = 1 AND distance_unit = 'miles'", [$user_id]);
    $dashboardStats['total_miles'] = $total_miles ? $total_miles['miles'] : 0;
    
    $gear_items = $db->fetchOne("SELECT COUNT(*) as count FROM user_gear WHERE user_id = ? AND deleted_at IS NULL", [$user_id]);
    $dashboardStats['gear_items'] = $gear_items ? $gear_items['count'] : 0;
    
    $backpacks = $db->fetchOne("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?", [$user_id]);
    $dashboardStats['backpacks'] = $backpacks ? $backpacks['count'] : 0;
    
    $upcoming_trips = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ? AND completed = 0 AND start_date > date('now')", [$user_id]);
    $dashboardStats['upcoming_trips'] = $upcoming_trips ? $upcoming_trips['count'] : 0;
    
    // Get recent trips with photos for activity feed
    $recentTrips = $db->fetchAll("
        SELECT title, photo_path, photo_alt_text, 
               CASE 
                 WHEN completed = 1 THEN 'trip_completed' 
                 ELSE 'trip_planned' 
               END as type,
               CASE 
                 WHEN updated_at > datetime('now', '-1 day') THEN 'today'
                 WHEN updated_at > datetime('now', '-2 days') THEN '1 day ago'
                 WHEN updated_at > datetime('now', '-7 days') THEN substr(cast((julianday('now') - julianday(updated_at)) as int), 1, 1) || ' days ago'
                 WHEN updated_at > datetime('now', '-30 days') THEN substr(cast((julianday('now') - julianday(updated_at))/7 as int), 1, 1) || ' weeks ago'
                 ELSE 'some time ago'
               END as date,
               distance, elevation_gain, completed
        FROM trips 
        WHERE user_id = ? 
        ORDER BY updated_at DESC 
        LIMIT 3", [$user_id]);
    
    // Convert to activity format
    $recentActivity = [];
    foreach ($recentTrips as $trip) {
        $xp = 25; // Base XP
        if ($trip['completed']) {
            $xp += ($trip['distance'] * 5) + (($trip['elevation_gain'] / 1000) * 10); // Distance and elevation bonuses
        }
        
        $recentActivity[] = [
            'type' => $trip['type'],
            'title' => $trip['title'],
            'date' => $trip['date'],
            'xp' => round($xp),
            'photo_path' => $trip['photo_path'],
            'photo_alt_text' => $trip['photo_alt_text']
        ];
    }
    
    // Get recent trips for the adventures card (increased limit to 6)
    $recentTripsData = $db->fetchAll("
        SELECT id, title, distance, elevation_gain, start_date, completed, photo_path, photo_alt_text,
               CASE 
                 WHEN completed = 1 THEN 'Completed ' || 
                   CASE 
                     WHEN start_date > datetime('now', '-1 day') THEN 'today'
                     WHEN start_date > datetime('now', '-2 days') THEN '1 day ago'
                     WHEN start_date > datetime('now', '-7 days') THEN substr(cast((julianday('now') - julianday(start_date)) as int), 1, 1) || ' days ago'
                     WHEN start_date > datetime('now', '-30 days') THEN substr(cast((julianday('now') - julianday(start_date))/7 as int), 1, 1) || ' weeks ago'
                     ELSE 'some time ago'
                   END
                 ELSE 'Planned for ' || date(start_date)
               END as status_text
        FROM trips 
        WHERE user_id = ? 
        ORDER BY CASE WHEN completed = 1 THEN start_date ELSE updated_at END DESC 
        LIMIT 6", [$user_id]);

    // Get ALL backpacks with real data (removed LIMIT to show all)
    $recentBackpacks = $db->fetchAll("
        SELECT b.id, b.name, b.description, b.created_at, b.updated_at,
               COUNT(DISTINCT bg.gear_id) as item_count,
               COALESCE(SUM(CASE WHEN g.weight_g IS NOT NULL THEN g.weight_g ELSE 0 END), 0) as total_weight_g
        FROM backpacks b
        LEFT JOIN backpack_gear bg ON b.id = bg.backpack_id
        LEFT JOIN user_gear g ON bg.gear_id = g.id AND g.deleted_at IS NULL
        WHERE b.user_id = ?
        GROUP BY b.id, b.name, b.description, b.created_at, b.updated_at
        ORDER BY b.updated_at DESC", [$user_id]);

    // Get recent gear additions (increased to show more items)
    $recentGearItems = $db->fetchAll("
        SELECT name, category, weight_g, created_at
        FROM user_gear 
        WHERE user_id = ? AND deleted_at IS NULL
        ORDER BY created_at DESC 
        LIMIT 5", [$user_id]);

} catch (Exception $e) {
    // Fallback to default values if database query fails
    error_log("Dashboard data fetch error: " . $e->getMessage());
    $dashboardStats = [
        'trips_count' => 0,
        'completed_trips' => 0,
        'total_miles' => 0,
        'gear_items' => 0,
        'backpacks' => 0,
        'upcoming_trips' => 0
    ];
    $recentActivity = [];
    $recentTripsData = [];
    $recentBackpacks = [];
    $recentGearItems = [];
}

$achievements = [
    ['id' => 'first_summit', 'title' => 'First Summit', 'description' => 'Completed your first mountain peak', 'earned' => $dashboardStats['completed_trips'] >= 1],
    ['id' => 'gear_collector', 'title' => 'Gear Collector', 'description' => 'Added 50+ gear items', 'earned' => $dashboardStats['gear_items'] >= 50],
    ['id' => 'pack_master', 'title' => 'Pack Master', 'description' => 'Created 5 different packs', 'earned' => $dashboardStats['backpacks'] >= 5],
    ['id' => 'trail_veteran', 'title' => 'Trail Veteran', 'description' => 'Hiked 500+ miles', 'earned' => $dashboardStats['total_miles'] >= 500]
];

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Modern Dashboard Hero -->
<div class="modern-dashboard-hero modern-animate-slide-in">
    <div class="modern-dashboard-hero-content">
        <h1 class="modern-dashboard-hero-title">Welcome back to your Trailhead! 🏔️</h1>
        <p class="modern-dashboard-hero-subtitle">Ready for your next adventure? Let's explore what's waiting for you on the trails.</p>
        
        <!-- Modern Quick Actions -->
        <div class="modern-dashboard" style="grid-template-columns: repeat(3, 1fr); gap: var(--space-lg); max-width: 600px; margin: var(--space-xl) auto 0;">
            <a href="<?php echo route_url('trips'); ?>?action=new" class="modern-btn modern-btn-lg modern-animate-slide-in" style="background: var(--adventure-primary);">
                🗺️ Plan Adventure
            </a>
            <a href="<?php echo route_url('backpacks'); ?>?action=new" class="modern-btn modern-btn-lg modern-animate-slide-in" style="background: var(--pack-primary);">
                🎒 Build Pack
            </a>
            <a href="<?php echo route_url('gear'); ?>?action=add" class="modern-btn modern-btn-lg modern-animate-slide-in" style="background: var(--gear-primary);">
                📦 Add Gear
            </a>
        </div>
    </div>
</div>

<!-- Dashboard Stats Grid -->
<div class="stats-grid animate-fade-in-up animate-delay-300">
    <div class="stat-card card-glow">
        <div class="stat-number animate-count-up"><?php echo $dashboardStats['trips_count']; ?></div>
        <div class="stat-label">Total Adventures</div>
    </div>
    <div class="stat-card card-glow">
        <div class="stat-number animate-count-up"><?php echo $dashboardStats['completed_trips']; ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card card-glow">
        <div class="stat-number animate-count-up"><?php echo $dashboardStats['total_miles']; ?></div>
        <div class="stat-label">Miles Hiked</div>
    </div>
    <div class="stat-card card-glow">
        <div class="stat-number animate-count-up"><?php echo $dashboardStats['gear_items']; ?></div>
        <div class="stat-label">Gear Items</div>
    </div>
</div>

<!-- Main Dashboard Content Grid -->
<div class="dashboard-primary-grid animate-fade-in-up animate-delay-500">
    
    <!-- Recent Adventures Card -->
    <div class="card card-adventures dashboard-card dashboard-card-adventures animate-float">
        <div class="card-header">
            <h2 class="card-title">🗺️ Recent Adventures</h2>
            <p class="card-subtitle">Your latest trail conquests</p>
        </div>
        <div class="card-body">
            <?php 
            // Debug output
            error_log("Recent trips data count: " . count($recentTripsData ?? []));
            if (!empty($recentTripsData)): ?>
                <div class="recent-trips-list">
                    <?php foreach ($recentTripsData as $trip): ?>
                        <div class="trip-item" onclick="location.href='<?php echo route_url('trips'); ?>?view=<?= $trip['id'] ?? '' ?>'">
                            <?php if (!empty($trip['photo_path']) && file_exists(__DIR__ . '/' . $trip['photo_path'])): ?>
                                <div class="trip-photo">
                                    <img src="<?= htmlspecialchars($trip['photo_path']) ?>" 
                                         alt="<?= htmlspecialchars($trip['photo_alt_text'] ?? 'Trip photo') ?>" 
                                         class="trip-photo-img"
                                         onerror="this.parentElement.innerHTML='<div class=\'trip-icon\'><?= $trip['completed'] ? '⛰️' : '🗺️' ?></div>'">
                                </div>
                            <?php else: ?>
                                <div class="trip-icon"><?= $trip['completed'] ? '⛰️' : '🗺️' ?></div>
                            <?php endif; ?>
                            <div class="trip-info">
                                <h4><?= htmlspecialchars($trip['title'] ?? 'Untitled Adventure') ?></h4>
                                <p>
                                    <?php if (($trip['distance'] ?? 0) > 0): ?>
                                        <?= number_format($trip['distance'], 1) ?> miles
                                        <?php if (($trip['elevation_gain'] ?? 0) > 0): ?>
                                            • <?= number_format($trip['elevation_gain']) ?> ft elevation
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Adventure details to be added
                                    <?php endif; ?>
                                </p>
                                <span class="trip-date"><?= htmlspecialchars($trip['status_text'] ?? 'Adventure planned') ?></span>
                            </div>
                            <div class="trip-badge">
                                <?php if ($trip['completed']): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Planned</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🗺️</div>
                    <h3 class="empty-state-title">No adventures yet!</h3>
                    <p class="empty-state-description">Start planning your first hiking adventure and begin earning XP!</p>
                    <div style="margin-top: 1rem;">
                        <a href="<?php echo route_url('trips'); ?>?action=new" class="btn btn-adventures">Plan First Adventure</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo route_url('trips'); ?>" class="btn btn-adventures">View All Adventures</a>
        </div>
    </div>
    
    <!-- Backpack Management Card -->
    <div class="card card-backpacks dashboard-card dashboard-card-backpacks animate-float">
        <div class="card-header">
            <h2 class="card-title">🎒 My Backpacks</h2>
            <p class="card-subtitle">Organized gear for every adventure</p>
        </div>
        <div class="card-body">
            <?php 
            error_log("Recent backpacks data count: " . count($recentBackpacks ?? []));
            if (!empty($recentBackpacks)): ?>
                <div class="pack-preview-grid">
                    <?php foreach ($recentBackpacks as $pack): ?>
                        <div class="pack-preview" onclick="location.href='<?php echo route_url('backpacks'); ?>?view=<?= $pack['id'] ?>'">
                            <div class="pack-icon">🎒</div>
                            <div style="flex: 1;">
                                <h4><?= htmlspecialchars($pack['name'] ?? 'Unnamed Pack') ?></h4>
                                <p>
                                    <?= (int)$pack['item_count'] ?> items • 
                                    <?php 
                                    $weightLbs = round(($pack['total_weight_g'] ?? 0) / 453.592, 1);
                                    echo $weightLbs > 0 ? $weightLbs . ' lbs' : 'No weight data';
                                    ?>
                                </p>
                                <?php if (!empty($pack['description'])): ?>
                                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-top: 0.25rem;">
                                        <?= htmlspecialchars(substr($pack['description'], 0, 50)) ?><?= strlen($pack['description']) > 50 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <span class="pack-status badge <?= $pack['item_count'] > 0 ? 'badge-success' : 'badge-info' ?>">
                                <?= $pack['item_count'] > 0 ? 'Ready' : 'Empty' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎒</div>
                    <h3 class="empty-state-title">No packs created yet!</h3>
                    <p class="empty-state-description">Create your first backpack and start organizing your gear efficiently.</p>
                    <div style="margin-top: 1rem;">
                        <a href="<?php echo route_url('backpacks'); ?>?action=new" class="btn btn-backpacks">Create First Pack</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo route_url('backpacks'); ?>?action=new" class="btn btn-backpacks">Create New Pack</a>
            <a href="<?php echo route_url('backpacks'); ?>" class="btn btn-secondary">View All Packs</a>
        </div>
    </div>
    
    <!-- Gear Library Card -->
    <div class="card card-gear dashboard-card dashboard-card-gear animate-float">
        <div class="card-header">
            <h2 class="card-title">📦 Gear Library</h2>
            <p class="card-subtitle">Your complete equipment collection</p>
        </div>
        <div class="card-body">
            <?php if (!empty($recentGearItems)): ?>
                <div class="gear-summary">
                    <div class="gear-total">
                        <div class="gear-total-number"><?= $dashboardStats['gear_items'] ?></div>
                        <div class="gear-total-label">Total Items</div>
                    </div>
                </div>
                
                <!-- Recent Gear Additions -->
                <div class="recent-gear">
                    <h4>Recently Added</h4>
                    <?php foreach ($recentGearItems as $gear): ?>
                        <div class="gear-item-row" onclick="location.href='<?php echo route_url('gear'); ?>'">
                            <span class="gear-item-icon">
                                <?php
                                $categoryIcons = [
                                    'shelter' => '⛺',
                                    'sleep' => '🛏️',
                                    'cooking' => '🍳',
                                    'water' => '💧',
                                    'clothing' => '👕',
                                    'footwear' => '🥾',
                                    'navigation' => '🧭',
                                    'first-aid' => '🏥',
                                    'electronics' => '📱',
                                    'tools' => '🔧'
                                ];
                                echo $categoryIcons[$gear['category'] ?? 'other'] ?? '📦';
                                ?>
                            </span>
                            <span class="gear-item-name"><?= htmlspecialchars($gear['name'] ?? 'Unnamed Item') ?></span>
                            <span class="gear-item-weight">
                                <?php
                                if (($gear['weight_g'] ?? 0) > 0) {
                                    $weightLbs = round($gear['weight_g'] / 453.592, 1);
                                    echo $weightLbs . ' lbs';
                                } else {
                                    echo 'No weight';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3 class="empty-state-title">Build your gear library!</h3>
                    <p class="empty-state-description">Add your hiking equipment to track weights, organize gear, and build perfect packs.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo route_url('gear'); ?>?action=add" class="btn btn-gear">Add Gear</a>
            <a href="<?php echo route_url('gear'); ?>" class="btn btn-secondary">Browse Library</a>
        </div>
    </div>
    
    <!-- Progress & Achievements Card -->
    <div class="card card-progress dashboard-card dashboard-card-progress animate-float twinkle-stars">
        <div class="card-header">
            <h2 class="card-title">🏆 Achievements</h2>
            <p class="card-subtitle">Track your hiking milestones</p>
        </div>
        <div class="card-body">
            <div class="achievements-grid">
                <?php foreach ($achievements as $achievement): ?>
                    <div class="achievement <?php echo $achievement['earned'] ? 'earned' : 'locked'; ?>">
                        <div class="achievement-icon">
                            <?php if ($achievement['earned']): ?>
                                🏅
                            <?php else: ?>
                                🔒
                            <?php endif; ?>
                        </div>
                        <div class="achievement-content">
                            <h4 class="achievement-title"><?php echo htmlspecialchars($achievement['title']); ?></h4>
                            <p class="achievement-description"><?php echo htmlspecialchars($achievement['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer">
            <a href="<?php echo route_url('dashboard'); ?>?view=achievements" class="btn btn-progress">View All Achievements</a>
        </div>
    </div>
    
</div>

<!-- Secondary Dashboard Content -->
<div class="dashboard-secondary-grid animate-fade-in-up animate-delay-700">
    
    <!-- Activity Feed -->
    <div class="card animate-float">
        <div class="card-header">
            <h2 class="card-title">📈 Recent Activity</h2>
            <p class="card-subtitle">Your latest trail achievements</p>
        </div>
        <div class="card-body">
            <?php if (!empty($recentActivity)): ?>
                <div class="activity-feed">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <?php if (!empty($activity['photo_path'])): ?>
                                <div class="activity-photo">
                                    <img src="<?= htmlspecialchars($activity['photo_path']) ?>" 
                                         alt="<?= htmlspecialchars($activity['photo_alt_text'] ?? 'Trip photo') ?>" 
                                         class="activity-photo-img">
                                </div>
                            <?php else: ?>
                                <div class="activity-icon">
                                    <?php 
                                    $icons = [
                                        'trip_completed' => '✅',
                                        'trip_planned' => '🗺️',
                                        'gear_added' => '📦',
                                        'pack_created' => '🎒',
                                        'achievement_earned' => '🏆'
                                    ];
                                    echo $icons[$activity['type']] ?? '📝';
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="activity-content">
                                <h4><?php echo htmlspecialchars($activity['title'] ?? 'Activity'); ?></h4>
                                <span class="activity-date"><?php echo htmlspecialchars($activity['date'] ?? 'Recently'); ?></span>
                            </div>
                            <div class="activity-xp">
                                <span class="badge badge-success">+<?php echo $activity['xp']; ?> XP</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📈</div>
                    <h3 class="empty-state-title">No activity yet!</h3>
                    <p class="empty-state-description">Start exploring to see your activity feed here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Weather Widget -->
    <div class="card animate-float">
        <div class="card-header">
            <h2 class="card-title">🌤️ Trail Conditions</h2>
            <p class="card-subtitle">Current conditions for your area</p>
        </div>
        <div class="card-body">
            <div class="weather-info">
                <div class="current-weather">
                    <div class="weather-icon">☀️</div>
                    <div class="weather-temp">72°F</div>
                    <div class="weather-desc">Partly Cloudy</div>
                </div>
                <div class="weather-details">
                    <div class="weather-detail">
                        <span class="detail-label">Humidity</span>
                        <span class="detail-value">45%</span>
                    </div>
                    <div class="weather-detail">
                        <span class="detail-label">Wind</span>
                        <span class="detail-value">8 mph</span>
                    </div>
                    <div class="weather-detail">
                        <span class="detail-label">UV Index</span>
                        <span class="detail-value">6</span>
                    </div>
                </div>
            </div>
            <div class="trail-conditions">
                <h4>Trail Alerts</h4>
                <div class="condition-item">
                    <span class="condition-icon">✅</span>
                    <span class="condition-text">White Mountains: Good conditions</span>
                </div>
                <div class="condition-item">
                    <span class="condition-icon">⚠️</span>
                    <span class="condition-text">Green Mountains: Muddy trails</span>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-secondary">Update Location</button>
        </div>
    </div>
    
    <!-- Upcoming Trips -->
    <div class="card animate-float">
        <div class="card-header">
            <h2 class="card-title">📅 Upcoming Adventures</h2>
            <p class="card-subtitle">Your planned expeditions</p>
        </div>
        <div class="card-body">
            <?php if ($dashboardStats['upcoming_trips'] > 0): ?>
                <div class="upcoming-trips">
                    <div class="trip-preview">
                        <div class="trip-date">
                            <div class="date-month">OCT</div>
                            <div class="date-day">15</div>
                        </div>
                        <div class="trip-details">
                            <h4>Presidential Traverse</h4>
                            <p>18.2 miles • NH, USA</p>
                            <span class="trip-prep badge badge-warning">Pack Ready: 85%</span>
                        </div>
                    </div>
                    <div class="trip-preview">
                        <div class="trip-date">
                            <div class="date-month">NOV</div>
                            <div class="date-day">3</div>
                        </div>
                        <div class="trip-details">
                            <h4>Mount Katahdin</h4>
                            <p>10.4 miles • ME, USA</p>
                            <span class="trip-prep badge badge-info">Planning</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <h3 class="empty-state-title">No trips planned</h3>
                    <p class="empty-state-description">Plan your next adventure and start building excitement!</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="<?php echo route_url('trips'); ?>?action=new" class="btn btn-primary">Plan New Trip</a>
        </div>
    </div>
    
</div>


<?php require_once __DIR__ . '/includes/template-footer.php'; ?>