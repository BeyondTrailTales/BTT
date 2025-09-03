<?php
/**
 * Direct AJAX Handler - Bypasses complex API to avoid timeouts
 * This handles backpack CRUD operations directly with SQLite
 */

require_once __DIR__ . '/app/bootstrap.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

// Connect directly to SQLite
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$route = $_GET['route'] ?? '';
$id = $_GET['id'] ?? null;

// Helper function to get icon for category
function getIcon($category) {
    $icons = [
        'shelter' => '⛺',
        'sleep' => '🛌',
        'cooking' => '🔥',
        'water' => '💧',
        'clothing' => '👕',
        'navigation' => '🗺️',
        'hygiene' => '🧼',
        'first-aid' => '🏥',
        'electronics' => '📱',
        'food' => '🍔',
        'footwear' => '👟',
        'repair' => '🔧',
        'other' => '📦',
        'tools' => '🔧',
        'pack' => '🎒'
    ];
    return $icons[$category] ?? '📦';
}

try {
    // Handle gear library
    if ($route === 'gear') {
        if ($method === 'GET') {
            // First, try to load user-specific gear
            $stmt = $db->prepare("SELECT * FROM user_gear WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '') ORDER BY category, name");
            $stmt->execute([$user_id]);
            $userGear = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Load default gear from gear-default.json file
            $gearJsonFile = __DIR__ . '/assets/data/gear-default.json';
            $defaultGear = [];
            
            if (file_exists($gearJsonFile)) {
                $jsonContent = file_get_contents($gearJsonFile);
                $gearData = json_decode($jsonContent, true);
                
                if (isset($gearData['items']) && is_array($gearData['items'])) {
                    $defaultGear = $gearData['items'];
                }
            }
            
            // Also try to load global gear items from database
            $stmt = $db->query("SELECT * FROM gear_items WHERE user_id IS NULL OR user_id = 0 ORDER BY category, name");
            $globalGear = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine all gear sources
            $gear = array_merge($userGear, $globalGear);
            
            // Add default gear from JSON if we don't have much in the database
            if (count($gear) < 50 && !empty($defaultGear)) {
                // Add default gear from JSON file
                foreach ($defaultGear as $item) {
                    $gear[] = [
                        'id' => $item['id'] ?? 'def-' . uniqid(),
                        'name' => $item['name'],
                        'category' => $item['category'],
                        'weight' => $item['weight_g'] ?? 0,
                        'weight_g' => $item['weight_g'] ?? 0,
                        'brand' => '',
                        'icon' => $item['icon'] ?? '',
                        'notes' => $item['notes'] ?? '',
                        'tags' => $item['tags'] ?? [],
                        'is_default' => true
                    ];
                }
            } elseif (empty($gear) && empty($defaultGear)) {
                // Fallback to minimal default items if JSON file not found
                $minimalDefaults = [
                    ['name' => 'Tent', 'category' => 'shelter', 'weight' => 1200],
                    ['name' => 'Sleeping Bag', 'category' => 'sleep', 'weight' => 900],
                    ['name' => 'Sleeping Pad', 'category' => 'sleep', 'weight' => 450],
                    ['name' => 'Stove', 'category' => 'cooking', 'weight' => 100],
                    ['name' => 'Water Filter', 'category' => 'water', 'weight' => 80]
                ];
                
                foreach ($minimalDefaults as $item) {
                    $gear[] = [
                        'id' => 'min-' . uniqid(),
                        'name' => $item['name'],
                        'category' => $item['category'],
                        'weight' => $item['weight'],
                        'weight_g' => $item['weight'],
                        'brand' => '',
                        'icon' => getIcon($item['category']),
                        'is_default' => true
                    ];
                }
            }
            
            // Format for frontend (handle both table formats)
            $formattedGear = [];
            foreach ($gear as $item) {
                // Handle weight field name differences
                $weight = isset($item['weight_g']) ? $item['weight_g'] : (isset($item['weight']) ? $item['weight'] : 0);
                
                $formattedGear[] = [
                    'id' => $item['id'] ?? 'gear-' . uniqid(),
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'weight' => (int)$weight,
                    'weight_g' => (int)$weight,
                    'brand' => $item['brand'] ?? '',
                    'icon' => $item['icon'] ?? getIcon($item['category'])
                ];
            }
            
            echo json_encode($formattedGear);
            exit;
        }
    }
    
    // Handle backpack operations
    if ($route === 'backpacks') {
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    // Get single backpack
                    $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get items for this backpack
                    if ($pack) {
                        $stmt = $db->prepare("SELECT * FROM backpack_gear WHERE backpack_id = ?");
                        $stmt->execute([$id]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Organize items into sections
                        $sections = [];
                        foreach ($items as $item) {
                            $section = $item['section'] ?? 'main';
                            if (!isset($sections[$section])) {
                                $sections[$section] = [
                                    'id' => $section,
                                    'name' => ucfirst($section),
                                    'items' => []
                                ];
                            }
                            $sections[$section]['items'][] = [
                                'name' => $item['custom_name'] ?? 'Unknown',
                                'weight_g' => $item['custom_weight'] ?? 0,
                                'quantity' => $item['quantity'] ?? 1,
                                'category' => $item['custom_category'] ?? 'other'
                            ];
                        }
                        $pack['sections'] = array_values($sections);
                    }
                    
                    echo json_encode($pack ?: ['success' => false, 'message' => 'Backpack not found']);
                } else {
                    // Get all backpacks
                    $stmt = $db->prepare("SELECT * FROM backpacks WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$user_id]);
                    $packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Calculate actual weight and item count for each pack
                    foreach ($packs as &$pack) {
                        $stmt = $db->prepare("SELECT SUM(custom_weight * quantity) as total_weight, SUM(quantity) as total_items FROM backpack_gear WHERE backpack_id = ?");
                        $stmt->execute([$pack['id']]);
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $pack['total_weight_g'] = ($pack['weight_empty_g'] ?? 0) + ($stats['total_weight'] ?? 0);
                        $pack['total_items'] = $stats['total_items'] ?? 0;
                        $pack['trip_count'] = 0; // TODO: Add trip count if needed
                    }
                    
                    // Return in API format
                    echo json_encode($packs);
                }
                break;
                
            case 'POST':
                // Create new backpack
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['name'])) {
                    echo json_encode(['success' => false, 'message' => 'Name is required']);
                    exit;
                }
                
                $stmt = $db->prepare("
                    INSERT INTO backpacks (user_id, name, description, capacity_l, weight_empty_g, type, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                
                $stmt->execute([
                    $user_id,
                    $data['name'],
                    $data['description'] ?? '',
                    $data['capacity_l'] ?? 65,
                    $data['weight_empty_g'] ?? 0,
                    $data['type'] ?? 'custom'
                ]);
                
                $packId = $db->lastInsertId();
                
                // Save sections and items if provided
                if (isset($data['sections']) && is_array($data['sections'])) {
                    foreach ($data['sections'] as $section) {
                        if (isset($section['items']) && is_array($section['items'])) {
                            foreach ($section['items'] as $item) {
                                $stmt = $db->prepare("
                                    INSERT INTO backpack_gear 
                                    (backpack_id, custom_name, custom_weight, custom_category, quantity, section, position) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $packId,
                                    $item['name'] ?? 'Unknown',
                                    $item['weight_g'] ?? 0,
                                    $item['category'] ?? 'other',
                                    $item['quantity'] ?? 1,
                                    $section['id'] ?? 'main',
                                    0
                                ]);
                            }
                        }
                    }
                }
                
                // Return created pack
                $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ?");
                $stmt->execute([$packId]);
                $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack created successfully'
                ]);
                break;
                
            case 'PUT':
                // Update backpack
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'ID required']);
                    exit;
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $db->prepare("
                    UPDATE backpacks 
                    SET name = ?, description = ?, updated_at = datetime('now')
                    WHERE id = ? AND user_id = ?
                ");
                
                $stmt->execute([
                    $data['name'] ?? '',
                    $data['description'] ?? '',
                    $id,
                    $user_id
                ]);
                
                // Clear existing items
                $db->prepare("DELETE FROM backpack_gear WHERE backpack_id = ?")->execute([$id]);
                
                // Save new items
                if (isset($data['sections']) && is_array($data['sections'])) {
                    foreach ($data['sections'] as $section) {
                        if (isset($section['items']) && is_array($section['items'])) {
                            foreach ($section['items'] as $item) {
                                $stmt = $db->prepare("
                                    INSERT INTO backpack_gear 
                                    (backpack_id, custom_name, custom_weight, custom_category, quantity, section, position) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $id,
                                    $item['name'] ?? 'Unknown',
                                    $item['weight_g'] ?? 0,
                                    $item['category'] ?? 'other',
                                    $item['quantity'] ?? 1,
                                    $section['id'] ?? 'main',
                                    0
                                ]);
                            }
                        }
                    }
                }
                
                // Return updated pack
                $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ?");
                $stmt->execute([$id]);
                $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack updated successfully'
                ]);
                break;
                
            case 'DELETE':
                if (!$id) {
                    echo json_encode(['success' => false, 'message' => 'ID required']);
                    exit;
                }
                
                // Delete items first
                $db->prepare("DELETE FROM backpack_gear WHERE backpack_id = ?")->execute([$id]);
                
                // Delete backpack
                $stmt = $db->prepare("DELETE FROM backpacks WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Backpack deleted successfully'
                ]);
                break;
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
