<?php
/**
 * Dashboard - Main landing page with summaries
 */
require_once dirname(__DIR__) . '/app/config.php';

// Get stats from database
$stats = [
    'backpacks' => 0,
    'trips' => 0,
    'gear' => 0,
    'upcoming_trips' => 0,
    'completed_trips' => 0,
    'total_distance' => 0
];

try {
    $pdo = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get counts
    $stats['backpacks'] = $pdo->query("SELECT COUNT(*) FROM backpacks")->fetchColumn();
    $stats['trips'] = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
    $stats['gear'] = $pdo->query("SELECT COUNT(*) FROM gear_items")->fetchColumn();
    
    // Get upcoming trips (future start date)
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE start_date > ?");
    $stmt->execute([$today]);
    $stats['upcoming_trips'] = $stmt->fetchColumn();
    
    // Get completed trips
    $stats['completed_trips'] = $pdo->query("SELECT COUNT(*) FROM trips WHERE completed = 1")->fetchColumn();
    
    // Get total distance
    $stats['total_distance'] = $pdo->query("SELECT COALESCE(SUM(distance), 0) FROM trips WHERE completed = 1")->fetchColumn();
    
    // Get recent trips
    $recentTrips = $pdo->query("
        SELECT t.*, b.name as backpack_name 
        FROM trips t 
        LEFT JOIN backpacks b ON t.backpack_id = b.id 
        ORDER BY t.created_at DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get favorite trips
    $favoriteTrips = $pdo->query("
        SELECT t.*, b.name as backpack_name 
        FROM trips t 
        LEFT JOIN backpacks b ON t.backpack_id = b.id 
        WHERE t.favorite = 1 
        ORDER BY t.start_date DESC 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle error silently, stats remain at 0
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BeyondTrailTales</title>
    <link rel="stylesheet" href="/BTT/assets/css/ux-refresh.css">
    <link rel="stylesheet" href="/BTT/assets/css/dashboard-clean.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0d3b2e 0%, #1a5f4a 100%);
            color: #e8f5e9;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: #81c784;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            opacity: 0.9;
            font-size: 1.2rem;
        }
        
        .nav-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 3rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-link {
            color: #81c784;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(129, 199, 132, 0.2);
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border-color: #81c784;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #a5d6a7;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            opacity: 0.8;
            font-size: 0.95rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .content-card h2 {
            color: #81c784;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .trip-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .trip-item:hover {
            background: rgba(129, 199, 132, 0.1);
            transform: translateX(4px);
        }
        
        .trip-item h3 {
            color: #a5d6a7;
            margin-bottom: 0.5rem;
        }
        
        .trip-meta {
            display: flex;
            gap: 1rem;
            opacity: 0.8;
            font-size: 0.9rem;
            flex-wrap: wrap;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, rgba(129, 199, 132, 0.2) 0%, rgba(76, 175, 80, 0.2) 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(129, 199, 132, 0.3);
        }
        
        .welcome-banner h2 {
            color: #81c784;
            margin-bottom: 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            opacity: 0.7;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-upcoming {
            background: rgba(255, 193, 7, 0.3);
            color: #ffc107;
        }
        
        .badge-completed {
            background: rgba(76, 175, 80, 0.3);
            color: #4caf50;
        }
        
        .badge-favorite {
            color: #ffc107;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="<?php echo isset($enableUX) && $enableUX ? 'ux-refresh' : ''; ?>">
    <div class="dashboard-container">
        <!-- Compact Welcome Banner -->
        <div class="welcome-banner">
            <h1>Welcome to your Trailhead! 🏔️</h1>
            <p>Your adventure command center</p>
        </div>
        
        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            
            <!-- Quick Actions Bar - Horizontal -->
            <div class="quick-start-section">
                <div class="quick-start-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="quick-actions-grid">
                    <a href="trips.php" class="quick-action">
                        <div class="quick-action-icon">🗺️</div>
                        <div class="quick-action-label">Plan Trip</div>
                    </a>
                    <a href="backpacks.php" class="quick-action">
                        <div class="quick-action-icon">🎒</div>
                        <div class="quick-action-label">Build Pack</div>
                    </a>
                    <a href="gear.php" class="quick-action">
                        <div class="quick-action-icon">⛺</div>
                        <div class="quick-action-label">Add Gear</div>
                    </a>
                    <a href="#" class="quick-action" onclick="UXUtils.toast('Coming soon!', 'info'); return false;">
                        <div class="quick-action-icon">📋</div>
                        <div class="quick-action-label">Quick List</div>
                    </a>
                </div>
            </div>
            
            <!-- Trips Overview -->
            <div class="next-trip-card">
                <div class="card-header">
                    <h3 class="card-title">🏔️ Trips Overview</h3>
                    <a href="trips-list.php" class="card-link">View All →</a>
                </div>
                <?php if (!empty($recentTrips)): ?>
                    <?php $nextTrip = $recentTrips[0]; ?>
                    <div class="trip-info">
                        <div class="trip-name"><?php echo htmlspecialchars($nextTrip['title']); ?></div>
                        <div class="trip-details">
                            <span class="trip-detail">📍 <?php echo htmlspecialchars($nextTrip['location'] ?? 'No location'); ?></span>
                            <span class="trip-detail">📅 <?php echo date('M j', strtotime($nextTrip['start_date'])); ?></span>
                            <?php if (isset($nextTrip['distance']) && $nextTrip['distance'] > 0): ?>
                                <span class="trip-detail">🥾 <?php echo $nextTrip['distance']; ?> mi</span>
                            <?php endif; ?>
                        </div>
                        <?php if (strtotime($nextTrip['start_date']) > time()): ?>
                            <div class="countdown">🔥 <?php echo floor((strtotime($nextTrip['start_date']) - time()) / 86400); ?> days to go!</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No trips planned yet</p>
                        <a href="trips.php" class="pack-action-btn primary">Plan Your First Trip</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Backpacks Overview -->
            <div class="pack-status-card">
                <div class="card-header">
                    <h3 class="card-title">🎒 Pack Status</h3>
                    <a href="backpacks-list.php" class="card-link">View All →</a>
                </div>
                <div class="pack-stats">
                    <div class="pack-stat">
                        <div class="pack-stat-value"><?php echo $stats['backpacks']; ?></div>
                        <div class="pack-stat-label">Total Packs</div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-value"><?php echo $stats['gear']; ?></div>
                        <div class="pack-stat-label">Gear Items</div>
                    </div>
                </div>
                <div class="pack-actions">
                    <a href="backpacks.php" class="pack-action-btn">Check Pack</a>
                    <a href="backpacks.php?action=new" class="pack-action-btn primary">Quick Pack</a>
                </div>
            </div>
            
            <!-- Trail Stats - Horizontal Bar -->
            <div class="trail-stats-card">
                <div class="card-header">
                    <h3 class="card-title">📊 Trail Stats</h3>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">🏔️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stats['trips']; ?></div>
                            <div class="stat-label">Trips</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">🥾</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($stats['total_distance']); ?></div>
                            <div class="stat-label">Miles</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">⛰️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($stats['elevation_gain'] ?? 0); ?></div>
                            <div class="stat-label">Ft Climbed</div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stats['completed_trips']; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- My Gear Quick View -->
            <div class="my-gear-card">
                <div class="card-header">
                    <h3 class="card-title">⛺ My Gear</h3>
                    <a href="gear.php" class="card-link">Manage →</a>
                </div>
                <div class="gear-stats">
                    <div class="gear-stat">
                        <span class="gear-stat-icon">📦</span>
                        <span class="gear-stat-value"><?php echo $stats['gear']; ?></span> Total Items
                    </div>
                    <div class="gear-stat">
                        <span class="gear-stat-icon">⭐</span>
                        <span class="gear-stat-value"><?php echo $stats['favorite_gear'] ?? 0; ?></span> Favorites
                    </div>
                    <div class="gear-stat">
                        <span class="gear-stat-icon">🆕</span>
                        <span class="gear-stat-value"><?php echo $stats['new_gear'] ?? 0; ?></span> This Month
                    </div>
                </div>
                <button class="add-gear-btn" onclick="window.location.href='gear.php?action=add'">Add New Gear</button>
            </div>
            
            <!-- Achievements -->
            <div class="achievements-card">
                <div class="card-header">
                    <h3 class="card-title">🏆 Trail Achievements</h3>
                    <span class="xp-text">⭐ 0 XP</span>
                </div>
                <div class="level-info">
                    <span class="level-text">Level 1</span>
                    <span class="xp-text">0 / 100 XP</span>
                </div>
                <div class="xp-bar">
                    <div class="xp-fill" style="width: 0%"></div>
                </div>
                <div class="badges-row">
                    <div class="badge earned">
                        <div class="badge-icon">🥾</div>
                        <div class="badge-name">First Steps</div>
                    </div>
                    <div class="badge">
                        <div class="badge-icon">⛰️</div>
                        <div class="badge-name">Summit Seeker</div>
                    </div>
                    <div class="badge">
                        <div class="badge-icon">☀️</div>
                        <div class="badge-name">Trail Master</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Section -->
            <div class="recent-activity-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Activity</h2>
                </div>
                <div class="activity-grid">
                    <!-- Recent Trips -->
                    <div class="activity-card">
                        <div class="card-header">
                            <h3 class="card-title">Your Adventures</h3>
                        </div>
                        <div class="activity-list">
                            <?php if (!empty($recentTrips)): ?>
                                <?php foreach(array_slice($recentTrips, 0, 3) as $trip): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <div class="activity-name"><?php echo htmlspecialchars($trip['title']); ?></div>
                                        <div class="activity-meta">
                                            <?php if ($trip['completed'] == 1): ?>
                                                ✅ Completed
                                            <?php else: ?>
                                                📅 <?php echo date('M j', strtotime($trip['start_date'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="trips.php?id=<?php echo $trip['id']; ?>" class="activity-action">View</a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No trips yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Backpacks -->
                    <div class="activity-card">
                        <div class="card-header">
                            <h3 class="card-title">Your Packs</h3>
                        </div>
                        <div class="activity-list">
                            <?php 
                            // Get recent backpacks
                            $recentPacks = $db->query("SELECT id, name, base_weight FROM backpacks WHERE user_id = ? ORDER BY created_at DESC LIMIT 3", [$_SESSION['user_id']])->fetchAll();
                            if (!empty($recentPacks)): ?>
                                <?php foreach($recentPacks as $pack): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <div class="activity-name"><?php echo htmlspecialchars($pack['name']); ?></div>
                                        <div class="activity-meta">⚖️ <?php echo number_format($pack['base_weight'], 1); ?> lbs</div>
                                    </div>
                                    <a href="backpacks.php?id=<?php echo $pack['id']; ?>" class="activity-action">View</a>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No backpacks yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
