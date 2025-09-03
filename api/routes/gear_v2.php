<?php
/**
 * Enhanced Gear API Routes V2
 * 
 * Manages gear with default/user separation and view preferences
 * Following Context7 best practices
 */

// Get user ID from session or default to guest
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
}

// Main route handler
function handleGearRoute($method, $path, $db) {
    $userId = getCurrentUserId();
    
    // Parse path segments
    $segments = array_filter(explode('/', $path));
    $endpoint = $segments[0] ?? '';
    $id = $segments[1] ?? null;
    
    switch ($endpoint) {
        case 'preferences':
            handlePreferencesRoute($method, $userId, $db);
            break;
            
        case 'clear-user':
            if ($method === 'DELETE') {
                clearUserGear($userId, $db);
            } else {
                Response::methodNotAllowed(['DELETE']);
            }
            break;
            
        default:
            // Main gear endpoint
            switch ($method) {
                case 'GET':
                    if ($id) {
                        getGearById($id, $userId, $db);
                    } else {
                        getAllGear($userId, $db);
                    }
                    break;
                    
                case 'POST':
                    createGearItem($userId, $db);
                    break;
                    
                case 'PUT':
                    if (!$id) {
                        Response::error('Gear ID is required for update', 400);
                    }
                    updateGearItem($id, $userId, $db);
                    break;
                    
                case 'DELETE':
                    if (!$id) {
                        Response::error('Gear ID is required for delete', 400);
                    }
                    deleteGearItem($id, $userId, $db);
                    break;
                    
                default:
                    Response::methodNotAllowed(['GET', 'POST', 'PUT', 'DELETE']);
            }
    }
}

/**
 * Handle preferences routes
 */
function handlePreferencesRoute($method, $userId, $db) {
    switch ($method) {
        case 'GET':
            getPreferences($userId, $db);
            break;
            
        case 'PATCH':
        case 'PUT':
            updatePreferences($userId, $db);
            break;
            
        default:
            Response::methodNotAllowed(['GET', 'PATCH']);
    }
}

/**
 * Get all gear items based on view mode
 */
