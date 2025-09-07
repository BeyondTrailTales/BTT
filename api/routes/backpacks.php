<?php
/**
 * Backpacks API Routes
 * 
 * Enhanced backpack management with sections, items, templates, and export/import
 * Now with user scoping - users only see/manage their own backpacks
 */

// Include helper functions
require_once __DIR__ . '/backpacks_helpers.php';
require_once __DIR__ . '/backpacks_items.php';
// AuthService is already loaded via bootstrap in api/config.php
use App\Services\AuthService;

function handleBackpacksRoute($method, $id) {
    // Check authentication for all routes
    if (!AuthService::isAuthenticated()) {
        Response::unauthorized('Authentication required');
    }
    // Parse sub-routes for sections and items
    $pathParts = explode('/', trim($_GET['path'] ?? '', '/'));
    $action = isset($pathParts[2]) ? $pathParts[2] : null;
    $subId = isset($pathParts[3]) ? $pathParts[3] : null;
    
    // Handle template routes
    if ($id === 'templates') {
        if ($method === 'GET') {
            getBackpackTemplates();
        } else {
            Response::methodNotAllowed();
        }
        return;
    }
    
    // Handle import route
    if ($id === 'import' && $method === 'POST') {
        importBackpack();
        return;
    }
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'export') {
                    exportBackpack($id);
                } else {
                    getBackpackById($id);
                }
            } else {
                getAllBackpacks();
            }
            break;
            
        case 'POST':
            if ($id && $action === 'duplicate') {
                duplicateBackpack($id);
            } elseif ($id && $action === 'from-template') {
                createFromTemplate($id);
            } elseif ($id && $action === 'sections') {
                addSection($id);
            } elseif ($id && $action === 'items') {
                addItemToSection($id);
            } else {
                createBackpack();
            }
            break;
            
        case 'PUT':
            if (!$id) {
                Response::error('Backpack ID is required for update', 400);
            }
            if ($action === 'sections' && $subId) {
                updateSection($id, $subId);
            } elseif ($action === 'items' && $subId) {
                updateItem($id, $subId);
            } else {
                updateBackpack($id);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                Response::error('Backpack ID is required for delete', 400);
            }
            if ($action === 'sections' && $subId) {
                deleteSection($id, $subId);
            } elseif ($action === 'items' && $subId) {
                deleteItem($id, $subId);
            } else {
                deleteBackpack($id);
            }
            break;
            
        default:
            Response::methodNotAllowed();
    }
}

/**
 * Get all backpacks for current user with trip count
 */
function getAllBackpacks() {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        $includeItems = isset($_GET['include_items']) && $_GET['include_items'] === 'true';
        
        if ($db->isSQLite()) {
            // Simplified query - just get the backpacks first
            $sql = "SELECT * FROM backpacks WHERE user_id = :user_id ORDER BY created_at DESC";
            $backpacks = $db->fetchAll($sql, ['user_id' => $user['id']]);
            
            // Add default values for missing fields to prevent issues
            foreach ($backpacks as &$backpack) {
                $backpack['total_items'] = 0;
                $backpack['total_weight_g'] = floatval($backpack['weight_empty_g'] ?? 0);
                $backpack['trip_count'] = 0;
                
                // If include_items is requested, load sections and items
                if ($includeItems) {
                    $backpack['sections'] = loadBackpackSections($backpack['id'], $user['id']);
                    
                    // Flatten items for easy access
                    $backpack['items'] = [];
                    foreach ($backpack['sections'] as $section) {
                        foreach ($section['items'] as $item) {
                            $item['section'] = $section['id'];
                            $item['section_name'] = $section['name'];
                            $backpack['items'][] = $item;
                        }
                    }
                    
                    // Update totals
                    $backpack['total_items'] = count($backpack['items']);
                    $totalWeight = $backpack['weight_empty_g'] ?? 0;
                    foreach ($backpack['items'] as $item) {
                        $totalWeight += ($item['weight_g'] ?? 0) * ($item['quantity'] ?? 1);
                    }
                    $backpack['total_weight_g'] = $totalWeight;
                }
            }
        } else {
            // JSON fallback - filter by user
            $allBackpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            $backpacks = array_filter($allBackpacks, function($pack) use ($user) {
                return isset($pack['user_id']) && $pack['user_id'] == $user['id'];
            });
            $backpacks = array_values($backpacks); // Reset array keys
        }
        
        Response::success($backpacks);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch backpacks: ' . $e->getMessage());
    }
}

