<?php
/**
 * Debug Dashboard Database Queries
 * Check why real data isn't showing up
 */

require_once __DIR__ . '/app/bootstrap.php';
require_auth();
require_once __DIR__ . '/api/classes/Database.php';

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

echo "<h1>🔍 Dashboard Query Debug for User ID: {$user_id}</h1>";

try {
    // Test each query individually
    echo "<h2>1. Recent Trips Query</h2>";
    $recentTripsData = $db->fetchAll("
        SELECT title, distance, elevation_gain, start_date, completed, photo_path, photo_alt_text,
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
        LIMIT 3", [$user_id]);
        
    echo "<p>Found " . count($recentTripsData) . " trips</p>";
    echo "<pre>" . print_r($recentTripsData, true) . "</pre>";
    
    echo "<h2>2. Recent Backpacks Query</h2>";
    $recentBackpacks = $db->fetchAll("
        SELECT b.id, b.name, b.description, b.created_at,
               COUNT(DISTINCT bg.gear_id) as item_count,
               COALESCE(SUM(CASE WHEN g.weight_g IS NOT NULL THEN g.weight_g ELSE 0 END), 0) as total_weight_g
        FROM backpacks b
        LEFT JOIN backpack_gear bg ON b.id = bg.backpack_id
        LEFT JOIN user_gear g ON bg.gear_id = g.id AND g.deleted_at IS NULL
        WHERE b.user_id = ?
        GROUP BY b.id, b.name, b.description, b.created_at
        ORDER BY b.updated_at DESC
        LIMIT 2", [$user_id]);
        
    echo "<p>Found " . count($recentBackpacks) . " backpacks</p>";
    echo "<pre>" . print_r($recentBackpacks, true) . "</pre>";
    
    echo "<h2>3. Recent Gear Query</h2>";
    $recentGearItems = $db->fetchAll("
        SELECT name, category, weight_g, created_at
        FROM user_gear 
        WHERE user_id = ? AND deleted_at IS NULL
        ORDER BY created_at DESC 
        LIMIT 3", [$user_id]);
        
    echo "<p>Found " . count($recentGearItems) . " gear items</p>";
    echo "<pre>" . print_r($recentGearItems, true) . "</pre>";
    
    echo "<h2>4. Basic Stats Query</h2>";
    $stats = [];
    
    $trips_count = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE user_id = ?", [$user_id]);
    $stats['trips'] = $trips_count['count'] ?? 0;
    
    $gear_count = $db->fetchOne("SELECT COUNT(*) as count FROM user_gear WHERE user_id = ? AND deleted_at IS NULL", [$user_id]);
    $stats['gear'] = $gear_count['count'] ?? 0;
    
    $backpack_count = $db->fetchOne("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?", [$user_id]);
    $stats['backpacks'] = $backpack_count['count'] ?? 0;
    
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    // Check if there's data in tables at all
    echo "<h2>5. Database Table Check</h2>";
    
    $all_trips = $db->fetchAll("SELECT id, title, user_id FROM trips LIMIT 5");
    echo "<p>Sample trips (any user): " . count($all_trips) . " found</p>";
    echo "<pre>" . print_r($all_trips, true) . "</pre>";
    
    $all_backpacks = $db->fetchAll("SELECT id, name, user_id FROM backpacks LIMIT 5");
    echo "<p>Sample backpacks (any user): " . count($all_backpacks) . " found</p>";
    echo "<pre>" . print_r($all_backpacks, true) . "</pre>";
    
    $all_gear = $db->fetchAll("SELECT id, name, user_id FROM user_gear WHERE deleted_at IS NULL LIMIT 5");
    echo "<p>Sample gear (any user): " . count($all_gear) . " found</p>";
    echo "<pre>" . print_r($all_gear, true) . "</pre>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='/BTT/dashboard' style='color:#4caf50;'>← Back to Dashboard</a></p>";
?>