<?php
/**
 * Trip Packing Model
 * 
 * Manages packing lists for trips, including items from selected backpack
 * and custom items added specifically for the trip.
 * 
 * @version 1.0.0
 */

namespace App\Models;

use App\Services\AuthService;
use Exception;
use Database;

class TripPacking {
    
    /**
     * Get complete packing list for a trip
     * 
     * @param int $tripId Trip ID
     * @return array Array with items and summary
     */
    public static function getPackingList($tripId) {
        $db = Database::getInstance();
        
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // First, get the trip details to find the selected backpack
        $trip = $db->fetchOne(
            "SELECT t.*, b.id as backpack_id, b.name as backpack_name 
             FROM trips t 
             LEFT JOIN backpacks b ON t.backpack_id = b.id
             WHERE t.id = :trip_id AND t.user_id = :user_id",
            ['trip_id' => $tripId, 'user_id' => $user['id']]
        );
        
        if (!$trip) {
            throw new Exception('Trip not found or access denied');
        }
        
        $items = [];
        $categories = ['main' => [], 'lid' => [], 'pockets' => [], 'external' => []];
        
        // If trip has a backpack, load its items
        if ($trip['backpack_id']) {
            // Get all items from the backpack
            $backpackItems = $db->fetchAll(
                "SELECT 
                    bg.gear_id,
                    bg.quantity,
                    bg.section,
                    bg.worn,
                    bg.consumable,
                    COALESCE(bg.custom_name, gi.name) as name,
                    COALESCE(bg.custom_weight, gi.weight) as weight,
                    COALESCE(bg.custom_category, gi.category) as gear_category,
                    COALESCE(bg.custom_brand, gi.brand) as brand,
                    COALESCE(bg.custom_notes, gi.notes) as gear_notes
                FROM backpack_gear bg
                LEFT JOIN gear_items gi ON bg.gear_id = gi.id
                WHERE bg.backpack_id = :backpack_id
                ORDER BY bg.section, bg.position",
                ['backpack_id' => $trip['backpack_id']]
            );
            
            // Get packed states for these items
            $packedStates = [];
            if (!empty($backpackItems)) {
                $gearIds = array_filter(array_column($backpackItems, 'gear_id'));
                if (!empty($gearIds)) {
                    $placeholders = implode(',', array_fill(0, count($gearIds), '?'));
                    $params = array_merge([$tripId], $gearIds);
                    
                    $packedRows = $db->fetchAll(
                        "SELECT gear_id, is_packed 
                         FROM trip_packed_items 
                         WHERE trip_id = ? AND gear_id IN ($placeholders)",
                        $params
                    );
                    
                    foreach ($packedRows as $row) {
                        $packedStates[$row['gear_id']] = (bool)$row['is_packed'];
                    }
                }
            }
            
            // Process backpack items
            foreach ($backpackItems as $item) {
                $category = self::normalizeCategory($item['section']);
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
                    'brand' => $item['brand'],
                    'notes' => $item['gear_notes'],
                    'worn' => (bool)$item['worn'],
                    'consumable' => (bool)$item['consumable'],
                    'is_packed' => $isPacked,
                    'is_custom' => false
                ];
                
                $items[] = $processedItem;
                $categories[$category][] = $processedItem;
            }
        }
        
        // Get custom items for this trip
        $customItems = $db->fetchAll(
            "SELECT * FROM trip_packed_items 
             WHERE trip_id = :trip_id AND is_custom = 1
             ORDER BY category, sort_order, item_name",
            ['trip_id' => $tripId]
        );
        
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
        
        return [
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
    }
    
    /**
     * Get packing progress for a trip
     * 
     * @param int $tripId Trip ID
     * @return array Progress stats
     */
    public static function getProgress($tripId) {
        $packingList = self::getPackingList($tripId);
        return $packingList['summary'];
    }
    