/**
 * Get single backpack by ID with sections and items (only if owned by current user)
 */
function getBackpackById($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        
        if ($db->isSQLite()) {
            $sql = "
                SELECT 
                    b.*,
                    COUNT(t.id) as trip_count
                FROM backpacks b
                LEFT JOIN trips t ON b.id = t.backpack_id AND t.user_id = :user_id
                WHERE b.id = :id AND b.user_id = :user_id
                GROUP BY b.id
            ";
            
            $backpack = $db->fetchOne($sql, ['id' => $id, 'user_id' => $user['id']]);
            
            // Load sections and items from database
            if ($backpack) {
                $backpack['sections'] = loadBackpackSections($id, $user['id']);
                
                // Calculate weight totals
                $totalWeight = $backpack['weight_empty_g'] ?? 0;
                $baseWeight = $totalWeight;
                $itemCount = 0;
                
                foreach ($backpack['sections'] as &$section) {
                    foreach ($section['items'] as $item) {
                        $itemWeight = ($item['weight_g'] ?? 0) * ($item['quantity'] ?? 1);
                        $totalWeight += $itemWeight;
                        
                        // Base weight excludes consumables
                        if (!isset($item['consumable']) || !$item['consumable']) {
                            $baseWeight += $itemWeight;
                        }
                        $itemCount += ($item['quantity'] ?? 1);
                    }
                }
                
                $backpack['total_weight_g'] = $totalWeight;
                $backpack['base_weight_g'] = $baseWeight;
                $backpack['total_items'] = $itemCount;
            }
        } else {
            // JSON fallback with enhanced structure
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            $backpack = null;
            
            foreach ($backpacks as $b) {
                if ($b['id'] == $id) {
                    $backpack = $b;
                    
                    // Add trip count
                    $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
                    $backpack['trip_count'] = 0;
                    foreach ($trips as $trip) {
                        if (isset($trip['backpack_id']) && $trip['backpack_id'] == $id) {
                            $backpack['trip_count']++;
                        }
                    }
                    
                    // Ensure sections exist - if not, create default structure
                    if (!isset($backpack['sections']) || empty($backpack['sections'])) {
                        $backpack['sections'] = createDefaultSections($id);
                    }
                    
                    // Calculate weight totals
                    $totalWeight = 0;
                    $baseWeight = 0;
                    $itemCount = 0;
                    
                    foreach ($backpack['sections'] as &$section) {
                        $section['weight'] = 0;
                        $section['item_count'] = 0;
                        
                        if (isset($section['items'])) {
                            foreach ($section['items'] as &$item) {
                                $itemWeight = ($item['weight_g'] ?? 0) * ($item['quantity'] ?? 1);
                                $section['weight'] += $itemWeight;
                                $section['item_count'] += ($item['quantity'] ?? 1);
                                $totalWeight += $itemWeight;
                                
                                // Base weight excludes consumables
                                if (!isset($item['consumable']) || !$item['consumable']) {
                                    $baseWeight += $itemWeight;
                                }
                                $itemCount += ($item['quantity'] ?? 1);
                            }
                        }
                    }
                    
                    $backpack['total_weight_g'] = $totalWeight;
                    $backpack['base_weight_g'] = $baseWeight;
                    $backpack['total_items'] = $itemCount;
                    
                    break;
                }
            }
        }
        
        if (!$backpack) {
            Response::notFound('Backpack not found');
        }
        
        Response::success($backpack);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch backpack: ' . $e->getMessage());
    }
}

/**
 * Create new backpack with enhanced schema
 */
