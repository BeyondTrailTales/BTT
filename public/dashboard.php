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
<body>
    <div class="container">
        <header>
            <h1>🌲 BeyondTrailTales</h1>
            <p class="subtitle">Your Trail Companion for Epic Adventures</p>
        </header>
        
        <nav class="nav-bar">
            <a href="dashboard.php" class="nav-link active">🏠 Dashboard</a>
            <a href="backpacks-list.php" class="nav-link">🎒 Backpacks</a>
            <a href="trips-list.php" class="nav-link">🏔️ Trips</a>
            <a href="test-status.php" class="nav-link">🔧 System</a>
        </nav>
        
        <div class="welcome-banner">
            <h2>Welcome back, Trail Explorer!</h2>
            <p>Ready for your next adventure? Check out your stats below.</p>
        </div>
        
        <div class="quick-actions">
            <a href="trips-list.php" class="action-btn">
                ➕ Plan New Trip
            </a>
            <a href="backpacks-list.php" class="action-btn">
                🎒 Manage Packs
            </a>
            <a href="test-status.php" class="action-btn">
                📊 View Reports
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎒</div>
                <div class="stat-value"><?php echo $stats['backpacks']; ?></div>
                <div class="stat-label">Backpacks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏔️</div>
                <div class="stat-value"><?php echo $stats['trips']; ?></div>
                <div class="stat-label">Total Trips</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $stats['gear']; ?></div>
                <div class="stat-label">Gear Items</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo $stats['upcoming_trips']; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['completed_trips']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🥾</div>
                <div class="stat-value"><?php echo number_format($stats['total_distance']); ?></div>
                <div class="stat-label">Miles Hiked</div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="content-card">
                <h2>📅 Recent Trips</h2>
                <?php if (empty($recentTrips)): ?>
                    <div class="empty-state">
                        <p>No trips yet. Start planning your first adventure!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentTrips as $trip): ?>
                        <div class="trip-item">
                            <h3>
                                <?php echo htmlspecialchars($trip['title']); ?>
                                <?php if ($trip['favorite'] == 1): ?>
                                    <span class="badge-favorite">⭐</span>
                                <?php endif; ?>
                            </h3>
                            <div class="trip-meta">
                                <span>📍 <?php echo htmlspecialchars($trip['location']); ?></span>
                                <span>📅 <?php echo date('M j', strtotime($trip['start_date'])); ?></span>
                                <?php if ($trip['completed'] == 1): ?>
                                    <span class="badge badge-completed">Completed</span>
                                <?php elseif (strtotime($trip['start_date']) > time()): ?>
                                    <span class="badge badge-upcoming">Upcoming</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="content-card">
                <h2>⭐ Favorite Trips</h2>
                <?php if (empty($favoriteTrips)): ?>
                    <div class="empty-state">
                        <p>Mark your favorite trips to see them here!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($favoriteTrips as $trip): ?>
                        <div class="trip-item">
                            <h3><?php echo htmlspecialchars($trip['title']); ?> ⭐</h3>
                            <div class="trip-meta">
                                <span>📍 <?php echo htmlspecialchars($trip['location']); ?></span>
                                <span>🥾 <?php echo $trip['distance']; ?> <?php echo $trip['distance_unit'] ?? 'mi'; ?></span>
                                <?php if ($trip['backpack_name']): ?>
                                    <span>🎒 <?php echo htmlspecialchars($trip['backpack_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
