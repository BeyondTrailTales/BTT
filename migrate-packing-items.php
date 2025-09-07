<?php
/**
 * Migrate orphaned packing list items to backpack inventory
 * This fixes trips that have custom items in the packing list but not in the backpack
 */

require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Initialize database connection
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die('User not authenticated');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $tripId = (int)($data['trip_id'] ?? 0);
        
        if (!$tripId) {
            throw new Exception('Trip ID is required');
        }
        
        // Get trip details
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trip) {
            throw new Exception('Trip not found or access denied');
        }
        
        if (!$trip['backpack_id']) {
            throw new Exception('Trip must have a selected backpack to migrate items');
        }
        
        $db->beginTransaction();
        
        // Find orphaned custom items for this trip
        $stmt = $db->prepare("
            SELECT * FROM trip_packed_items 
            WHERE trip_id = ? AND is_custom = 1
            ORDER BY category, item_name
        ");
        $stmt->execute([$tripId]);
        $orphanedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $migratedCount = 0;
        $skippedCount = 0;
        
        foreach ($orphanedItems as $item) {
            // Map category to backpack section
            $sectionMapping = [
                'main' => 'main',
                'lid' => 'lid', 
                'pockets' => 'pockets',
                'external' => 'external'
            ];
            $section = $sectionMapping[$item['category']] ?? 'main';
            
            // Check if this exact item already exists in the backpack
            $stmt = $db->prepare("
                SELECT id FROM backpack_gear 
                WHERE backpack_id = ? AND custom_name = ? AND section = ?
            ");
            $stmt->execute([$trip['backpack_id'], $item['item_name'], $section]);
            $existingGear = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingGear) {
                // Get the next position in this section
                $stmt = $db->prepare("
                    SELECT COALESCE(MAX(position), 0) + 1 as next_position 
                    FROM backpack_gear 
                    WHERE backpack_id = ? AND section = ?
                ");
                $stmt->execute([$trip['backpack_id'], $section]);
                $positionResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $position = $positionResult['next_position'];
                
                // Add the custom item to the backpack's gear inventory
                $stmt = $db->prepare("
                    INSERT INTO backpack_gear (
                        backpack_id, gear_id, custom_name, custom_category, 
                        custom_weight, custom_notes, quantity, section, position, created_at
                    ) VALUES (?, NULL, ?, 'other', 0, ?, ?, ?, ?, datetime('now'))
                ");
                
                $stmt->execute([
                    $trip['backpack_id'], 
                    $item['item_name'], 
                    $item['notes'], 
                    $item['quantity'], 
                    $section, 
                    $position
                ]);
                
                $migratedCount++;
            } else {
                $skippedCount++;
            }
        }
        
        // Remove the orphaned items from trip_packed_items since they're now in the backpack
        if ($migratedCount > 0) {
            $stmt = $db->prepare("DELETE FROM trip_packed_items WHERE trip_id = ? AND is_custom = 1");
            $stmt->execute([$tripId]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'migrated' => $migratedCount,
            'skipped' => $skippedCount,
            'message' => $migratedCount > 0 ? 
                "Migrated $migratedCount orphaned items to backpack inventory" :
                "No items needed migration"
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        error_log('Migration error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}

// HTML interface for migration
?>
<!DOCTYPE html>
<html>
<head>
    <title>Packing List Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .trip { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .button { background: #007cba; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .button:hover { background: #005a87; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🎒 Packing List Migration Tool</h1>
    <p>This tool fixes trips that have orphaned items in their packing lists that aren't in the selected backpack.</p>
    
    <div id="results"></div>
    
    <h2>Your Trips with Potential Issues</h2>
    
    <?php
    // Find trips with orphaned packing items
    $stmt = $db->prepare("
        SELECT DISTINCT t.id, t.title, t.backpack_id, b.name as backpack_name,
               COUNT(tpi.id) as orphaned_items
        FROM trips t
        LEFT JOIN backpacks b ON t.backpack_id = b.id
        LEFT JOIN trip_packed_items tpi ON t.id = tpi.trip_id AND tpi.is_custom = 1
        WHERE t.user_id = ? AND t.backpack_id IS NOT NULL
        GROUP BY t.id
        HAVING orphaned_items > 0
        ORDER BY t.title
    ");
    $stmt->execute([$user_id]);
    $problematicTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problematicTrips)): ?>
        <p><strong>✅ Great! No trips found with orphaned packing list items.</strong></p>
    <?php else: ?>
        <p>Found <?= count($problematicTrips) ?> trips with orphaned items that need migration:</p>
        
        <?php foreach ($problematicTrips as $trip): ?>
            <div class="trip">
                <h3><?= htmlspecialchars($trip['title']) ?></h3>
                <p>
                    <strong>Backpack:</strong> <?= htmlspecialchars($trip['backpack_name']) ?><br>
                    <strong>Orphaned Items:</strong> <?= $trip['orphaned_items'] ?>
                </p>
                <button class="button" onclick="migrateTrip(<?= $trip['id'] ?>)">
                    Migrate Items to Backpack
                </button>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
            <strong>⚠️ What this does:</strong>
            <ul>
                <li>Moves orphaned packing list items into your backpack's gear inventory</li>
                <li>Ensures packing lists only show items that are actually in the selected backpack</li>
                <li>Makes the packing system consistent and prevents duplicate/phantom items</li>
                <li>Preserves your item data - nothing gets lost</li>
            </ul>
        </div>
    <?php endif; ?>
    
    <script>
        async function migrateTrip(tripId) {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Migrating...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ trip_id: tripId })
                });
                
                const result = await response.json();
                
                const resultsDiv = document.getElementById('results');
                if (result.success) {
                    resultsDiv.innerHTML = `<div class="success">
                        <strong>Success!</strong> ${result.message}
                        <br>Migrated: ${result.migrated} items, Skipped: ${result.skipped} items
                    </div>` + resultsDiv.innerHTML;
                    
                    // Remove the trip from the list
                    button.closest('.trip').style.display = 'none';
                } else {
                    resultsDiv.innerHTML = `<div class="error">
                        <strong>Error:</strong> ${result.message}
                    </div>` + resultsDiv.innerHTML;
                }
            } catch (error) {
                const resultsDiv = document.getElementById('results');
                resultsDiv.innerHTML = `<div class="error">
                    <strong>Error:</strong> ${error.message}
                </div>` + resultsDiv.innerHTML;
            }
            
            button.disabled = false;
            button.textContent = 'Migrate Items to Backpack';
        }
    </script>
</body>
</html>