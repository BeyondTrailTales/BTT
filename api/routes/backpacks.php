<?php
/**
 * Backpacks API Routes
 * 
 * Enhanced backpack management with sections, items, templates, and export/import
 */

// Include helper functions
require_once __DIR__ . '/backpacks_helpers.php';

function handleBackpacksRoute($method, $id) {
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
 * Get all backpacks with trip count
 */
function getAllBackpacks() {
    try {
        $db = Database::getInstance();
        
        if ($db->isSQLite()) {
            $sql = "
                SELECT 
                    b.*,
                    COUNT(t.id) as trip_count
                FROM backpacks b
                LEFT JOIN trips t ON b.id = t.backpack_id
                GROUP BY b.id
                ORDER BY b.created_at DESC
            ";
            
            $backpacks = $db->fetchAll($sql);
        } else {
            // JSON fallback
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            
            // Add trip count
            foreach ($backpacks as &$backpack) {
                $backpack['trip_count'] = 0;
                foreach ($trips as $trip) {
                    if (isset($trip['backpack_id']) && $trip['backpack_id'] == $backpack['id']) {
                        $backpack['trip_count']++;
                    }
                }
            }
        }
        
        Response::success($backpacks);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch backpacks: ' . $e->getMessage());
    }
}

/**
 * Get single backpack by ID with sections and items
 */
function getBackpackById($id) {
    try {
        $db = Database::getInstance();
        
        if ($db->isSQLite()) {
            $sql = "
                SELECT 
                    b.*,
                    COUNT(t.id) as trip_count
                FROM backpacks b
                LEFT JOIN trips t ON b.id = t.backpack_id
                WHERE b.id = :id
                GROUP BY b.id
            ";
            
            $backpack = $db->fetchOne($sql, ['id' => $id]);
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
        $data = get_request_data();
        
        // Validate required fields
        if (empty($data['name'])) {
            Response::validationError(['name' => 'Name is required']);
        }
        
        // Get next ID
        $backpacks = getBackpacksData();
        $nextId = getNextId($backpacks);
        
        // Prepare backpack data with new schema
        $backpack_data = [
            'id' => $nextId,
            'name' => trim($data['name']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'capacity_l' => isset($data['capacity_l']) ? floatval($data['capacity_l']) : 65,
            'weight_empty_g' => isset($data['weight_empty_g']) ? floatval($data['weight_empty_g']) : 0,
            'base_weight' => isset($data['base_weight']) ? floatval($data['base_weight']) : 0, // Legacy field
            'type' => isset($data['type']) ? $data['type'] : 'custom',
            'tags' => isset($data['tags']) ? $data['tags'] : [],
            'sections' => createDefaultSections($nextId),
            'linked_trip_id' => null,
            'template_id' => isset($data['template_id']) ? $data['template_id'] : null,
            'version' => '1.0.0',
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
        
        // Add to backpacks array and save
        $backpacks[] = $backpack_data;
        saveBackpacksData($backpacks);
        
        Response::success($backpack_data, 'Backpack created successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to create backpack: ' . $e->getMessage());
    }
}

/**
 * Update existing backpack with sections and items
 */
function updateBackpack($id) {
    try {
        // Load existing backpacks
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
        
        if (!$existing) {
            Response::notFound('Backpack not found');
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
        
        // Update sections and items if provided
        if (isset($data['sections'])) {
            $existing['sections'] = [];
            
            foreach ($data['sections'] as $section) {
                // Validate section structure
                if (!isset($section['name']) || empty(trim($section['name']))) {
                    Response::validationError(['sections' => 'Each section must have a name']);
                }
                
                $validSection = [
                    'id' => isset($section['id']) ? $section['id'] : 'section-' . uniqid(),
                    'name' => trim($section['name']),
                    'order' => isset($section['order']) ? intval($section['order']) : count($existing['sections']),
                    'capacity_percentage' => isset($section['capacity_percentage']) ? floatval($section['capacity_percentage']) : 20,
                    'color' => isset($section['color']) ? $section['color'] : '#10b981',
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
                            'id' => isset($item['id']) ? $item['id'] : 'item-' . uniqid(),
                            'gear_id' => isset($item['gear_id']) ? $item['gear_id'] : null,
                            'name' => trim($item['name']),
                            'weight_g' => isset($item['weight_g']) ? floatval($item['weight_g']) : 0,
                            'quantity' => isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1,
                            'notes' => isset($item['notes']) ? trim($item['notes']) : '',
                            'worn' => isset($item['worn']) ? (bool)$item['worn'] : false,
                            'consumable' => isset($item['consumable']) ? (bool)$item['consumable'] : false,
                            'category' => isset($item['category']) ? $item['category'] : 'other',
                            'packed' => isset($item['packed']) ? (bool)$item['packed'] : false,
                            'last_packed' => isset($item['last_packed']) ? $item['last_packed'] : null
                        ];
                        
                        // Handle weight overrides
                        if (isset($item['overrides']) && isset($item['overrides']['weight_g'])) {
                            $validItem['overrides'] = [
                                'weight_g' => floatval($item['overrides']['weight_g'])
                            ];
                        }
                        
                        $validSection['items'][] = $validItem;
                    }
                }
                
                $existing['sections'][] = $validSection;
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
        $backpacks[$existingIndex] = $existing;
        saveBackpacksData($backpacks);
        
        Response::success($existing, 'Backpack updated successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to update backpack: ' . $e->getMessage());
    }
}

/**
 * Delete backpack
 */
function deleteBackpack($id) {
    try {
        $db = Database::getInstance();
        $force = isset($_GET['force']) && $_GET['force'] === 'true';
        
        // Check if backpack exists
        if ($db->isSQLite()) {
            $existing = $db->fetchOne("SELECT * FROM backpacks WHERE id = :id", ['id' => $id]);
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