    /**
     * Update packed state for a gear item
     * 
     * @param int $tripId Trip ID
     * @param int $gearId Gear item ID
     * @param bool $isPacked Packed state
     * @return bool Success
     */
    public static function upsertPackedState($tripId, $gearId, $isPacked) {
        $db = Database::getInstance();
        $user = AuthService::getCurrentUser();
        
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // Verify trip ownership
        self::ensureTripOwnership($tripId, $user['id']);
        
        // Check if record exists
        $existing = $db->fetchOne(
            "SELECT id FROM trip_packed_items 
             WHERE trip_id = :trip_id AND gear_id = :gear_id",
            ['trip_id' => $tripId, 'gear_id' => $gearId]
        );
        
        if ($existing) {
            // Update existing
            return $db->query(
                "UPDATE trip_packed_items 
                 SET is_packed = :is_packed, updated_at = CURRENT_TIMESTAMP
                 WHERE trip_id = :trip_id AND gear_id = :gear_id",
                [
                    'is_packed' => $isPacked ? 1 : 0,
                    'trip_id' => $tripId,
                    'gear_id' => $gearId
                ]
            );
        } else {
            // Insert new
            return $db->query(
                "INSERT INTO trip_packed_items 
                 (trip_id, user_id, gear_id, is_custom, is_packed, created_at, updated_at)
                 VALUES (:trip_id, :user_id, :gear_id, 0, :is_packed, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [
                    'trip_id' => $tripId,
                    'user_id' => $user['id'],
                    'gear_id' => $gearId,
                    'is_packed' => $isPacked ? 1 : 0
                ]
            );
        }
    }
    
    /**
     * Add a custom item to the packing list
     * 
     * @param int $tripId Trip ID
     * @param string $name Item name
     * @param string $category Category (main, lid, pockets, external)
     * @param int $quantity Quantity
     * @param string|null $notes Notes
     * @return array Created item
     */
    public static function addCustomItem($tripId, $name, $category = 'main', $quantity = 1, $notes = null) {
        $db = Database::getInstance();
        $user = AuthService::getCurrentUser();
        
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // Validate inputs
        if (empty($name) || strlen($name) > 120) {
            throw new Exception('Item name must be between 1 and 120 characters');
        }
        
        if (!in_array($category, ['main', 'lid', 'pockets', 'external'])) {
            throw new Exception('Invalid category');
        }
        
        if ($quantity < 1 || $quantity > 999) {
            throw new Exception('Quantity must be between 1 and 999');
        }
        
        // Verify trip ownership
        self::ensureTripOwnership($tripId, $user['id']);
        
        // Insert custom item
        $db->query(
            "INSERT INTO trip_packed_items 
             (trip_id, user_id, is_custom, item_name, category, quantity, notes, is_packed, created_at, updated_at)
             VALUES (:trip_id, :user_id, 1, :item_name, :category, :quantity, :notes, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [
                'trip_id' => $tripId,
                'user_id' => $user['id'],
                'item_name' => $name,
                'category' => $category,
                'quantity' => $quantity,
                'notes' => $notes
            ]
        );
        
        $itemId = $db->lastInsertId();
        
        return [
            'id' => 'custom-' . $itemId,
            'custom_id' => $itemId,
            'type' => 'custom',
            'name' => $name,
            'category' => $category,
            'quantity' => $quantity,
            'notes' => $notes,
            'is_packed' => false,
            'is_custom' => true
        ];
    }
    
    /**
     * Update a custom item
     * 
     * @param int $tripId Trip ID
     * @param int $itemId Custom item ID
     * @param array $fields Fields to update
     * @return bool Success
     */
    public static function updateCustomItem($tripId, $itemId, $fields) {
        $db = Database::getInstance();
        $user = AuthService::getCurrentUser();
        
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // Verify ownership
        self::ensureTripOwnership($tripId, $user['id']);
        self::ensureCustomItemOwnership($itemId, $tripId);
        
        $allowedFields = ['item_name', 'category', 'quantity', 'notes', 'is_packed'];
        $updates = [];
        $params = ['id' => $itemId, 'trip_id' => $tripId];
        
        foreach ($fields as $field => $value) {
            if (in_array($field, $allowedFields)) {
                // Validate field values
                if ($field === 'item_name' && (empty($value) || strlen($value) > 120)) {
                    throw new Exception('Item name must be between 1 and 120 characters');
                }
                if ($field === 'category' && !in_array($value, ['main', 'lid', 'pockets', 'external'])) {
                    throw new Exception('Invalid category');
                }
                if ($field === 'quantity' && ($value < 1 || $value > 999)) {
                    throw new Exception('Quantity must be between 1 and 999');
                }
                if ($field === 'is_packed') {
                    $value = $value ? 1 : 0;
                }
                
                $updates[] = "$field = :$field";
                $params[$field] = $value;
            }
        }
        
        if (empty($updates)) {
            return true; // Nothing to update
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE trip_packed_items SET " . implode(', ', $updates) . 
               " WHERE id = :id AND trip_id = :trip_id AND is_custom = 1";
        
        return $db->query($sql, $params);
    }
    
    /**
     * Delete a custom item
     * 
     * @param int $tripId Trip ID
     * @param int $itemId Custom item ID
     * @return bool Success
     */
    public static function deleteCustomItem($tripId, $itemId) {
        $db = Database::getInstance();
        $user = AuthService::getCurrentUser();
        
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // Verify ownership
        self::ensureTripOwnership($tripId, $user['id']);
        self::ensureCustomItemOwnership($itemId, $tripId);
        
        return $db->delete(
            'trip_packed_items',
            'id = :id AND trip_id = :trip_id AND is_custom = 1',
            ['id' => $itemId, 'trip_id' => $tripId]
        );
    }
    
    /**
     * Bulk update packed states
     * 
     * @param int $tripId Trip ID
     * @param array $items Array of items to update
     * @return bool Success
     */
    public static function bulkSetPacked($tripId, $items) {
        $db = Database::getInstance();
        $user = AuthService::getCurrentUser();
        
        if (!$user) {
            throw new Exception('User not authenticated');
        }
        
        // Verify trip ownership
        self::ensureTripOwnership($tripId, $user['id']);
        
        $db->beginTransaction();
        
        try {
            foreach ($items as $item) {
                if (!isset($item['type']) || !isset($item['is_packed'])) {
                    continue;
                }
                
                $isPacked = $item['is_packed'] ? 1 : 0;
                
                if ($item['type'] === 'gear' && isset($item['gear_id'])) {
                    self::upsertPackedState($tripId, $item['gear_id'], $isPacked);
                } elseif ($item['type'] === 'custom' && isset($item['id'])) {
                    self::updateCustomItem($tripId, $item['id'], ['is_packed' => $isPacked]);
                }
            }
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Normalize category names from backpack sections
     * 
     * @param string $section Section name
     * @return string Normalized category
     */
    private static function normalizeCategory($section) {
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
     * Ensure user owns the trip
     * 
     * @param int $tripId Trip ID
     * @param int $userId User ID
     * @throws Exception if not authorized
     */
    private static function ensureTripOwnership($tripId, $userId) {
        $db = Database::getInstance();
        
        $trip = $db->fetchOne(
            "SELECT id FROM trips WHERE id = :trip_id AND user_id = :user_id",
            ['trip_id' => $tripId, 'user_id' => $userId]
        );
        
        if (!$trip) {
            throw new Exception('Trip not found or access denied');
        }
    }
    
    /**
     * Ensure custom item belongs to the trip
     * 
     * @param int $itemId Item ID
     * @param int $tripId Trip ID
     * @throws Exception if not found
     */
    private static function ensureCustomItemOwnership($itemId, $tripId) {
        $db = Database::getInstance();
        
        $item = $db->fetchOne(
            "SELECT id FROM trip_packed_items 
             WHERE id = :id AND trip_id = :trip_id AND is_custom = 1",
            ['id' => $itemId, 'trip_id' => $tripId]
        );
        
        if (!$item) {
            throw new Exception('Custom item not found');
        }
    }
}