function createBackpack() {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $data = get_request_data();
        
        // Validate required fields
        if (empty($data['name'])) {
            Response::validationError(['name' => 'Name is required']);
        }
        
        $db = Database::getInstance();
        
        // Prepare backpack data with new schema
        $backpack_data = [
            'user_id' => $user['id'],  // Set the user_id
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'capacity' => isset($data['capacity']) ? intval($data['capacity']) : null, // Legacy field
            'capacity_l' => isset($data['capacity_l']) ? floatval($data['capacity_l']) : 65,
            'weight_empty_g' => isset($data['weight_empty_g']) ? floatval($data['weight_empty_g']) : 0,
            'base_weight' => isset($data['base_weight']) ? floatval($data['base_weight']) : 0,
            'type' => isset($data['type']) ? $data['type'] : 'custom',
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : json_encode([]),
            'image_url' => isset($data['image_url']) ? trim($data['image_url']) : null,
            'image_alt' => isset($data['image_alt']) ? trim($data['image_alt']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate base_weight
        if ($backpack_data['base_weight'] < 0) {
            Response::validationError(['base_weight' => 'Base weight cannot be negative']);
        }
        
        // Validate type
        $validTypes = ['day-hike', 'weekend', 'multi-day', 'thru-hike', 'ultralight', 'winter', 'custom'];
        if (!in_array($backpack_data['type'], $validTypes)) {
            Response::validationError(['type' => 'Invalid backpack type']);
        }
        
        // Insert into database
        if ($db->isSQLite()) {
            $backpack_id = $db->insert('backpacks', $backpack_data);
            
            // Save sections if provided
            if (isset($data['sections']) && is_array($data['sections'])) {
                error_log('Creating backpack - saving sections: ' . json_encode($data['sections']));
                $result = saveBackpackSections($backpack_id, $user['id'], $data['sections']);
                error_log('Sections save result: ' . ($result ? 'success' : 'failed'));
            } else {
                error_log('No sections provided in create request');
            }
            
            // Fetch the created backpack to return (but don't call getBackpackById as it sends its own response)
            $created = $db->fetchOne(
                "SELECT * FROM backpacks WHERE id = :id",
                ['id' => $backpack_id]
            );
            
            // Load sections if they were saved
            if ($created) {
                $created['sections'] = loadBackpackSections($backpack_id, $user['id']);
            }
            
            Response::success($created, 'Backpack created successfully');
        } else {
            // JSON fallback
            $backpacks = getBackpacksData();
            $backpack_data['id'] = getNextId($backpacks);
            $backpacks[] = $backpack_data;
            saveBackpacksData($backpacks);
            Response::success($backpack_data, 'Backpack created successfully');
        }
        
    } catch (Exception $e) {
        Response::serverError('Failed to create backpack: ' . $e->getMessage());
    }
}

/**
 * Update existing backpack with sections and items
 */
function updateBackpack($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        
        // Check if backpack exists and is owned by current user
        if ($db->isSQLite()) {
            $existing = $db->fetchOne(
                "SELECT * FROM backpacks WHERE id = :id AND user_id = :user_id",
                ['id' => $id, 'user_id' => $user['id']]
            );
        } else {
            // JSON fallback
            $backpacks = getBackpacksData();
            $existingIndex = -1;
            $existing = null;
            
            foreach ($backpacks as $index => $backpack) {
                if ($backpack['id'] == $id) {
                    $existing = $backpack;
                    $existingIndex = $index;
                    break;
                }
            }
        }
        
        if (!$existing) {
            Response::notFound('Backpack not found or access denied');
        }
        
        $data = get_request_data();
        
        // Update basic fields
        if (isset($data['name'])) {
            $existing['name'] = trim($data['name']);
            if (empty($existing['name'])) {
                Response::validationError(['name' => 'Name cannot be empty']);
            }
        }
        
        if (isset($data['description'])) {
            $existing['description'] = trim($data['description']);
        }
        
        if (isset($data['capacity_l'])) {
            $existing['capacity_l'] = floatval($data['capacity_l']);
        }
        
        if (isset($data['weight_empty_g'])) {
            $existing['weight_empty_g'] = floatval($data['weight_empty_g']);
        }
        
        if (isset($data['base_weight'])) {
            $existing['base_weight'] = floatval($data['base_weight']);
            if ($existing['base_weight'] < 0) {
                Response::validationError(['base_weight' => 'Base weight cannot be negative']);
            }
        }
        
        if (isset($data['type'])) {
            $validTypes = ['day-hike', 'weekend', 'multi-day', 'thru-hike', 'ultralight', 'winter', 'custom'];
            if (!in_array($data['type'], $validTypes)) {
                Response::validationError(['type' => 'Invalid backpack type']);
            }
            $existing['type'] = $data['type'];
        }
        
        if (isset($data['tags'])) {
            $existing['tags'] = $data['tags'];
        }
        
        // Save sections if provided
        $sectionsToSave = null;
        if (isset($data['sections'])) {
            $sectionsToSave = [];
            
            foreach ($data['sections'] as $section) {
                // Validate section structure
                if (!isset($section['name']) || empty(trim($section['name']))) {
                    Response::validationError(['sections' => 'Each section must have a name']);
                }
                
                $validSection = [
                    'id' => isset($section['id']) ? $section['id'] : 'section-' . uniqid(),
                    'name' => trim($section['name']),
                    'order' => isset($section['order']) ? intval($section['order']) : count($sectionsToSave),
                    'items' => []
                ];
                
                // Process items in this section
                if (isset($section['items']) && is_array($section['items'])) {
                    foreach ($section['items'] as $item) {
                        // Validate item structure
                        if (!isset($item['name']) || empty(trim($item['name']))) {
                            continue; // Skip invalid items
                        }
                        
                        $validItem = [
                            'gear_id' => isset($item['gear_id']) ? $item['gear_id'] : null,
                            'name' => trim($item['name']),
                            'weight_g' => isset($item['weight_g']) ? floatval($item['weight_g']) : 0,
                            'quantity' => isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1,
                            'notes' => isset($item['notes']) ? trim($item['notes']) : '',
                            'category' => isset($item['category']) ? $item['category'] : 'other',
                            'brand' => isset($item['brand']) ? trim($item['brand']) : '',
                            'price' => isset($item['price']) ? floatval($item['price']) : 0
                        ];
                        
                        $validSection['items'][] = $validItem;
                    }
                }
                
                $sectionsToSave[] = $validSection;
            }
        }
        
        // Calculate totals
        $totalWeight = isset($existing['weight_empty_g']) ? $existing['weight_empty_g'] : 0;
        $baseWeight = $totalWeight;
        $itemCount = 0;
        
        if (isset($existing['sections'])) {
            foreach ($existing['sections'] as &$section) {
                $section['weight'] = 0;
                $section['item_count'] = 0;
                
                if (isset($section['items'])) {
                    foreach ($section['items'] as $item) {
                        // Use override weight if available
                        $itemWeight = $item['weight_g'];
                        if (isset($item['overrides']) && isset($item['overrides']['weight_g'])) {
                            $itemWeight = $item['overrides']['weight_g'];
                        }
                        
                        $totalItemWeight = $itemWeight * ($item['quantity'] ?? 1);
                        
                        // Don't count worn items in pack weight
                        if (!isset($item['worn']) || !$item['worn']) {
                            $section['weight'] += $totalItemWeight;
                            $totalWeight += $totalItemWeight;
                            
                            // Base weight excludes consumables
                            if (!isset($item['consumable']) || !$item['consumable']) {
                                $baseWeight += $totalItemWeight;
                            }
                        }
                        
                        $section['item_count'] += ($item['quantity'] ?? 1);
                        $itemCount += ($item['quantity'] ?? 1);
                    }
                }
            }
        }
        
        $existing['total_weight_g'] = $totalWeight;
        $existing['base_weight_g'] = $baseWeight;
        $existing['total_items'] = $itemCount;
        $existing['updated_at'] = date('Y-m-d H:i:s');
        
        // Save updated backpack
        if ($db->isSQLite()) {
            // Update in database
            $updateData = [
                'name' => $existing['name'],
                'description' => $existing['description'] ?? null,
                'capacity_l' => $existing['capacity_l'] ?? 65,
                'weight_empty_g' => $existing['weight_empty_g'] ?? 0,
                'base_weight' => $existing['base_weight'] ?? 0,
                'type' => $existing['type'] ?? 'custom',
                'tags' => isset($existing['tags']) ? json_encode($existing['tags']) : json_encode([]),
                'updated_at' => $existing['updated_at']
            ];
            
            $db->update('backpacks', $updateData, 'id = :id AND user_id = :user_id', [
                'id' => $id,
                'user_id' => $user['id']
            ]);
            
            // Save sections to database if provided
            if ($sectionsToSave !== null) {
                error_log('Updating backpack ' . $id . ' - saving sections: ' . json_encode($sectionsToSave));
                $result = saveBackpackSections($id, $user['id'], $sectionsToSave);
                error_log('Sections update result: ' . ($result ? 'success' : 'failed'));
            } else {
                error_log('No sections to save in update request for backpack ' . $id);
            }
            
            // Return the updated backpack with sections loaded
            $updated = $db->fetchOne(
                "SELECT * FROM backpacks WHERE id = :id AND user_id = :user_id",
                ['id' => $id, 'user_id' => $user['id']]
            );
            
            if ($updated) {
                $updated['sections'] = loadBackpackSections($id, $user['id']);
            }
            
            Response::success($updated, 'Backpack updated successfully');
        } else {
            // JSON fallback
            $backpacks[$existingIndex] = $existing;
            saveBackpacksData($backpacks);
            Response::success($existing, 'Backpack updated successfully');
        }
        
    } catch (Exception $e) {
        Response::serverError('Failed to update backpack: ' . $e->getMessage());
    }
}

