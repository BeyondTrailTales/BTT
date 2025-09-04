<?php
/**
 * Direct AJAX Handler - Bypasses complex API to avoid timeouts
 * This handles backpack CRUD operations directly with SQLite
 */

// Clean all output buffers and start fresh
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/app/bootstrap.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Set headers early
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Connect directly to SQLite
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys = ON');

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$route = $_GET['route'] ?? '';
$id = $_GET['id'] ?? null;

// Debug logging (only to error log, not output)
if (defined('BTT_DEBUG') && BTT_DEBUG) {
    error_log("AJAX Handler: User=$user_id, Method=$method, Route=$route, ID=$id");
}

// Check if required tables exist and create them if not
try {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('backpacks', $tables)) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Creating missing backpacks table");
        $db->exec("
            CREATE TABLE backpacks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                capacity_l INTEGER DEFAULT 65,
                weight_empty_g INTEGER DEFAULT 0,
                type VARCHAR(50) DEFAULT 'custom',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    if (!in_array('backpack_gear', $tables)) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Creating missing backpack_gear table");
        $db->exec("
            CREATE TABLE backpack_gear (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                backpack_id INTEGER NOT NULL,
                custom_name VARCHAR(255) NOT NULL,
                custom_weight INTEGER DEFAULT 0,
                custom_category VARCHAR(100) DEFAULT 'other',
                quantity INTEGER DEFAULT 1,
                section VARCHAR(100) DEFAULT 'main',
                position INTEGER DEFAULT 0,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    if (!in_array('user_gear', $tables)) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Creating missing user_gear table");
        $db->exec("
            CREATE TABLE user_gear (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL,
                weight_g INTEGER DEFAULT 0,
                notes TEXT,
                tags TEXT,
                deleted_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    if (!in_array('trips', $tables)) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Creating missing trips table");
        $db->exec("
            CREATE TABLE trips (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                location VARCHAR(255),
                start_date DATE,
                end_date DATE,
                description TEXT,
                trip_type VARCHAR(50),
                distance REAL DEFAULT 0,
                distance_unit VARCHAR(10) DEFAULT 'miles',
                elevation_gain REAL DEFAULT 0,
                difficulty VARCHAR(50),
                favorite INTEGER DEFAULT 0,
                completed INTEGER DEFAULT 0,
                backpack_id INTEGER,
                photo_path VARCHAR(500),
                photo_alt_text VARCHAR(500),
                permit_required INTEGER DEFAULT 0,
                permit_cost REAL,
                permit_info TEXT,
                trailhead_parking VARCHAR(255),
                parking_cost REAL,
                cell_coverage VARCHAR(50),
                crowd_level VARCHAR(50),
                water_sources TEXT,
                trail_conditions TEXT,
                pre_trip_notes TEXT,
                post_trip_notes TEXT,
                lessons_learned TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
} catch (Exception $e) {
    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Error checking/creating tables: " . $e->getMessage());
}

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
            // Return gear in the expected format for GearManager
            $stmt = $db->prepare("
                SELECT * FROM user_gear 
                WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '') 
                ORDER BY category, name
            ");
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
            
            // Combine all gear sources  
            $gear = [];
            
            // Add user gear first
            foreach ($userGear as $item) {
                $gear[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'weight_g' => (int)($item['weight_g'] ?? 0),
                    'notes' => $item['notes'] ?? '',
                    'tags' => !empty($item['tags']) ? (is_string($item['tags']) ? explode(',', $item['tags']) : $item['tags']) : [],
                    'is_default' => false,
                    'is_custom' => true
                ];
            }
            
            // Add default gear if we don't have much custom gear
            if (count($gear) < 50 && !empty($defaultGear)) {
                foreach ($defaultGear as $item) {
                    $gear[] = [
                        'id' => 'def-' . ($item['id'] ?? uniqid()),
                        'name' => $item['name'],
                        'category' => $item['category'],
                        'weight_g' => (int)($item['weight_g'] ?? 0),
                        'notes' => $item['notes'] ?? '',
                        'tags' => $item['tags'] ?? [],
                        'is_default' => true,
                        'is_custom' => false
                    ];
                }
            }
            
            // Return in the expected format for GearManager.js
            ob_clean();
            echo json_encode([
                'success' => true,
                'data' => [
                    'items' => $gear,
                    'total' => count($gear)
                ]
            ]);
            exit;
        }
        
        if ($method === 'POST') {
            // Create new gear item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['category'])) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Name and category are required']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO user_gear (user_id, name, category, weight_g, notes, tags, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            
            $tags = isset($data['tags']) && is_array($data['tags']) ? implode(',', $data['tags']) : (string)($data['tags'] ?? '');
            
            $stmt->execute([
                $user_id,
                $data['name'],
                $data['category'],
                (float)($data['weight_g'] ?? 0),
                $data['notes'] ?? '',
                $tags
            ]);
            
            $itemId = $db->lastInsertId();
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $itemId,
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'weight_g' => (float)($data['weight_g'] ?? 0),
                    'notes' => $data['notes'] ?? '',
                    'tags' => is_array($data['tags']) ? $data['tags'] : []
                ],
                'message' => 'Gear item created successfully'
            ]);
            exit;
        }
        
        if ($method === 'PUT') {
            // Update gear item
            if (!$id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                UPDATE user_gear 
                SET name = ?, category = ?, weight_g = ?, notes = ?, tags = ?, updated_at = datetime('now')
                WHERE id = ? AND user_id = ?
            ");
            
            $tags = isset($data['tags']) && is_array($data['tags']) ? implode(',', $data['tags']) : (string)($data['tags'] ?? '');
            
            $stmt->execute([
                $data['name'] ?? '',
                $data['category'] ?? '',
                (float)($data['weight_g'] ?? 0),
                $data['notes'] ?? '',
                $tags,
                $id,
                $user_id
            ]);
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Gear item updated successfully'
            ]);
            exit;
        }
        
        if ($method === 'DELETE') {
            // Delete gear item
            if (!$id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM user_gear WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Gear item deleted successfully'
            ]);
            exit;
        }
    }
    
    // Handle trips
    if ($route === 'trips') {
        if ($method === 'GET') {
            if ($id) {
                // Get single trip
                $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $user_id]);
                $trip = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode($trip ?: ['success' => false, 'message' => 'Trip not found']);
                exit;
            } else {
                // Get all trips
                $stmt = $db->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode($trips);
                exit;
            }
            exit;
        }
        
        if ($method === 'POST') {
            // Create new trip
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['title'])) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO trips (
                    user_id, title, location, start_date, end_date, description, 
                    trip_type, distance, distance_unit, elevation_gain, difficulty,
                    favorite, completed, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            
            $stmt->execute([
                $user_id,
                $data['title'],
                $data['location'] ?? '',
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['description'] ?? '',
                $data['trip_type'] ?? null,
                (float)($data['distance'] ?? 0),
                $data['distance_unit'] ?? 'miles',
                (float)($data['elevation_gain'] ?? 0),
                $data['difficulty'] ?? null,
                (int)($data['favorite'] ?? 0),
                (int)($data['completed'] ?? 0)
            ]);
            
            $tripId = $db->lastInsertId();
            
            // Return created trip
            $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
            $stmt->execute([$tripId]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            ob_clean();
            echo json_encode($trip);
            exit;
        }
        
        if ($method === 'PUT') {
            // Update trip
            if (!$id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                UPDATE trips 
                SET title = ?, location = ?, start_date = ?, end_date = ?, description = ?,
                    trip_type = ?, distance = ?, distance_unit = ?, elevation_gain = ?, difficulty = ?,
                    favorite = ?, completed = ?, updated_at = datetime('now')
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['title'] ?? '',
                $data['location'] ?? '',
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['description'] ?? '',
                $data['trip_type'] ?? null,
                (float)($data['distance'] ?? 0),
                $data['distance_unit'] ?? 'miles',
                (float)($data['elevation_gain'] ?? 0),
                $data['difficulty'] ?? null,
                (int)($data['favorite'] ?? 0),
                (int)($data['completed'] ?? 0),
                $id,
                $user_id
            ]);
            
            // Return updated trip
            $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
            $stmt->execute([$id]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            ob_clean();
            echo json_encode($trip);
            exit;
        }
        
        if ($method === 'DELETE') {
            // Delete trip
            if (!$id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Trip deleted successfully'
            ]);
            exit;
        }
    }
    
    // Handle backpack operations
    if ($route === 'backpacks') {
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    // Get single backpack
                    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Getting single backpack ID=$id for user=$user_id");
                    $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get items for this backpack
                    if ($pack) {
                        $stmt = $db->prepare("SELECT * FROM backpack_gear WHERE backpack_id = ?");
                        $stmt->execute([$id]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Found " . count($items) . " items for backpack $id");
                        
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
                    } else {
                        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Backpack $id not found for user $user_id");
                    }
                    
                    ob_clean();
                    echo json_encode($pack ?: ['success' => false, 'message' => 'Backpack not found']);
                    exit;
                } else {
                    // Get all backpacks
                    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Getting all backpacks for user=$user_id");
                    $stmt = $db->prepare("SELECT * FROM backpacks WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$user_id]);
                    $packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Found " . count($packs) . " backpacks for user=$user_id");
                    
                    // Calculate actual weight and item count for each pack
                    foreach ($packs as &$pack) {
                        $stmt = $db->prepare("SELECT SUM(custom_weight * quantity) as total_weight, SUM(quantity) as total_items FROM backpack_gear WHERE backpack_id = ?");
                        $stmt->execute([$pack['id']]);
                        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $pack['total_weight_g'] = ($pack['weight_empty_g'] ?? 0) + ($stats['total_weight'] ?? 0);
                        $pack['total_items'] = $stats['total_items'] ?? 0;
                        $pack['trip_count'] = 0; // TODO: Add trip count if needed
                    }
                    
                    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Returning " . count($packs) . " processed backpacks");
                    // Clean any remaining output and return JSON
                    ob_clean();
                    echo json_encode($packs);
                    exit;
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
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack created successfully'
                ]);
                exit;
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
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack updated successfully'
                ]);
                exit;
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
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Backpack deleted successfully'
                ]);
                exit;
                break;
        }
    }
    
    // If no route matched, return error
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unknown route: ' . $route
    ]);
    exit;
    
} catch (Exception $e) {
    // Clean any output buffer before error response
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}

// This should never be reached due to explicit exits above
if (!headers_sent()) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No matching route found'
    ]);
    exit;
}
