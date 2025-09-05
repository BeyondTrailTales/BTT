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
                $isPacked = $packedStates[$item['gear_id']] ?? false;
                
                $processedItem = [
                    'id' => 'gear-' . $item['gear_id'],
                    'gear_id' => $item['gear_id'],
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
                
                $items[] = $processedItem;
                $categories[$category][] = $processedItem;
            }
        }
        
        // Get custom items for this trip
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
 * Add a custom item to the packing list
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
        
        // Insert custom item directly into the database
        $stmt = $db->prepare("
            INSERT INTO trip_packed_items (
                trip_id, item_name, category, quantity, notes, 
                is_packed, is_custom, sort_order, created_at
            ) VALUES (?, ?, ?, ?, ?, 0, 1, 
                COALESCE((SELECT MAX(sort_order) + 1 FROM trip_packed_items WHERE trip_id = ? AND category = ?), 1),
                datetime('now')
            )
        ");
        
        $category = $data['category'] ?? 'main';
        $quantity = (int)($data['quantity'] ?? 1);
        $notes = $data['notes'] ?? null;
        
        $stmt->execute([
            $tripId, $data['item_name'], $category, $quantity, $notes, $tripId, $category
        ]);
        
        $itemId = $db->lastInsertId();
        
        $item = [
            'id' => $itemId,
            'trip_id' => $tripId,
            'item_name' => $data['item_name'],
            'category' => $category,
            'quantity' => $quantity,
            'notes' => $notes,
            'is_packed' => false,
            'is_custom' => true
        ];
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $item]);
        exit;
    } catch (Exception $e) {
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
            
            if ($type === 'gear' && isset($item['gear_id'])) {
                // Update or insert gear item packed state
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO trip_packed_items 
                    (trip_id, gear_id, is_packed, is_custom, updated_at)
                    VALUES (?, ?, ?, 0, datetime('now'))
                ");
                $stmt->execute([$tripId, $item['gear_id'], $isPacked]);
                
            } elseif ($type === 'custom' && isset($item['id'])) {
                // Update custom item packed state
                $stmt = $db->prepare("
                    UPDATE trip_packed_items 
                    SET is_packed = ?, updated_at = datetime('now')
                    WHERE trip_id = ? AND id = ? AND is_custom = 1
                ");
                $stmt->execute([$isPacked, $tripId, $item['id']]);
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