/**
 * Delete backpack (only if owned by current user)
 */
function deleteBackpack($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        $force = isset($_GET['force']) && $_GET['force'] === 'true';
        
        // Check if backpack exists and is owned by current user
        if ($db->isSQLite()) {
            $existing = $db->fetchOne(
                "SELECT * FROM backpacks WHERE id = :id AND user_id = :user_id",
                ['id' => $id, 'user_id' => $user['id']]
            );
        } else {
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            $existing = null;
            foreach ($backpacks as $backpack) {
                if ($backpack['id'] == $id) {
                    $existing = $backpack;
                    break;
                }
            }
        }
        
        if (!$existing) {
            Response::notFound('Backpack not found');
        }
        
        // Check if backpack is used by any trips
        $trip_count = 0;
        if ($db->isSQLite()) {
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM trips WHERE backpack_id = :id", ['id' => $id]);
            $trip_count = $result['count'];
        } else {
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            foreach ($trips as $trip) {
                if (isset($trip['backpack_id']) && $trip['backpack_id'] == $id) {
                    $trip_count++;
                }
            }
        }
        
        if ($trip_count > 0 && !$force) {
            Response::conflict(
                "Cannot delete backpack. It is used by $trip_count trip(s). " .
                "Use ?force=true to delete anyway (trips will be unlinked)."
            );
        }
        
        // If forcing, unlink trips first
        if ($trip_count > 0 && $force) {
            if ($db->isSQLite()) {
                $db->query("UPDATE trips SET backpack_id = NULL WHERE backpack_id = :id", ['id' => $id]);
            } else {
                $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
                foreach ($trips as &$trip) {
                    if (isset($trip['backpack_id']) && $trip['backpack_id'] == $id) {
                        $trip['backpack_id'] = null;
                    }
                }
                file_put_contents(BTT_JSON_PATH . '/trips.json', json_encode($trips, JSON_PRETTY_PRINT), LOCK_EX);
            }
        }
        
        // Delete backpack - handle both SQLite and JSON
        if ($db->isSQLite()) {
            $db->delete('backpacks', 'id = :id', ['id' => $id]);
        } else {
            // For JSON storage, manually remove the backpack
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            $filtered = [];
            foreach ($backpacks as $backpack) {
                if ($backpack['id'] != $id) {
                    $filtered[] = $backpack;
                }
            }
            file_put_contents(BTT_JSON_PATH . '/backpacks.json', json_encode($filtered, JSON_PRETTY_PRINT), LOCK_EX);
        }
        
        Response::success(null, 'Backpack deleted successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to delete backpack: ' . $e->getMessage());
    }
}
