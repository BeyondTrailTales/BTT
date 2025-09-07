<?php
/**
 * Trip Packing API Routes
 * 
 * Handles packing list management for trips
 * 
 * @version 1.0.0
 */

use App\Services\AuthService;
use App\Models\TripPacking;

// Make sure required classes are loaded
require_once dirname(__DIR__, 2) . '/app/models/TripPacking.php';

/**
 * Main route handler for trip packing endpoints - adapted for ajax-handler pattern
 */
function handleTripPackingRoute($method, $pathParts) {
    global $db, $user_id;
    
    // Check authentication (already done in ajax-handler)
    if (!$user_id) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $tripId = (int)$pathParts[0];
    if (!$tripId) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid trip ID']);
        exit;
    }
    
    // Parse additional path from query parameters
    $subAction = $_GET['sub_action'] ?? '';
    
    try {
        switch ($method) {
            case 'GET':
                if ($subAction === 'progress') {
                    // GET /trips/{tripId}/packing-list?sub_action=progress
                    getPackingProgress($tripId);
                } else {
                    // GET /trips/{tripId}/packing-list
                    getPackingList($tripId);
                }
                break;
                
            case 'POST':
                if ($subAction === 'custom') {
                    // POST /trips/{tripId}/packing-list?sub_action=custom
                    addCustomItem($tripId);
                } elseif ($subAction === 'bulk') {
                    // POST /trips/{tripId}/packing-list?sub_action=bulk
                    bulkUpdateItems($tripId);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Invalid action for POST method']);
                    exit;
                }
                break;
                
            case 'PATCH':
            case 'PUT':
                $itemId = $_GET['item_id'] ?? null;
                if (!$itemId) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Item ID required']);
                    exit;
                }
                
                if ($subAction === 'gear') {
                    // PATCH /trips/{tripId}/packing-list?sub_action=gear&item_id={gearId}
                    updateGearPackedState($tripId, (int)$itemId);
                } elseif ($subAction === 'custom') {
                    // PATCH /trips/{tripId}/packing-list?sub_action=custom&item_id={itemId}
                    updateCustomItem($tripId, (int)$itemId);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Invalid action for PATCH/PUT method']);
                    exit;
                }
                break;
                
            case 'DELETE':
                $itemId = $_GET['item_id'] ?? null;
                if (!$itemId || $subAction !== 'custom') {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Custom item ID required']);
                    exit;
                }
                // DELETE /trips/{tripId}/packing-list?sub_action=custom&item_id={itemId}
                deleteCustomItem($tripId, (int)$itemId);
                break;
                
            default:
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit;
        }
    } catch (Exception $e) {
        error_log('Trip packing error: ' . $e->getMessage());
        error_log('Trip packing error trace: ' . $e->getTraceAsString());
        
        ob_clean();
        // Return detailed error for debugging
        echo json_encode([
            'success' => false, 
            'message' => 'Packing API error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'debug' => true
        ]);
        exit;
    }
}

/**
 * Normalize category names from backpack sections
 */
function normalizeCategory($section) {
    $mapping = [
        'main' => 'main',
        'lid' => 'lid',
        'pockets' => 'pockets',
        'side_pockets' => 'pockets',
        'external' => 'external',
        'hip_belt' => 'pockets'
    ];
    
    return $mapping[strtolower($section)] ?? 'main';
}

/**
 * Get complete packing list for a trip
 */
