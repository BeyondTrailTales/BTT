<?php
/**
 * Helper functions for backpack API operations
 */

/**
 * Get backpacks data from JSON storage
 */
function getBackpacksData() {
    $file = BTT_JSON_PATH . '/backpacks.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?? [];
    }
    return [];
}

/**
 * Save backpacks data to JSON storage
 */
function saveBackpacksData($backpacks) {
    $file = BTT_JSON_PATH . '/backpacks.json';
    file_put_contents($file, json_encode($backpacks, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Get gear data from JSON storage
 */
function getGearData() {
    $file = BTT_JSON_PATH . '/gear.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?? [];
    }
    return [];
}

/**
 * Save gear data to JSON storage
 */
function saveGearData($gear) {
    $file = BTT_JSON_PATH . '/gear.json';
    file_put_contents($file, json_encode($gear, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Get next available ID for a collection
 */
function getNextId($collection) {
    $maxId = 0;
    foreach ($collection as $item) {
        if (isset($item['id']) && $item['id'] > $maxId) {
            $maxId = $item['id'];
        }
    }
    return $maxId + 1;
}

/**
 * Create default sections for a new backpack
 */
function createDefaultSections($backpackId) {
    return [
        [
            'id' => 'main-' . $backpackId,
            'name' => 'Main Compartment',
            'order' => 0,
            'capacity_percentage' => 40,
            'color' => '#10b981',
            'items' => []
        ],
        [
            'id' => 'top-' . $backpackId,
            'name' => 'Top Lid',
            'order' => 1,
            'capacity_percentage' => 15,
            'color' => '#3b82f6',
            'items' => []
        ],
        [
            'id' => 'side-' . $backpackId,
            'name' => 'Side Pockets',
            'order' => 2,
            'capacity_percentage' => 15,
            'color' => '#8b5cf6',
            'items' => []
        ],
        [
            'id' => 'front-' . $backpackId,
            'name' => 'Front Mesh',
            'order' => 3,
            'capacity_percentage' => 10,
            'color' => '#f59e0b',
            'items' => []
        ],
        [
            'id' => 'hip-' . $backpackId,
            'name' => 'Hip Belt',
            'order' => 4,
            'capacity_percentage' => 10,
            'color' => '#ef4444',
            'items' => []
        ],
        [
            'id' => 'external-' . $backpackId,
            'name' => 'External Attachments',
            'order' => 5,
            'capacity_percentage' => 10,
            'color' => '#14b8a6',
            'items' => []
        ]
    ];
}

/**
 * Get backpack templates
 */
function getBackpackTemplates() {
    try {
        $templatesDir = BTT_ROOT . '/storage/templates/backpacks';
        $templates = [];
        
        if (is_dir($templatesDir)) {
            $files = glob($templatesDir . '/*.json');
            foreach ($files as $file) {
                $template = json_decode(file_get_contents($file), true);
                if ($template) {
                    // Calculate total weight and item count
                    $totalWeight = isset($template['weight_empty_g']) ? $template['weight_empty_g'] : 0;
                    $itemCount = 0;
                    
                    if (isset($template['sections'])) {
                        foreach ($template['sections'] as $section) {
                            if (isset($section['items'])) {
                                foreach ($section['items'] as $item) {
                                    $itemWeight = isset($item['weight_g']) ? $item['weight_g'] : 0;
                                    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                                    $totalWeight += $itemWeight * $quantity;
                                    $itemCount += $quantity;
                                }
                            }
                        }
                    }
                    
                    $template['total_weight_g'] = $totalWeight;
                    $template['item_count'] = $itemCount;
                    $templates[] = $template;
                }
            }
        }
        
        Response::success($templates);
    } catch (Exception $e) {
        Response::serverError('Failed to fetch templates: ' . $e->getMessage());
    }
}

/**
 * Duplicate a backpack
 */
function duplicateBackpack($id) {
    try {
        $backpacks = getBackpacksData();
        $sourceBackpack = null;
        
        foreach ($backpacks as $backpack) {
            if ($backpack['id'] == $id) {
                $sourceBackpack = $backpack;
                break;
            }
        }
        
        if (!$sourceBackpack) {
            Response::notFound('Source backpack not found');
        }
        
        $data = get_request_data();
        $nextId = getNextId($backpacks);
        
        // Create duplicate with new ID
        $newBackpack = $sourceBackpack;
        $newBackpack['id'] = $nextId;
        $newBackpack['name'] = isset($data['name']) ? $data['name'] : $sourceBackpack['name'] . ' (Copy)';
        $newBackpack['created_at'] = date('Y-m-d H:i:s');
        $newBackpack['updated_at'] = date('Y-m-d H:i:s');
        $newBackpack['linked_trip_id'] = null;
        
        // Update section IDs
        if (isset($newBackpack['sections'])) {
            foreach ($newBackpack['sections'] as &$section) {
                $section['id'] = str_replace('-' . $id, '-' . $nextId, $section['id']);
                
                // Update item IDs
                if (isset($section['items'])) {
                    foreach ($section['items'] as &$item) {
                        $item['id'] = 'item-' . uniqid();
                        $item['packed'] = false; // Reset packed state
                        $item['last_packed'] = null;
                    }
                }
            }
        }
        
        $backpacks[] = $newBackpack;
        saveBackpacksData($backpacks);
        
        Response::success($newBackpack, 'Backpack duplicated successfully');
    } catch (Exception $e) {
        Response::serverError('Failed to duplicate backpack: ' . $e->getMessage());
    }
}

/**
 * Create backpack from template
 */
function createFromTemplate($templateId) {
    try {
        $templateFile = BTT_ROOT . '/storage/templates/backpacks/' . $templateId . '.json';
        
        if (!file_exists($templateFile)) {
            Response::notFound('Template not found');
        }
        
        $template = json_decode(file_get_contents($templateFile), true);
        if (!$template) {
            Response::error('Invalid template file', 400);
        }
        
        $data = get_request_data();
        $backpacks = getBackpacksData();
        $nextId = getNextId($backpacks);
        
        // Create new backpack from template
        $newBackpack = $template;
        $newBackpack['id'] = $nextId;
        $newBackpack['name'] = isset($data['name']) ? $data['name'] : $template['name'];
        $newBackpack['template_id'] = $templateId;
        $newBackpack['created_at'] = date('Y-m-d H:i:s');
        $newBackpack['updated_at'] = date('Y-m-d H:i:s');
        $newBackpack['linked_trip_id'] = null;
        
        // Update section IDs
        if (isset($newBackpack['sections'])) {
            foreach ($newBackpack['sections'] as &$section) {
                $section['id'] = $section['id'] . '-' . $nextId;
                
                // Update item IDs and reset packed state
                if (isset($section['items'])) {
                    foreach ($section['items'] as &$item) {
                        $item['id'] = 'item-' . uniqid();
                        $item['packed'] = false;
                        $item['last_packed'] = null;
                    }
                }
            }
        }
        
        $backpacks[] = $newBackpack;
        saveBackpacksData($backpacks);
        
        Response::success($newBackpack, 'Backpack created from template successfully');
    } catch (Exception $e) {
        Response::serverError('Failed to create from template: ' . $e->getMessage());
    }
}

/**
 * Export backpack to JSON
 */
function exportBackpack($id) {
    try {
        $backpacks = getBackpacksData();
        $backpack = null;
        
        foreach ($backpacks as $b) {
            if ($b['id'] == $id) {
                $backpack = $b;
                break;
            }
        }
        
        if (!$backpack) {
            Response::notFound('Backpack not found');
        }
        
        // Remove IDs and timestamps for clean export
        $export = $backpack;
        unset($export['id']);
        unset($export['created_at']);
        unset($export['updated_at']);
        unset($export['linked_trip_id']);
        
        // Clean section IDs
        if (isset($export['sections'])) {
            foreach ($export['sections'] as &$section) {
                unset($section['id']);
                if (isset($section['items'])) {
                    foreach ($section['items'] as &$item) {
                        unset($item['id']);
                        unset($item['last_packed']);
                    }
                }
            }
        }
        
        // Set headers for download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . 
               preg_replace('/[^a-zA-Z0-9-_]/', '', $backpack['name']) . '.json"');
        
        echo json_encode($export, JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        Response::serverError('Failed to export backpack: ' . $e->getMessage());
    }
}

/**
 * Import backpack from JSON
 */
function importBackpack() {
    try {
        $data = get_request_data();
        
        if (!$data || !is_array($data)) {
            Response::error('Invalid import data', 400);
        }
        
        // Validate required fields
        if (!isset($data['name'])) {
            Response::validationError(['name' => 'Name is required in import data']);
        }
        
        $backpacks = getBackpacksData();
        $nextId = getNextId($backpacks);
        
        // Create new backpack from import
        $newBackpack = $data;
        $newBackpack['id'] = $nextId;
        $newBackpack['created_at'] = date('Y-m-d H:i:s');
        $newBackpack['updated_at'] = date('Y-m-d H:i:s');
        $newBackpack['linked_trip_id'] = null;
        $newBackpack['version'] = '1.0.0';
        
        // Ensure default values
        if (!isset($newBackpack['capacity_l'])) {
            $newBackpack['capacity_l'] = 65;
        }
        if (!isset($newBackpack['weight_empty_g'])) {
            $newBackpack['weight_empty_g'] = 0;
        }
        if (!isset($newBackpack['type'])) {
            $newBackpack['type'] = 'custom';
        }
        if (!isset($newBackpack['tags'])) {
            $newBackpack['tags'] = [];
        }
        
        // Generate IDs for sections and items
        if (isset($newBackpack['sections'])) {
            $sectionOrder = 0;
            foreach ($newBackpack['sections'] as &$section) {
                $section['id'] = 'section-' . uniqid();
                if (!isset($section['order'])) {
                    $section['order'] = $sectionOrder++;
                }
                
                if (isset($section['items'])) {
                    $itemOrder = 0;
                    foreach ($section['items'] as &$item) {
                        $item['id'] = 'item-' . uniqid();
                        if (!isset($item['order'])) {
                            $item['order'] = $itemOrder++;
                        }
                        if (!isset($item['packed'])) {
                            $item['packed'] = false;
                        }
                        $item['last_packed'] = null;
                    }
                }
            }
        } else {
            $newBackpack['sections'] = createDefaultSections($nextId);
        }
        
        $backpacks[] = $newBackpack;
        saveBackpacksData($backpacks);
        
        Response::success($newBackpack, 'Backpack imported successfully');
    } catch (Exception $e) {
        Response::serverError('Failed to import backpack: ' . $e->getMessage());
    }
}

/**
 * Add section to backpack
 */
function addSection($backpackId) {
    try {
        $data = get_request_data();
        
        if (!isset($data['name'])) {
            Response::validationError(['name' => 'Section name is required']);
        }
        
        $backpacks = getBackpacksData();
        $found = false;
        
        foreach ($backpacks as &$backpack) {
            if ($backpack['id'] == $backpackId) {
                $found = true;
                
                if (!isset($backpack['sections'])) {
                    $backpack['sections'] = [];
                }
                
                // Find max order
                $maxOrder = -1;
                foreach ($backpack['sections'] as $section) {
                    if (isset($section['order']) && $section['order'] > $maxOrder) {
                        $maxOrder = $section['order'];
                    }
                }
                
                $newSection = [
                    'id' => 'section-' . uniqid(),
                    'name' => $data['name'],
                    'order' => $maxOrder + 1,
                    'capacity_percentage' => isset($data['capacity_percentage']) ? $data['capacity_percentage'] : 10,
                    'color' => isset($data['color']) ? $data['color'] : '#10b981',
                    'items' => []
                ];
                
                $backpack['sections'][] = $newSection;
                $backpack['updated_at'] = date('Y-m-d H:i:s');
                
                saveBackpacksData($backpacks);
                Response::success($newSection, 'Section added successfully');
                return;
            }
        }
        
        if (!$found) {
            Response::notFound('Backpack not found');
        }
    } catch (Exception $e) {
        Response::serverError('Failed to add section: ' . $e->getMessage());
    }
}

/**
 * Add item to section
 */
function addItemToSection($backpackId) {
    try {
        $data = get_request_data();
        
        if (!isset($data['section_id']) || !isset($data['name']) || !isset($data['weight_g'])) {
            Response::validationError([
                'section_id' => 'Section ID is required',
                'name' => 'Item name is required',
                'weight_g' => 'Item weight is required'
            ]);
        }
        
        $backpacks = getBackpacksData();
        $found = false;
        
        foreach ($backpacks as &$backpack) {
            if ($backpack['id'] == $backpackId) {
                $found = true;
                
                if (!isset($backpack['sections'])) {
                    Response::error('Backpack has no sections', 400);
                }
                
                $sectionFound = false;
                foreach ($backpack['sections'] as &$section) {
                    if ($section['id'] == $data['section_id']) {
                        $sectionFound = true;
                        
                        if (!isset($section['items'])) {
                            $section['items'] = [];
                        }
                        
                        // Find max order
                        $maxOrder = -1;
                        foreach ($section['items'] as $item) {
                            if (isset($item['order']) && $item['order'] > $maxOrder) {
                                $maxOrder = $item['order'];
                            }
                        }
                        
                        $newItem = [
                            'id' => 'item-' . uniqid(),
                            'gear_id' => isset($data['gear_id']) ? $data['gear_id'] : null,
                            'name' => $data['name'],
                            'category' => isset($data['category']) ? $data['category'] : 'other',
                            'quantity' => isset($data['quantity']) ? intval($data['quantity']) : 1,
                            'weight_g' => floatval($data['weight_g']),
                            'consumable' => isset($data['consumable']) ? $data['consumable'] : false,
                            'worn' => isset($data['worn']) ? $data['worn'] : false,
                            'packed' => false,
                            'notes' => isset($data['notes']) ? $data['notes'] : '',
                            'order' => $maxOrder + 1,
                            'last_packed' => null
                        ];
                        
                        $section['items'][] = $newItem;
                        $backpack['updated_at'] = date('Y-m-d H:i:s');
                        
                        saveBackpacksData($backpacks);
                        Response::success($newItem, 'Item added successfully');
                        return;
                    }
                }
                
                if (!$sectionFound) {
                    Response::notFound('Section not found');
                }
                return;
            }
        }
        
        if (!$found) {
            Response::notFound('Backpack not found');
        }
    } catch (Exception $e) {
        Response::serverError('Failed to add item: ' . $e->getMessage());
    }
}

// Include this file in the main backpacks.php route file
require_once __DIR__ . '/backpacks_helpers.php';
?>
