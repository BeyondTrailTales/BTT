<?php
/**
 * Backpack Items Management Functions
 * Handles saving and loading gear items for backpacks
 */

/**
 * Save backpack sections and items to database
 * 
 * @param int $backpackId The backpack ID
 * @param int $userId The user ID (for verification)
 * @param array $sections Array of sections with items
 */
function saveBackpackSections($backpackId, $userId, $sections) {
    $db = Database::getInstance();
    
    error_log("saveBackpackSections called for backpack $backpackId, user $userId");
    error_log("Sections received: " . json_encode($sections));
    
    if (!$db->isSQLite()) {
        // For JSON storage, sections are stored inline
        return true;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // First, clear existing items for this backpack
        $deleted = $db->delete('backpack_gear', 'backpack_id = :backpack_id', ['backpack_id' => $backpackId]);
        error_log("Deleted $deleted existing items for backpack $backpackId");
        
        // Now insert each item from each section
        if (!empty($sections)) {
            foreach ($sections as $section) {
                $sectionId = $section['id'] ?? 'main';
                
                if (isset($section['items']) && is_array($section['items'])) {
                    error_log("Section $sectionId has " . count($section['items']) . " items");
                    foreach ($section['items'] as $itemIndex => $item) {
                        error_log("Processing item $itemIndex: " . json_encode($item));
                        // Prepare backpack_gear data
                        $backpackGearData = [
                            'backpack_id' => $backpackId,
                            'quantity' => $item['quantity'] ?? 1,
                            'section' => $sectionId,
                            'worn' => isset($item['worn']) ? (int)$item['worn'] : 0,
                            'consumable' => isset($item['consumable']) ? (int)$item['consumable'] : 0,
                            'position' => isset($item['position']) ? $item['position'] : 0
                        ];
                        
                        // Check if this references an existing gear item or is custom
                        if (isset($item['gear_id']) && $item['gear_id']) {
                            // Reference to existing gear item (system or user's)
                            $backpackGearData['gear_id'] = $item['gear_id'];
                            
                            // Custom fields are null when using gear_id
                            $backpackGearData['custom_name'] = null;
                            $backpackGearData['custom_weight'] = null;
                            $backpackGearData['custom_category'] = null;
                            $backpackGearData['custom_brand'] = null;
                            $backpackGearData['custom_notes'] = null;
                            $backpackGearData['custom_price'] = null;
                        } else {
                            // Custom item - store directly in backpack_gear
                            $backpackGearData['gear_id'] = null;
                            $backpackGearData['custom_name'] = $item['name'] ?? 'Unnamed Item';
                            $backpackGearData['custom_weight'] = $item['weight_g'] ?? 0;
                            $backpackGearData['custom_category'] = $item['category'] ?? 'other';
                            $backpackGearData['custom_brand'] = $item['brand'] ?? '';
                            $backpackGearData['custom_notes'] = $item['notes'] ?? '';
                            $backpackGearData['custom_price'] = $item['price'] ?? 0;
                        }
                        
                        // Insert into backpack_gear
                        $sql = "INSERT INTO backpack_gear 
                                (backpack_id, gear_id, custom_name, custom_weight, custom_category, 
                                 custom_brand, custom_notes, custom_price, quantity, section, 
                                 worn, consumable, position) 
                                VALUES 
                                (:backpack_id, :gear_id, :custom_name, :custom_weight, :custom_category,
                                 :custom_brand, :custom_notes, :custom_price, :quantity, :section,
                                 :worn, :consumable, :position)";
                        $db->query($sql, $backpackGearData);
                    }
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // Count saved items for verification
        $savedCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM backpack_gear WHERE backpack_id = :backpack_id",
            ['backpack_id' => $backpackId]
        );
        error_log("Successfully saved {$savedCount['count']} items for backpack $backpackId");
        
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $db->rollback();
        error_log("Error saving backpack sections: " . $e->getMessage());
        return false;
    }
}

/**
 * Load backpack sections and items from database
 * 
 * @param int $backpackId The backpack ID
 * @param int $userId The user ID (for verification)
 * @return array Array of sections with items
 */
function loadBackpackSections($backpackId, $userId) {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        // For JSON storage, return default sections
        return createDefaultSections($backpackId);
    }
    
    try {
        // Get all gear items for this backpack
        $sql = "
            SELECT 
                bg.id,
                bg.section,
                bg.quantity,
                bg.gear_id,
                bg.custom_name,
                bg.custom_weight,
                bg.custom_category,
                bg.custom_brand,
                bg.custom_notes,
                bg.custom_price,
                bg.worn,
                bg.consumable,
                bg.position,
                COALESCE(bg.custom_name, gi.name) as name,
                COALESCE(bg.custom_weight, gi.weight) as weight_g,
                COALESCE(bg.custom_category, gi.category) as category,
                COALESCE(bg.custom_brand, gi.brand) as brand,
                COALESCE(bg.custom_notes, gi.notes) as notes,
                COALESCE(bg.custom_price, gi.price) as price
            FROM backpack_gear bg
            LEFT JOIN gear_items gi ON bg.gear_id = gi.id
            WHERE bg.backpack_id = :backpack_id
            ORDER BY bg.section, bg.position, name
        ";
        
        $items = $db->fetchAll($sql, ['backpack_id' => $backpackId]);
        
        // Group items by section
        $sectionItems = [];
        foreach ($items as $item) {
            $section = $item['section'] ?? 'main';
            if (!isset($sectionItems[$section])) {
                $sectionItems[$section] = [];
            }
            
            $sectionItems[$section][] = [
                'id' => 'item-' . $item['id'],
                'gear_id' => $item['gear_id'],
                'name' => $item['name'],
                'weight_g' => floatval($item['weight_g']),
                'quantity' => intval($item['quantity']),
                'category' => $item['category'],
                'brand' => $item['brand'] ?? '',
                'notes' => $item['notes'] ?? '',
                'price' => floatval($item['price'] ?? 0),
                'worn' => (bool)$item['worn'],
                'consumable' => (bool)$item['consumable'],
                'position' => intval($item['position'])
            ];
        }
        
        // Create sections structure
        $sections = [];
        
        // Define default sections
        $defaultSections = [
            'main' => ['name' => 'Main Compartment', 'order' => 0, 'color' => '#10b981'],
            'lid' => ['name' => 'Top Lid', 'order' => 1, 'color' => '#3b82f6'],
            'pockets' => ['name' => 'Side Pockets', 'order' => 2, 'color' => '#8b5cf6'],
            'external' => ['name' => 'External', 'order' => 3, 'color' => '#f59e0b']
        ];
        
        // Build sections with items
        foreach ($defaultSections as $sectionId => $sectionInfo) {
            $sections[] = [
                'id' => $sectionId,
                'name' => $sectionInfo['name'],
                'order' => $sectionInfo['order'],
                'color' => $sectionInfo['color'],
                'items' => $sectionItems[$sectionId] ?? [],
                'weight' => 0,  // Will be calculated
                'item_count' => count($sectionItems[$sectionId] ?? [])
            ];
        }
        
        // Add any custom sections not in defaults
        foreach ($sectionItems as $sectionId => $items) {
            if (!isset($defaultSections[$sectionId])) {
                $sections[] = [
                    'id' => $sectionId,
                    'name' => ucfirst(str_replace('-', ' ', $sectionId)),
                    'order' => count($sections),
                    'color' => '#6b7280',
                    'items' => $items,
                    'weight' => 0,
                    'item_count' => count($items)
                ];
            }
        }
        
        // Calculate section weights
        foreach ($sections as &$section) {
            $sectionWeight = 0;
            foreach ($section['items'] as $item) {
                $sectionWeight += ($item['weight_g'] ?? 0) * ($item['quantity'] ?? 1);
            }
            $section['weight'] = $sectionWeight;
        }
        
        return $sections;
        
    } catch (Exception $e) {
        error_log("Error loading backpack sections: " . $e->getMessage());
        // Return default sections on error
        return createDefaultSections($backpackId);
    }
}

/**
 * Add or update a single item in a backpack section
 */
function addItemToBackpack($backpackId, $userId, $sectionId, $itemData) {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        return false;
    }
    
    try {
        // Verify backpack ownership
        $backpack = $db->fetchOne(
            "SELECT id FROM backpacks WHERE id = :id AND user_id = :user_id",
            ['id' => $backpackId, 'user_id' => $userId]
        );
        
        if (!$backpack) {
            return false;
        }
        
        // Handle gear ID
        $gearId = null;
        if (isset($itemData['gear_id'])) {
            $gearId = $itemData['gear_id'];
        } else {
            // Create new gear item
            $gearData = [
                'name' => $itemData['name'] ?? 'Unnamed Item',
                'weight' => $itemData['weight_g'] ?? 0,
                'category' => $itemData['category'] ?? 'other',
                'brand' => $itemData['brand'] ?? '',
                'notes' => $itemData['notes'] ?? '',
                'price' => $itemData['price'] ?? 0
            ];
            $gearId = $db->insert('gear_items', $gearData);
        }
        
        // Insert or update in backpack_gear
        $sql = "INSERT OR REPLACE INTO backpack_gear (backpack_id, gear_id, quantity, section) 
                VALUES (:backpack_id, :gear_id, :quantity, :section)";
        
        $db->query($sql, [
            'backpack_id' => $backpackId,
            'gear_id' => $gearId,
            'quantity' => $itemData['quantity'] ?? 1,
            'section' => $sectionId
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error adding item to backpack: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove an item from a backpack
 */
function removeItemFromBackpack($backpackId, $userId, $gearId) {
    $db = Database::getInstance();
    
    if (!$db->isSQLite()) {
        return false;
    }
    
    try {
        // Verify backpack ownership
        $backpack = $db->fetchOne(
            "SELECT id FROM backpacks WHERE id = :id AND user_id = :user_id",
            ['id' => $backpackId, 'user_id' => $userId]
        );
        
        if (!$backpack) {
            return false;
        }
        
        // Remove from backpack_gear
        $db->delete('backpack_gear', 'backpack_id = :backpack_id AND gear_id = :gear_id', [
            'backpack_id' => $backpackId,
            'gear_id' => $gearId
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error removing item from backpack: " . $e->getMessage());
        return false;
    }
}