function getAllGear($userId, $db) {
    try {
        // Get user preference for view mode
        $mode = $_GET['mode'] ?? null;
        
        if (!$mode) {
            // Get from user preferences
            $stmt = $db->prepare("SELECT view_mode FROM gear_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $pref = $stmt->fetch(PDO::FETCH_ASSOC);
            $mode = $pref ? $pref['view_mode'] : 'all';
        }
        
        // Validate mode
        if (!in_array($mode, ['all', 'custom_only', 'default_only'])) {
            $mode = 'all';
        }
        
        // Get filters
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        
        $items = [];
        
        // Get default gear if needed
        if ($mode === 'all' || $mode === 'default_only') {
            $sql = "SELECT *, 'default' as source FROM gear_defaults WHERE is_active = 1";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            if ($search) {
                $sql .= " AND (name LIKE ? OR brand LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY category, name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $defaults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($defaults as &$item) {
                $item['id'] = 'default_' . $item['id'];
                $item['editable'] = false;
            }
            
            $items = array_merge($items, $defaults);
        }
        
        // Get user gear if needed
        if ($mode === 'all' || $mode === 'custom_only') {
            $sql = "SELECT *, 'user' as source FROM gear_user WHERE user_id = ?";
            $params = [$userId];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            if ($search) {
                $sql .= " AND (name LIKE ? OR brand LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY category, name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $userGear = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($userGear as &$item) {
                $item['id'] = 'user_' . $item['id'];
                $item['editable'] = true;
            }
            
            $items = array_merge($items, $userGear);
        }
        
        // Calculate statistics
        $stats = [
            'total_items' => count($items),
            'total_weight' => array_sum(array_column($items, 'weight_g')),
            'default_count' => count(array_filter($items, fn($i) => $i['source'] === 'default')),
            'user_count' => count(array_filter($items, fn($i) => $i['source'] === 'user')),
            'view_mode' => $mode
        ];
        
        Response::success([
            'items' => $items,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch gear: ' . $e->getMessage());
    }
}

/**
 * Get single gear item by ID
 */
function getGearById($id, $userId, $db) {
    try {
        // Parse ID to determine source
        if (strpos($id, 'default_') === 0) {
            $realId = str_replace('default_', '', $id);
            $stmt = $db->prepare("SELECT *, 'default' as source FROM gear_defaults WHERE id = ? AND is_active = 1");
            $stmt->execute([$realId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $item['id'] = $id;
                $item['editable'] = false;
                Response::success($item);
            }
        } elseif (strpos($id, 'user_') === 0) {
            $realId = str_replace('user_', '', $id);
            $stmt = $db->prepare("SELECT *, 'user' as source FROM gear_user WHERE id = ? AND user_id = ?");
            $stmt->execute([$realId, $userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                $item['id'] = $id;
                $item['editable'] = true;
                Response::success($item);
            }
        }
        
        Response::notFound('Gear item not found');
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch gear item: ' . $e->getMessage());
    }
}

/**
 * Create new user gear item
 */
function createGearItem($userId, $db) {
    try {
        $data = get_request_data();
        
        // Validate required fields
        if (empty($data['name']) || empty($data['category'])) {
            Response::validationError([
                'name' => 'Name is required',
                'category' => 'Category is required'
            ]);
        }
        
        // Validate category
        $validCategories = ['shelter', 'sleep', 'cooking', 'clothing', 'footwear', 
                           'navigation', 'safety', 'hygiene', 'electronics', 'food', 
                           'water', 'first_aid', 'tools', 'other'];
        
        if (!in_array($data['category'], $validCategories)) {
            Response::validationError(['category' => 'Invalid category']);
        }
        
        // Validate weight if provided
        if (isset($data['weight_g']) && $data['weight_g'] < 0) {
            Response::validationError(['weight_g' => 'Weight cannot be negative']);
        }
        
        // Insert into gear_user table
        $sql = "INSERT INTO gear_user (
                    user_id, name, category, weight_g, unit, icon, 
                    description, brand, price, quantity_owned
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userId,
            trim($data['name']),
            $data['category'],
            $data['weight_g'] ?? null,
            $data['unit'] ?? 'g',
            $data['icon'] ?? null,
            $data['description'] ?? null,
            $data['brand'] ?? null,
            $data['price'] ?? null,
            $data['quantity_owned'] ?? 1
        ]);
        
        $newId = $db->lastInsertId();
        
        // Fetch and return the created item
        $stmt = $db->prepare("SELECT *, 'user' as source FROM gear_user WHERE id = ?");
        $stmt->execute([$newId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $item['id'] = 'user_' . $item['id'];
        $item['editable'] = true;
        
        Response::success($item, 'Gear item created successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to create gear item: ' . $e->getMessage());
    }
}

/**
 * Update existing user gear item
 */
function updateGearItem($id, $userId, $db) {
    try {
        // Only user gear can be updated
        if (strpos($id, 'user_') !== 0) {
            Response::error('Only custom gear can be updated', 403);
        }
        
        $realId = str_replace('user_', '', $id);
        
        // Check ownership
        $stmt = $db->prepare("SELECT id FROM gear_user WHERE id = ? AND user_id = ?");
        $stmt->execute([$realId, $userId]);
        
        if (!$stmt->fetch()) {
            Response::notFound('Gear item not found');
        }
        
        $data = get_request_data();
        $updates = [];
        $params = [];
        
        // Build update query
        $allowedFields = ['name', 'category', 'weight_g', 'unit', 'icon', 
                         'description', 'brand', 'price', 'quantity_owned'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            Response::error('No fields to update', 400);
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $realId;
        $params[] = $userId;
        
        $sql = "UPDATE gear_user SET " . implode(', ', $updates) . 
               " WHERE id = ? AND user_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Return updated item
        $stmt = $db->prepare("SELECT *, 'user' as source FROM gear_user WHERE id = ?");
        $stmt->execute([$realId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $item['id'] = 'user_' . $item['id'];
        $item['editable'] = true;
        
        Response::success($item, 'Gear item updated successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to update gear item: ' . $e->getMessage());
    }
}

/**
 * Delete user gear item
 */
function deleteGearItem($id, $userId, $db) {
    try {
        // Only user gear can be deleted
        if (strpos($id, 'user_') !== 0) {
            Response::error('Only custom gear can be deleted', 403);
        }
        
        $realId = str_replace('user_', '', $id);
        
        $stmt = $db->prepare("DELETE FROM gear_user WHERE id = ? AND user_id = ?");
        $stmt->execute([$realId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            Response::success(null, 'Gear item deleted successfully');
        } else {
            Response::notFound('Gear item not found');
        }
        
    } catch (Exception $e) {
        Response::serverError('Failed to delete gear item: ' . $e->getMessage());
    }
}

/**
 * Clear all user gear
 */
function clearUserGear($userId, $db) {
    try {
        $db->beginTransaction();
        
        // Count items before deletion
        $stmt = $db->prepare("SELECT COUNT(*) FROM gear_user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            $db->commit();
            Response::success(['deleted' => 0], 'No custom gear to clear');
            return;
        }
        
        // Delete all user gear
        $stmt = $db->prepare("DELETE FROM gear_user WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Log the action
        btt_log("User $userId cleared all custom gear ($count items)", 'INFO');
        
        $db->commit();
        
        Response::success(['deleted' => $count], "Cleared $count custom gear items");
        
    } catch (Exception $e) {
        $db->rollBack();
        Response::serverError('Failed to clear gear: ' . $e->getMessage());
    }
}

/**
 * Get user preferences
 */
function getPreferences($userId, $db) {
    try {
        $stmt = $db->prepare("SELECT * FROM gear_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prefs) {
            // Return default preferences
            $prefs = [
                'view_mode' => 'all',
                'show_weights_in' => 'g',
                'sort_by' => 'category',
                'sort_order' => 'asc'
            ];
        }
        
        Response::success($prefs);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch preferences: ' . $e->getMessage());
    }
}

/**
 * Update user preferences
 */
function updatePreferences($userId, $db) {
    try {
        $data = get_request_data();
        
        // Validate view_mode if provided
        if (isset($data['view_mode'])) {
            if (!in_array($data['view_mode'], ['all', 'custom_only', 'default_only'])) {
                Response::validationError(['view_mode' => 'Invalid view mode']);
            }
        }
        
        // Check if preferences exist
        $stmt = $db->prepare("SELECT id FROM gear_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing
            $updates = [];
            $params = [];
            
            $allowedFields = ['view_mode', 'show_weights_in', 'sort_by', 'sort_order'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (!empty($updates)) {
                $updates[] = "updated_at = CURRENT_TIMESTAMP";
                $params[] = $userId;
                
                $sql = "UPDATE gear_preferences SET " . implode(', ', $updates) . 
                       " WHERE user_id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            }
        } else {
            // Insert new
            $sql = "INSERT INTO gear_preferences (
                        user_id, view_mode, show_weights_in, sort_by, sort_order
                    ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $data['view_mode'] ?? 'all',
                $data['show_weights_in'] ?? 'g',
                $data['sort_by'] ?? 'category',
                $data['sort_order'] ?? 'asc'
            ]);
        }
        
        // Return updated preferences
        getPreferences($userId, $db);
        
    } catch (Exception $e) {
        Response::serverError('Failed to update preferences: ' . $e->getMessage());
    }
}