function getPackingList($tripId) {
    global $db, $user_id;
    
    error_log("getPackingList called with tripId: $tripId, user_id: $user_id");
    
    try {
        // First, get the trip details to find the selected backpack
        $stmt = $db->prepare(
            "SELECT t.*, b.id as backpack_id, b.name as backpack_name 
             FROM trips t 
             LEFT JOIN backpacks b ON t.backpack_id = b.id
             WHERE t.id = ? AND t.user_id = ?"
        );
        $stmt->execute([$tripId, $user_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trip) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Trip not found or access denied']);
            exit;
        }
        
        $items = [];
        $categories = ['main' => [], 'lid' => [], 'pockets' => [], 'external' => []];
        
        // If trip has a backpack, load its items
        if ($trip['backpack_id']) {
            // Get all items from the backpack
            $stmt = $db->prepare(
                "SELECT 
                    bg.gear_id,
                    bg.quantity,
                    bg.section,
                    COALESCE(bg.custom_name, 'Unknown') as name,
                    COALESCE(bg.custom_weight, 0) as weight,
                    COALESCE(bg.custom_category, 'other') as gear_category,
                    COALESCE(bg.custom_notes, '') as gear_notes
                FROM backpack_gear bg
                WHERE bg.backpack_id = ?
                ORDER BY bg.section, bg.position"
            );
            $stmt->execute([$trip['backpack_id']]);
            $backpackItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get packed states for these items
            $packedStates = [];
            if (!empty($backpackItems)) {
                $gearIds = array_filter(array_column($backpackItems, 'gear_id'));
                if (!empty($gearIds)) {
                    $placeholders = implode(',', array_fill(0, count($gearIds), '?'));
                    $params = array_merge([$tripId], $gearIds);
                    
                    $stmt = $db->prepare(
                        "SELECT gear_id, is_packed 
                         FROM trip_packed_items 
                         WHERE trip_id = ? AND gear_id IN ($placeholders)"
                    );
                    $stmt->execute($params);
                    $packedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($packedRows as $row) {
                        $packedStates[$row['gear_id']] = (bool)$row['is_packed'];
                    }
                }
            }
            
            // Process backpack items
            foreach ($backpackItems as $item) {
                $category = normalizeCategory($item['section'] ?? 'main');
                
                // Determine if this is a real gear item or a custom backpack item
                $hasValidGearId = !empty($item['gear_id']) && $item['gear_id'] > 0;
                
                if ($hasValidGearId) {
                    // This is a real gear item
                    $isPacked = $packedStates[$item['gear_id']] ?? false;
                    
                    $processedItem = [
                        'id' => 'gear-' . $item['gear_id'],
                        'gear_id' => (int)$item['gear_id'],
                        'type' => 'gear',
                        'name' => $item['name'],
                        'quantity' => (int)$item['quantity'],
                        'weight' => (float)$item['weight'],
                        'category' => $category,
                        'gear_category' => $item['gear_category'],
                        'notes' => $item['gear_notes'],
                        'is_packed' => $isPacked,
                        'is_custom' => false
                    ];
                } else {
                    // This is a custom backpack item - treat it as a custom item
                    // We'll create a unique ID based on the backpack item
                    $customId = 'bp-' . $trip['backpack_id'] . '-' . md5($item['name'] . $item['section'] . $item['quantity']);
                    
                    // Check if there's already a packed state for this custom item
                    $stmt = $db->prepare("SELECT is_packed FROM trip_packed_items WHERE trip_id = ? AND item_name = ? AND category = ? AND is_custom = 1");
                    $stmt->execute([$tripId, $item['name'], $category]);
                    $customPacked = $stmt->fetch(PDO::FETCH_ASSOC);
                    $isPacked = $customPacked ? (bool)$customPacked['is_packed'] : false;
                    
                    $processedItem = [
                        'id' => 'custom-' . $customId,
                        'custom_id' => $customId,
                        'type' => 'custom',
                        'name' => $item['name'],
                        'quantity' => (int)$item['quantity'],
                        'weight' => (float)$item['weight'],
                        'category' => $category,
                        'gear_category' => $item['gear_category'],
                        'notes' => $item['gear_notes'],
                        'is_packed' => $isPacked,
                        'is_custom' => true
                    ];
                }
                
                $items[] = $processedItem;
                $categories[$category][] = $processedItem;
            }
        }
        
        // Only get custom items that AREN'T represented by the backpack items
        // This prevents orphaned items from showing up when a backpack is selected
        if ($trip['backpack_id']) {
            // Don't load separate custom items - backpack is the source of truth
            // Any items not in the backpack shouldn't appear in the packing list
            
            // However, we can provide a migration opportunity by checking for orphaned items
            $stmt = $db->prepare(
                "SELECT COUNT(*) as orphaned_count FROM trip_packed_items 
                 WHERE trip_id = ? AND is_custom = 1"
            );
            $stmt->execute([$tripId]);
            $orphanedCheck = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($orphanedCheck['orphaned_count'] > 0) {
                error_log("Trip $tripId has {$orphanedCheck['orphaned_count']} orphaned custom items that aren't in the selected backpack");
            }
        } else {
            // No backpack selected - show custom items for the trip
            $stmt = $db->prepare(
                "SELECT * FROM trip_packed_items 
                 WHERE trip_id = ? AND is_custom = 1
                 ORDER BY category, sort_order, item_name"
            );
            $stmt->execute([$tripId]);
            $customItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customItems as $item) {
                $processedItem = [
                    'id' => 'custom-' . $item['id'],
                    'custom_id' => $item['id'],
                    'type' => 'custom',
                    'name' => $item['item_name'],
                    'quantity' => (int)$item['quantity'],
                    'category' => $item['category'],
                    'notes' => $item['notes'],
                    'is_packed' => (bool)$item['is_packed'],
                    'is_custom' => true,
                    'sort_order' => (int)$item['sort_order']
                ];
                
                $items[] = $processedItem;
                $categories[$item['category']][] = $processedItem;
            }
        }
        
        // Calculate progress
        $total = count($items);
        $packed = count(array_filter($items, function($item) {
            return $item['is_packed'];
        }));
        $percent = $total > 0 ? floor(($packed / $total) * 100) : 0;
        
        $result = [
            'trip_id' => $tripId,
            'backpack_id' => $trip['backpack_id'],
            'backpack_name' => $trip['backpack_name'],
            'items' => $items,
            'categories' => $categories,
            'summary' => [
                'total' => $total,
                'packed' => $packed,
                'percent' => $percent
            ]
        ];
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Get packing progress summary for a trip
 */
function getPackingProgress($tripId) {
    global $db, $user_id;
    
    try {
        // Verify trip ownership
        $stmt = $db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        if (!$stmt->fetch()) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Trip not found or access denied']);
            exit;
        }
        
        // Count total and packed items (both gear and custom)
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_packed = 1 THEN 1 ELSE 0 END) as packed
            FROM (
                SELECT 1, COALESCE(tpi.is_packed, 0) as is_packed
                FROM trips t
                JOIN backpacks b ON t.backpack_id = b.id
                JOIN backpack_gear bg ON b.id = bg.backpack_id
                LEFT JOIN trip_packed_items tpi ON tpi.trip_id = t.id AND tpi.gear_id = bg.gear_id
                WHERE t.id = ? AND t.user_id = ?
                
                UNION ALL
                
                SELECT 1, is_packed
                FROM trip_packed_items
                WHERE trip_id = ? AND is_custom = 1
            ) combined
        ");
        $stmt->execute([$tripId, $user_id, $tripId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total = (int)$stats['total'];
        $packed = (int)$stats['packed'];
        $percent = $total > 0 ? floor(($packed / $total) * 100) : 0;
        
        $progress = [
            'total' => $total,
            'packed' => $packed,
            'percent' => $percent
        ];
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $progress]);
        exit;
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Add a custom item to the packing list AND to the backpack's gear inventory
 * This makes the packing checklist bidirectional with the backpack
 */
function addCustomItem($tripId) {
    global $db, $user_id;
    
    try {
        $data = get_request_data();
        
        // Validate required fields
        if (empty($data['item_name'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Item name is required']);
            exit;
        }
        
        // Get trip details to find the selected backpack
        $stmt = $db->prepare("SELECT backpack_id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$trip) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Trip not found']);
            exit;
        }
        
        $category = $data['category'] ?? 'main';
        $quantity = (int)($data['quantity'] ?? 1);
        $notes = $data['notes'] ?? null;
        $itemName = $data['item_name'];
        
        $db->beginTransaction();
        
        // 1. Add the item to the trip's packing list
        $stmt = $db->prepare("
            INSERT INTO trip_packed_items (
                trip_id, item_name, category, quantity, notes, 
                is_packed, is_custom, sort_order, created_at
            ) VALUES (?, ?, ?, ?, ?, 0, 1, 
                COALESCE((SELECT MAX(sort_order) + 1 FROM trip_packed_items WHERE trip_id = ? AND category = ?), 1),
                datetime('now')
            )
        ");
        
        $stmt->execute([
            $tripId, $itemName, $category, $quantity, $notes, $tripId, $category
        ]);
        
        $packingItemId = $db->lastInsertId();
        
        // 2. If the trip has a selected backpack, also add the item to the backpack's gear inventory
        if ($trip['backpack_id']) {
            // Map category to backpack section
            $sectionMapping = [
                'main' => 'main',
                'lid' => 'lid', 
                'pockets' => 'pockets',
                'external' => 'external'
            ];
            $section = $sectionMapping[$category] ?? 'main';
            
            // Check if this exact item already exists in the backpack
            $stmt = $db->prepare("
                SELECT id FROM backpack_gear 
                WHERE backpack_id = ? AND custom_name = ? AND section = ?
            ");
            $stmt->execute([$trip['backpack_id'], $itemName, $section]);
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
                    $itemName, 
                    $notes, 
                    $quantity, 
                    $section, 
                    $position
                ]);
                
                error_log("Added custom item '$itemName' to backpack {$trip['backpack_id']} in section '$section'");
            }
        }
        
        $db->commit();
        
        $item = [
            'id' => $packingItemId,
            'trip_id' => $tripId,
            'item_name' => $itemName,
            'category' => $category,
            'quantity' => $quantity,
            'notes' => $notes,
            'is_packed' => false,
            'is_custom' => true,
            'added_to_backpack' => !empty($trip['backpack_id'])
        ];
        
        ob_clean();
        echo json_encode([
            'success' => true, 
            'data' => $item,
            'message' => !empty($trip['backpack_id']) ? 
                "Item added to packing list and backpack inventory" : 
                "Item added to packing list"
        ]);
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        throw $e;
    }
}

/**
 * Update packed state for a gear item
 */
function updateGearPackedState($tripId, $gearId) {
    try {
        if (!$gearId) {
            Response::error('Invalid gear ID', 400);
        }
        
        $data = get_request_data();
        
        if (!isset($data['is_packed'])) {
            Response::validationError(['is_packed' => 'Packed state is required']);
        }
        
        $success = TripPacking::upsertPackedState(
            $tripId,
            $gearId,
            (bool)$data['is_packed']
        );
        
        Response::success(['success' => $success]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Update a custom item
 */
function updateCustomItem($tripId, $itemId) {
    try {
        if (!$itemId) {
            Response::error('Invalid item ID', 400);
        }
        
        $data = get_request_data();
        
        if (empty($data)) {
            Response::validationError(['error' => 'No fields to update']);
        }
        
        $success = TripPacking::updateCustomItem($tripId, $itemId, $data);
        
        Response::success(['success' => $success]);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Delete a custom item
 */
function deleteCustomItem($tripId, $itemId) {
    global $db, $user_id;
    
    try {
        if (!$itemId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            exit;
        }
        
        // Verify trip ownership and that item exists
        $stmt = $db->prepare("
            SELECT tpi.id 
            FROM trip_packed_items tpi
            JOIN trips t ON tpi.trip_id = t.id 
            WHERE tpi.id = ? AND tpi.trip_id = ? AND t.user_id = ? AND tpi.is_custom = 1
        ");
        $stmt->execute([$itemId, $tripId, $user_id]);
        
        if (!$stmt->fetch()) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
            exit;
        }
        
        // Delete the custom item
        $stmt = $db->prepare("DELETE FROM trip_packed_items WHERE id = ? AND is_custom = 1");
        $success = $stmt->execute([$itemId]);
        
        ob_clean();
        echo json_encode(['success' => $success]);
        exit;
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Bulk update packed states
 */
function bulkUpdateItems($tripId) {
    global $db, $user_id;
    
    try {
        $data = get_request_data();
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Items array is required']);
            exit;
        }
        
        // Verify trip ownership
        $stmt = $db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        if (!$stmt->fetch()) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Trip not found or access denied']);
            exit;
        }
        
        $db->beginTransaction();
        
        foreach ($data['items'] as $item) {
            $type = $item['type'] ?? 'gear';
            $isPacked = (bool)($item['is_packed'] ?? false);
            
            error_log("Processing bulk update item: " . json_encode($item));
            
            if ($type === 'gear' && isset($item['gear_id']) && $item['gear_id'] > 0) {
                $gearId = $item['gear_id'];
                
                // For gear items, we use INSERT OR REPLACE to handle both new and existing packed states
                // This ensures we don't create duplicates - it will update existing records or create new ones
                // Note: item_name must be NULL for gear items per the CHECK constraint
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO trip_packed_items 
                    (trip_id, gear_id, is_packed, is_custom, item_name, user_id, created_at, updated_at)
                    VALUES (?, ?, ?, 0, NULL, ?, datetime('now'), datetime('now'))
                ");
                $stmt->execute([$tripId, $gearId, $isPacked, $user_id]);
                error_log("Updated gear item $gearId packed state to " . ($isPacked ? 'true' : 'false'));
                
            } elseif ($type === 'custom' && isset($item['id'])) {
                $customId = $item['id'];
                
                // Check if this is a regular custom item (numeric ID) or backpack custom item (string ID)
                if (is_numeric($customId)) {
                    // Regular custom trip item - update existing record
                    $stmt = $db->prepare("
                        UPDATE trip_packed_items 
                        SET is_packed = ?, updated_at = datetime('now')
                        WHERE trip_id = ? AND id = ? AND is_custom = 1
                    ");
                    $result = $stmt->execute([$isPacked, $tripId, $customId]);
                    error_log("Updated custom item $customId packed state to " . ($isPacked ? 'true' : 'false') . " (affected rows: " . $stmt->rowCount() . ")");
                } else {
                    // Custom backpack item - these are derived from backpack gear but treated as custom
                    // We DON'T want to create database records for these, as they're handled by the UI
                    // These items only exist in the context of the current trip's backpack selection
                    // The packed state is managed in memory and doesn't persist as separate custom items
                    
                    if (strpos($customId, 'bp-') === 0) {
                        // This is a backpack-derived custom item
                        // We'll handle the packed state by creating a temporary record that matches by name and category
                        $itemName = $item['name'] ?? 'Custom Item';
                        $category = $item['category'] ?? 'main';
                        
                        // Use INSERT OR IGNORE followed by UPDATE to avoid duplications
                        // First, try to insert a new record
                        $stmt = $db->prepare("
                            INSERT OR IGNORE INTO trip_packed_items 
                            (trip_id, item_name, category, quantity, is_packed, is_custom, user_id, created_at, updated_at)
                            VALUES (?, ?, ?, 1, ?, 1, ?, datetime('now'), datetime('now'))
                        ");
                        $stmt->execute([$tripId, $itemName, $category, $isPacked, $user_id]);
                        
                        // Then update the existing record if it was already there
                        $stmt = $db->prepare("
                            UPDATE trip_packed_items 
                            SET is_packed = ?, updated_at = datetime('now')
                            WHERE trip_id = ? AND item_name = ? AND category = ? AND is_custom = 1
                        ");
                        $stmt->execute([$isPacked, $tripId, $itemName, $category]);
                        error_log("Updated backpack custom item '$itemName' in category '$category' packed state to " . ($isPacked ? 'true' : 'false'));
                    }
                }
            }
        }
        
        $db->commit();
        
        ob_clean();
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        throw $e;
    }
}

/**
 * Helper to get request data
 */
function get_request_data() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON', 400);
        }
        
        return $data ?? [];
    }
    
    // Fall back to POST/PUT data
    return $_POST;
}
