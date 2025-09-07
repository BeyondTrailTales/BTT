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
require_once __DIR__ . '/app/helpers/achievement_helper.php';
require_once __DIR__ . '/api/routes/trip_packing.php';

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

// Handle method override for FormData requests (from BTTApi.put and BTTApi.delete)
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$route = $_GET['route'] ?? '';
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Debug logging (only to error log, not output)
if (defined('BTT_DEBUG') && BTT_DEBUG) {
    error_log("AJAX Handler: User=$user_id, Method=$method, Route=$route, ID=$id");
}

/**
 * Handle create trip action for standalone edit page
 */
function handleCreateTripAction($db, $user_id) {
    try {
        // Validate required fields
        $title = $_POST['title'] ?? '';
        if (empty(trim($title))) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Adventure name is required']);
            exit;
        }

        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/img/trips/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload JPG, JPEG, or PNG files only.']);
                exit;
            }
            
            // Create unique filename
            $timestamp = date('Ymd_His');
            $randomId = substr(md5(uniqid()), 0, 13);
            $newFilename = "{$timestamp}_{$randomId}.{$fileExtension}";
            $uploadPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $photoPath = 'assets/img/trips/' . $newFilename;
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Failed to upload photo']);
                exit;
            }
        }

        // Prepare insert data
        $insertData = [
            'title' => $title,
            'location' => $_POST['location'] ?? '',
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'distance' => $_POST['distance'] ? floatval($_POST['distance']) : null,
            'distance_unit' => $_POST['distance_unit'] ?? 'miles',
            'elevation_gain' => $_POST['elevation_gain'] ? intval($_POST['elevation_gain']) : null,
            'difficulty' => $_POST['difficulty'] ?? null,
            'trip_type' => $_POST['trip_type'] ?? null,
            'description' => $_POST['description'] ?? '',
            'favorite' => isset($_POST['favorite']) ? intval($_POST['favorite']) : 0,
            'completed' => isset($_POST['completed']) ? intval($_POST['completed']) : 0,
            'backpack_id' => $_POST['backpack_id'] ?: null,
            'photo_path' => $photoPath,
            'photo_alt_text' => $_POST['photo_alt_text'] ?? '',
            // Logistics fields
            'permit_required' => isset($_POST['permit_required']) ? intval($_POST['permit_required']) : 0,
            'permit_cost' => $_POST['permit_cost'] ? floatval($_POST['permit_cost']) : null,
            'permit_info' => $_POST['permit_info'] ?? '',
            'trailhead_parking' => $_POST['trailhead_parking'] ?? '',
            'parking_cost' => $_POST['parking_cost'] ? floatval($_POST['parking_cost']) : null,
            // Conditions fields
            'water_sources' => $_POST['water_sources'] ?? '',
            'trail_conditions' => $_POST['trail_conditions'] ?? '',
            'cell_coverage' => $_POST['cell_coverage'] ?? '',
            'crowd_level' => $_POST['crowd_level'] ?? '',
            'camping_type' => $_POST['camping_type'] ?? '',
            'expected_weather' => $_POST['expected_weather'] ?? '',
            'emergency_contact' => $_POST['emergency_contact'] ?? '',
            // Notes fields
            'pre_trip_notes' => $_POST['pre_trip_notes'] ?? '',
            'post_trip_notes' => $_POST['post_trip_notes'] ?? '',
            'lessons_learned' => $_POST['lessons_learned'] ?? '',
            // System fields
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Build insert query
        $fields = array_keys($insertData);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO trips (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($insertData));
        
        // Get the created trip
        $newTripId = $db->lastInsertId();
        if (!$newTripId) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to get created trip ID']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$newTripId, $user_id]);
        $createdTrip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$createdTrip) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Failed to retrieve created trip']);
            exit;
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Adventure created successfully!',
            'trip' => $createdTrip
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to create trip: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Handle update trip action for standalone edit page
 */
function handleUpdateTripAction($db, $user_id) {
    try {
        $tripId = $_POST['id'] ?? null;
        if (!$tripId) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Trip ID is required']);
            exit;
        }

        // Verify trip belongs to user
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        $existingTrip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingTrip) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Trip not found']);
            exit;
        }

        // Handle photo upload
        $photoPath = $existingTrip['photo_path'];
        // Extract filename from the path if it exists
        $photoFilename = $photoPath ? basename($photoPath) : null;
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/img/trips/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Invalid file type. Please upload JPG, JPEG, or PNG files only.']);
                exit;
            }
            
            // Create unique filename
            $timestamp = date('Ymd_His');
            $randomId = substr(md5(uniqid()), 0, 13);
            $newFilename = "{$timestamp}_{$randomId}.{$fileExtension}";
            $uploadPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                // Delete old photo if exists
                if ($photoFilename && file_exists($uploadDir . $photoFilename)) {
                    unlink($uploadDir . $photoFilename);
                }
                
                $photoPath = 'assets/img/trips/' . $newFilename;
                $photoFilename = $newFilename;
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Failed to upload photo']);
                exit;
            }
        }
        
        // Handle photo removal
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
            if ($photoFilename && file_exists(__DIR__ . '/assets/img/trips/' . $photoFilename)) {
                unlink(__DIR__ . '/assets/img/trips/' . $photoFilename);
            }
            $photoPath = null;
            $photoFilename = null;
        }

        // Prepare update data - include all fields
        $updateData = [
            'title' => $_POST['title'] ?? $existingTrip['title'],
            'location' => $_POST['location'] ?? $existingTrip['location'],
            'start_date' => $_POST['start_date'] ?? $existingTrip['start_date'],
            'end_date' => $_POST['end_date'] ?? $existingTrip['end_date'],
            'description' => $_POST['description'] ?? $existingTrip['description'],
            'trip_type' => $_POST['trip_type'] ?? $existingTrip['trip_type'],
            'distance' => $_POST['distance'] ? floatval($_POST['distance']) : $existingTrip['distance'],
            'distance_unit' => $_POST['distance_unit'] ?? $existingTrip['distance_unit'],
            'elevation_gain' => $_POST['elevation_gain'] ? floatval($_POST['elevation_gain']) : $existingTrip['elevation_gain'],
            'difficulty' => $_POST['difficulty'] ?? $existingTrip['difficulty'],
            'favorite' => isset($_POST['favorite']) ? intval($_POST['favorite']) : $existingTrip['favorite'],
            'completed' => isset($_POST['completed']) ? intval($_POST['completed']) : $existingTrip['completed'],
            'backpack_id' => $_POST['backpack_id'] ?: null,
            'photo_path' => $photoPath,
            'photo_alt_text' => $_POST['photo_alt_text'] ?? $existingTrip['photo_alt_text'],
            // Logistics fields
            'permit_required' => isset($_POST['permit_required']) ? intval($_POST['permit_required']) : $existingTrip['permit_required'],
            'permit_cost' => $_POST['permit_cost'] ? floatval($_POST['permit_cost']) : $existingTrip['permit_cost'],
            'permit_info' => $_POST['permit_info'] ?? $existingTrip['permit_info'],
            'trailhead_parking' => $_POST['trailhead_parking'] ?? $existingTrip['trailhead_parking'],
            'parking_cost' => $_POST['parking_cost'] ? floatval($_POST['parking_cost']) : $existingTrip['parking_cost'],
            // Conditions fields
            'water_sources' => $_POST['water_sources'] ?? $existingTrip['water_sources'],
            'trail_conditions' => $_POST['trail_conditions'] ?? $existingTrip['trail_conditions'],
            'cell_coverage' => $_POST['cell_coverage'] ?? $existingTrip['cell_coverage'],
            'crowd_level' => $_POST['crowd_level'] ?? $existingTrip['crowd_level'],
            'camping_type' => $_POST['camping_type'] ?? $existingTrip['camping_type'],
            'expected_weather' => $_POST['expected_weather'] ?? $existingTrip['expected_weather'],
            'emergency_contact' => $_POST['emergency_contact'] ?? $existingTrip['emergency_contact'],
            // Notes fields
            'pre_trip_notes' => $_POST['pre_trip_notes'] ?? $existingTrip['pre_trip_notes'],
            'post_trip_notes' => $_POST['post_trip_notes'] ?? $existingTrip['post_trip_notes'],
            'lessons_learned' => $_POST['lessons_learned'] ?? $existingTrip['lessons_learned'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Build update query
        $updateFields = [];
        $updateValues = [];
        
        foreach ($updateData as $field => $value) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
        }
        
        $updateValues[] = $tripId;
        $updateValues[] = $user_id;

        $sql = "UPDATE trips SET " . implode(', ', $updateFields) . " WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateValues);

        // Get updated trip
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $user_id]);
        $updatedTrip = $stmt->fetch(PDO::FETCH_ASSOC);

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Adventure updated successfully!',
            'trip' => $updatedTrip
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to update trip: ' . $e->getMessage()]);
        exit;
    }
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
                gear_id INTEGER DEFAULT NULL,
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

// Check if backpack_gear table has gear_id column and add it if missing
try {
    $columns = $db->query("PRAGMA table_info(backpack_gear)")->fetchAll(PDO::FETCH_ASSOC);
    $hasGearId = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'gear_id') {
            $hasGearId = true;
            break;
        }
    }
    
    if (!$hasGearId) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Adding missing gear_id column to backpack_gear table");
        $db->exec("ALTER TABLE backpack_gear ADD COLUMN gear_id INTEGER DEFAULT NULL");
    }
} catch (Exception $e) {
    if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Error checking gear_id column: " . $e->getMessage());
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
            
            // Return the gear items directly (GearManager.js expects array or object with success/data)
            ob_clean();
            echo json_encode($gear);
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
    
    // Handle trips and trip packing list routes
    if (strpos($route, 'trips/') === 0) {
        // Parse trip ID and sub-route
        $routeParts = explode('/', $route);
        if (count($routeParts) >= 3 && $routeParts[2] === 'packing-list') {
            // trips/{id}/packing-list route
            $tripId = $routeParts[1];
            require_once __DIR__ . '/api/routes/trip_packing.php';
            handleTripPackingRoute($method, [$tripId]);
            exit;
        }
    }
    
    // Handle trips
    if ($route === 'trips') {
        try {
            if ($method === 'GET') {
                if ($id && $action === 'packing-list') {
                    // Handle trip packing list requests
                    require_once __DIR__ . '/api/routes/trip_packing.php';
                    handleTripPackingRoute($method, [$id, 'packing-list']);
                    exit;
                } elseif ($id) {
                    // Get single trip
                    $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    ob_clean();
                    echo json_encode($trip ?: ['success' => false, 'message' => 'Trip not found']);
                    exit;
                } else {
                    // Get all trips - support backpack_id filter
                    $backpackId = isset($_GET['backpack_id']) ? intval($_GET['backpack_id']) : null;
                    
                    if ($backpackId) {
                        $stmt = $db->prepare("SELECT * FROM trips WHERE user_id = ? AND backpack_id = ? ORDER BY created_at DESC");
                        $stmt->execute([$user_id, $backpackId]);
                    } else {
                        $stmt = $db->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
                        $stmt->execute([$user_id]);
                    }
                    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    ob_clean();
                    echo json_encode($trips);
                    exit;
                }
            }
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
        
        // Handle packing list actions for non-GET methods
        if ($id && $action === 'packing-list') {
            require_once __DIR__ . '/api/routes/trip_packing.php';
            handleTripPackingRoute($method, [$id, 'packing-list']);
            exit;
        }
        
        if ($method === 'POST') {
            try {
                // Create new trip - handle both FormData and JSON input
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                
                // If JSON decode failed, try $_POST (for FormData)
                if ($data === null && !empty($_POST)) {
                    $data = $_POST;
                }
                
                if (empty($data['title'])) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Title is required']);
                    exit;
                }
                
                $stmt = $db->prepare("
                    INSERT INTO trips (
                        user_id, title, location, start_date, end_date, description, 
                        trip_type, distance, distance_unit, elevation_gain, difficulty,
                        favorite, completed, backpack_id, photo_path, photo_alt_text,
                        permit_required, permit_cost, permit_info, trailhead_parking, parking_cost,
                        cell_coverage, crowd_level, water_sources, trail_conditions,
                        pre_trip_notes, post_trip_notes, lessons_learned, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
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
                    (int)($data['completed'] ?? 0),
                    !empty($data['backpack_id']) ? (int)$data['backpack_id'] : null,
                    $data['photo_path'] ?? null,
                    $data['photo_alt_text'] ?? null,
                    (int)($data['permit_required'] ?? 0),
                    !empty($data['permit_cost']) ? (float)$data['permit_cost'] : null,
                    $data['permit_info'] ?? null,
                    $data['trailhead_parking'] ?? null,
                    !empty($data['parking_cost']) ? (float)$data['parking_cost'] : null,
                    $data['cell_coverage'] ?? null,
                    $data['crowd_level'] ?? null,
                    $data['water_sources'] ?? null,
                    $data['trail_conditions'] ?? null,
                    $data['pre_trip_notes'] ?? null,
                    $data['post_trip_notes'] ?? null,
                    $data['lessons_learned'] ?? null
                ]);
                
                $tripId = $db->lastInsertId();
                
                // Return created trip
                $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
                $stmt->execute([$tripId]);
                $trip = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode($trip);
                exit;
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to create trip: ' . $e->getMessage()]);
                exit;
            }
        }
        
        if ($method === 'PUT') {
            try {
                // Update trip
                if (!$id) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'ID required']);
                    exit;
                }
                
                // Handle both FormData and JSON input
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                
                // If JSON decode failed, try $_POST (for FormData)
                if ($data === null && !empty($_POST)) {
                    $data = $_POST;
                    // Remove the _method field from data
                    unset($data['_method']);
                }
                
                // Build dynamic UPDATE query based on provided fields
                $updateFields = [];
                $updateValues = [];
                
                // Handle photo removal first
                if (isset($data['remove_photo']) && $data['remove_photo'] === '1') {
                    // Get existing trip to find photo path
                    $stmt = $db->prepare("SELECT photo_path FROM trips WHERE id = ? AND user_id = ?");
                    $stmt->execute([$id, $user_id]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing && $existing['photo_path']) {
                        // Delete the physical file
                        $photoPath = __DIR__ . '/' . $existing['photo_path'];
                        if (file_exists($photoPath)) {
                            @unlink($photoPath);
                        }
                    }
                    
                    // Set photo fields to null
                    $data['photo_path'] = null;
                    $data['photo_alt_text'] = null;
                }
                
                $allowedFields = [
                    'title', 'location', 'start_date', 'end_date', 'description',
                    'trip_type', 'distance', 'distance_unit', 'elevation_gain', 'difficulty',
                    'favorite', 'completed', 'backpack_id', 'photo_path', 'photo_alt_text',
                    'permit_required', 'permit_cost', 'permit_info', 'trailhead_parking', 'parking_cost',
                    'cell_coverage', 'crowd_level', 'water_sources', 'trail_conditions',
                    'pre_trip_notes', 'post_trip_notes', 'lessons_learned'
                ];
                
                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $updateFields[] = "$field = ?";
                        
                        // Handle different field types
                        if (in_array($field, ['favorite', 'completed', 'permit_required'])) {
                            $updateValues[] = (int)$data[$field];
                        } elseif (in_array($field, ['distance', 'elevation_gain', 'permit_cost', 'parking_cost'])) {
                            $updateValues[] = $data[$field] !== '' ? (float)$data[$field] : null;
                        } elseif ($field === 'backpack_id') {
                            $updateValues[] = !empty($data[$field]) ? (int)$data[$field] : null;
                        } else {
                            $updateValues[] = $data[$field];
                        }
                    }
                }
                
                if (empty($updateFields)) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'No fields to update']);
                    exit;
                }
                
                $updateFields[] = "updated_at = datetime('now')";
                
                $stmt = $db->prepare("
                    UPDATE trips 
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ? AND user_id = ?
                ");
                
                $updateValues[] = $id;
                $updateValues[] = $user_id;
                
                $stmt->execute($updateValues);
                
                // Return updated trip
                $stmt = $db->prepare("SELECT * FROM trips WHERE id = ?");
                $stmt->execute([$id]);
                $trip = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode($trip);
                exit;
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to update trip: ' . $e->getMessage()]);
                exit;
            }
        }
        
        if ($method === 'DELETE') {
            try {
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
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to delete trip: ' . $e->getMessage()]);
                exit;
            }
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
                        $stmt = $db->prepare("SELECT * FROM backpack_gear WHERE backpack_id = ? ORDER BY section, position");
                        $stmt->execute([$id]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Found " . count($items) . " items for backpack $id");
                        
                        // Load saved sections metadata
                        $savedSections = [];
                        if (!empty($pack['sections'])) {
                            $savedSections = json_decode($pack['sections'], true) ?: [];
                        }
                        
                        // Create a map of saved section data by ID
                        $sectionMap = [];
                        foreach ($savedSections as $section) {
                            $sectionMap[$section['id']] = $section;
                        }
                        
                        // Default section data for fallback
                        $defaultSectionNames = [
                            'main' => 'Main Pack',
                            'worn' => 'Worn Items',
                            'consumables' => 'Consumables',
                            'emergency' => 'Emergency Kit',
                            'electronics' => 'Electronics',
                            'cooking' => 'Cooking Gear',
                            'shelter' => 'Shelter System'
                        ];
                        
                        $defaultSectionIcons = [
                            'main' => '🎒',
                            'worn' => '👕',
                            'consumables' => '🍎',
                            'emergency' => '🚨',
                            'electronics' => '📱',
                            'cooking' => '🍳',
                            'shelter' => '🏕️'
                        ];
                        
                        $categoryIcons = [
                            'shelter' => '🏕️',
                            'sleep' => '🛏️',
                            'clothing' => '👕',
                            'cooking' => '🍳',
                            'water' => '💧',
                            'food' => '🍞',
                            'navigation' => '🧭',
                            'safety' => '🚨',
                            'tools' => '🔧',
                            'electronics' => '📱',
                            'personal' => '🧴',
                            'other' => '📦'
                        ];
                        
                        // Organize items into sections
                        $sections = [];
                        
                        // First, create all sections from saved metadata
                        foreach ($savedSections as $section) {
                            $sections[$section['id']] = [
                                'id' => $section['id'],
                                'name' => $section['name'],
                                'icon' => $section['icon'],
                                'items' => [],
                                'collapsed' => $section['collapsed'] ?? false
                            ];
                        }
                        
                        // Then add items to sections
                        foreach ($items as $item) {
                            $sectionId = $item['section'] ?? 'main';
                            
                            // If section doesn't exist in saved data, create it with defaults
                            if (!isset($sections[$sectionId])) {
                                $sections[$sectionId] = [
                                    'id' => $sectionId,
                                    'name' => isset($sectionMap[$sectionId]) ? $sectionMap[$sectionId]['name'] : ($defaultSectionNames[$sectionId] ?? ucfirst($sectionId)),
                                    'icon' => isset($sectionMap[$sectionId]) ? $sectionMap[$sectionId]['icon'] : ($defaultSectionIcons[$sectionId] ?? '📦'),
                                    'items' => [],
                                    'collapsed' => isset($sectionMap[$sectionId]) ? $sectionMap[$sectionId]['collapsed'] : false
                                ];
                            }
                            $itemCategory = $item['custom_category'] ?? 'other';
                            $sections[$sectionId]['items'][] = [
                                'id' => $item['id'],
                                'gear_id' => $item['gear_id'],
                                'name' => $item['custom_name'] ?? 'Unknown',
                                'weight_g' => $item['custom_weight'] ?? 0,
                                'quantity' => $item['quantity'] ?? 1,
                                'category' => $itemCategory,
                                'icon' => $categoryIcons[$itemCategory] ?? '📦',
                                'notes' => $item['notes'] ?? '',
                                'worn' => (bool)($item['worn'] ?? false),
                                'consumable' => (bool)($item['consumable'] ?? false)
                            ];
                        }
                        
                        // If no saved sections, ensure default sections exist
                        if (empty($savedSections)) {
                            if (!isset($sections['main'])) {
                                $sections['main'] = [
                                    'id' => 'main',
                                    'name' => 'Main Pack',
                                    'icon' => '🎒',
                                    'items' => [],
                                    'collapsed' => false
                                ];
                            }
                            if (!isset($sections['worn'])) {
                                $sections['worn'] = [
                                    'id' => 'worn',
                                    'name' => 'Worn Items',
                                    'icon' => '👕',
                                    'items' => [],
                                    'collapsed' => false
                                ];
                            }
                            if (!isset($sections['consumables'])) {
                                $sections['consumables'] = [
                                    'id' => 'consumables',
                                    'name' => 'Consumables',
                                    'icon' => '🍎',
                                    'items' => [],
                                    'collapsed' => false
                                ];
                            }
                        }
                        
                        $pack['sections'] = array_values($sections);
                        $pack['items'] = $items; // Also include raw items for compatibility
                    } else {
                        if (defined('BTT_DEBUG') && BTT_DEBUG) error_log("AJAX Handler: Backpack $id not found for user $user_id");
                    }
                    
                    ob_clean();
                    if ($pack) {
                        echo json_encode([
                            'success' => true,
                            'data' => $pack
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Backpack not found'
                        ]);
                    }
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
                
                // Save sections metadata as JSON
                $sectionsJson = null;
                if (isset($data['sections']) && is_array($data['sections'])) {
                    $sectionsMetadata = [];
                    foreach ($data['sections'] as $section) {
                        $sectionsMetadata[] = [
                            'id' => $section['id'],
                            'name' => $section['name'] ?? 'Unnamed Section',
                            'icon' => $section['icon'] ?? '📦',
                            'collapsed' => $section['collapsed'] ?? false
                        ];
                    }
                    $sectionsJson = json_encode($sectionsMetadata);
                }
                
                $stmt = $db->prepare("
                    INSERT INTO backpacks (user_id, name, description, capacity_l, weight_empty_g, type, sections, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                
                $stmt->execute([
                    $user_id,
                    $data['name'],
                    $data['description'] ?? '',
                    $data['capacity_l'] ?? 65,
                    $data['weight_empty_g'] ?? 0,
                    $data['type'] ?? 'custom',
                    $sectionsJson
                ]);
                
                $packId = $db->lastInsertId();
                
                // Save sections and items if provided
                if (isset($data['sections']) && is_array($data['sections'])) {
                    $position = 0;
                    foreach ($data['sections'] as $sectionIndex => $section) {
                        if (isset($section['items']) && is_array($section['items'])) {
                            foreach ($section['items'] as $itemIndex => $item) {
                                // Check if we need to add missing columns
                                try {
                                    $stmt = $db->prepare("
                                        INSERT INTO backpack_gear 
                                        (backpack_id, gear_id, custom_name, custom_weight, custom_category, quantity, section, position, custom_notes, worn, consumable) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    // Properly validate and handle gear_id (same logic as PUT)
                                    $gearId = null;
                                    if (!(isset($item['is_custom']) && $item['is_custom']) && isset($item['gear_id'])) {
                                        $rawGearId = $item['gear_id'];
                                        
                                        // Handle different gear_id formats
                                        if (is_numeric($rawGearId) && $rawGearId > 0) {
                                            // Valid numeric gear_id - verify it exists in database
                                            $checkStmt = $db->prepare("SELECT id FROM gear_items WHERE id = ?");
                                            $checkStmt->execute([$rawGearId]);
                                            if ($checkStmt->fetch()) {
                                                $gearId = (int)$rawGearId;
                                            }
                                        } elseif (is_string($rawGearId) && !empty($rawGearId)) {
                                            // String gear_id (like "def-def-clothing-baselayer-bottom")
                                            // These are template/default items that don't exist in gear_items table
                                            // Treat them as custom items instead
                                            $gearId = null;
                                            $item['is_custom'] = true;
                                            error_log("Converting template gear_id '$rawGearId' to custom item: " . ($item['name'] ?? 'Unknown'));
                                        }
                                    }
                                    
                                    $stmt->execute([
                                        $packId,
                                        $gearId,
                                        $item['name'] ?? 'Unknown',
                                        $item['weight_g'] ?? 0,
                                        $item['category'] ?? 'other',
                                        $item['quantity'] ?? 1,
                                        $section['id'] ?? 'main',
                                        $position++,
                                        $item['custom_notes'] ?? '',
                                        isset($item['worn']) && $item['worn'] ? 1 : 0,
                                        isset($item['consumable']) && $item['consumable'] ? 1 : 0
                                    ]);
                                } catch (PDOException $e) {
                                    // If columns are missing, try without them
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
                                        $position++
                                    ]);
                                }
                            }
                        }
                    }
                }
                
                // Get stats for the created pack
                $stmt = $db->prepare("
                    SELECT SUM(custom_weight * quantity) as total_weight, COUNT(*) as item_count, SUM(quantity) as total_quantity
                    FROM backpack_gear 
                    WHERE backpack_id = ?
                ");
                $stmt->execute([$packId]);
                $packStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get detailed section counts
                $stmt = $db->prepare("
                    SELECT section, COUNT(*) as item_types, SUM(quantity) as total_quantity
                    FROM backpack_gear 
                    WHERE backpack_id = ?
                    GROUP BY section
                    ORDER BY section
                ");
                $stmt->execute([$packId]);
                $sectionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Check for achievements
                $achievementContext = [
                    'action' => 'backpack_created',
                    'pack_id' => $packId,
                    'pack_weight' => $packStats['total_weight'] / 1000,
                    'item_count' => $packStats['item_count']
                ];
                $earnedAchievements = check_achievements_safe($user_id, $achievementContext, $db);
                
                // Return created pack
                $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ?");
                $stmt->execute([$packId]);
                $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack created successfully',
                    'stats' => [
                        'total_items' => (int)$packStats['item_count'],
                        'total_quantity' => (int)$packStats['total_quantity'],
                        'total_weight' => (float)$packStats['total_weight'],
                        'sections' => $sectionStats
                    ],
                    'achievements' => $earnedAchievements
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
                
                // Save sections metadata as JSON
                $sectionsJson = null;
                if (isset($data['sections']) && is_array($data['sections'])) {
                    $sectionsMetadata = [];
                    foreach ($data['sections'] as $section) {
                        $sectionsMetadata[] = [
                            'id' => $section['id'],
                            'name' => $section['name'] ?? 'Unnamed Section',
                            'icon' => $section['icon'] ?? '📦',
                            'collapsed' => $section['collapsed'] ?? false
                        ];
                    }
                    $sectionsJson = json_encode($sectionsMetadata);
                }
                
                $stmt = $db->prepare("
                    UPDATE backpacks 
                    SET name = ?, description = ?, sections = ?, updated_at = datetime('now')
                    WHERE id = ? AND user_id = ?
                ");
                
                $stmt->execute([
                    $data['name'] ?? '',
                    $data['description'] ?? '',
                    $sectionsJson,
                    $id,
                    $user_id
                ]);
                
                // Clear existing items
                $db->prepare("DELETE FROM backpack_gear WHERE backpack_id = ?")->execute([$id]);
                
                // Save new items
                if (isset($data['sections']) && is_array($data['sections'])) {
                    $position = 0;
                    foreach ($data['sections'] as $sectionIndex => $section) {
                        if (isset($section['items']) && is_array($section['items'])) {
                            foreach ($section['items'] as $itemIndex => $item) {
                                // Properly validate and handle gear_id BEFORE any database operations
                                $gearId = null;
                                if (!(isset($item['is_custom']) && $item['is_custom']) && isset($item['gear_id'])) {
                                    $rawGearId = $item['gear_id'];
                                    
                                    // Handle different gear_id formats
                                    if (is_numeric($rawGearId) && $rawGearId > 0) {
                                        // Valid numeric gear_id - verify it exists in database
                                        $checkStmt = $db->prepare("SELECT id FROM gear_items WHERE id = ?");
                                        $checkStmt->execute([$rawGearId]);
                                        if ($checkStmt->fetch()) {
                                            $gearId = (int)$rawGearId;
                                        }
                                    } elseif (is_string($rawGearId) && !empty($rawGearId)) {
                                        // String gear_id (like "def-def-clothing-baselayer-bottom")
                                        // These are template/default items that don't exist in gear_items table
                                        // Treat them as custom items instead
                                        $gearId = null;
                                        $item['is_custom'] = true;
                                        error_log("Converting template gear_id '$rawGearId' to custom item: " . ($item['name'] ?? 'Unknown'));
                                    }
                                }
                                
                                try {
                                    // Try with all columns first
                                    $stmt = $db->prepare("
                                        INSERT INTO backpack_gear 
                                        (backpack_id, gear_id, custom_name, custom_weight, custom_category, quantity, section, position, custom_notes, worn, consumable) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    
                                    $stmt->execute([
                                        $id,
                                        $gearId,
                                        $item['name'] ?? 'Unknown',
                                        $item['weight_g'] ?? 0,
                                        $item['category'] ?? 'other',
                                        $item['quantity'] ?? 1,
                                        $section['id'] ?? 'main',
                                        $position++,
                                        $item['custom_notes'] ?? '',
                                        isset($item['worn']) && $item['worn'] ? 1 : 0,
                                        isset($item['consumable']) && $item['consumable'] ? 1 : 0
                                    ]);
                                } catch (PDOException $e) {
                                    // Fallback if columns don't exist - reuse the same gear_id logic
                                    $stmt = $db->prepare("
                                        INSERT INTO backpack_gear 
                                        (backpack_id, gear_id, custom_name, custom_weight, custom_category, quantity, section, position) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([
                                        $id,
                                        $gearId, // Use the already validated gear_id
                                        $item['name'] ?? 'Unknown',
                                        $item['weight_g'] ?? 0,
                                        $item['category'] ?? 'other',
                                        $item['quantity'] ?? 1,
                                        $section['id'] ?? 'main',
                                        $position++
                                    ]);
                                }
                            }
                        }
                    }
                }
                
                // Calculate pack weight and check achievements
                $stmt = $db->prepare("
                    SELECT SUM(custom_weight * quantity) as total_weight, COUNT(*) as item_count, SUM(quantity) as total_quantity
                    FROM backpack_gear 
                    WHERE backpack_id = ?
                ");
                $stmt->execute([$id]);
                $packStats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get detailed section counts for confirmation
                $stmt = $db->prepare("
                    SELECT section, COUNT(*) as item_types, SUM(quantity) as total_quantity
                    FROM backpack_gear 
                    WHERE backpack_id = ?
                    GROUP BY section
                    ORDER BY section
                ");
                $stmt->execute([$id]);
                $sectionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $achievementContext = [
                    'action' => 'pack_updated',
                    'pack_id' => $id,
                    'pack_weight' => $packStats['total_weight'] / 1000, // Convert to kg
                    'item_count' => $packStats['item_count']
                ];
                $earnedAchievements = check_achievements_safe($user_id, $achievementContext, $db);
                
                // Return updated pack
                $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ?");
                $stmt->execute([$id]);
                $pack = $stmt->fetch(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => $pack,
                    'message' => 'Backpack updated successfully',
                    'stats' => [
                        'total_items' => (int)$packStats['item_count'],
                        'total_quantity' => (int)$packStats['total_quantity'],
                        'total_weight' => (float)$packStats['total_weight'],
                        'sections' => $sectionStats
                    ],
                    'achievements' => $earnedAchievements
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
    
    // Handle individual backpack gear items
    if ($route === 'backpack-gear') {
        if ($method === 'POST') {
            // Add item to backpack
            $backpack_id = $_POST['backpack_id'] ?? null;
            $custom_name = $_POST['custom_name'] ?? '';
            $custom_weight = intval($_POST['custom_weight'] ?? 0);
            $custom_category = $_POST['custom_category'] ?? 'other';
            $quantity = intval($_POST['quantity'] ?? 1);
            $section = $_POST['section'] ?? 'main';
            $gear_id = $_POST['gear_id'] ?? null;
            $notes = $_POST['notes'] ?? '';
            
            if (!$backpack_id || !$custom_name) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            // Verify backpack belongs to user
            $stmt = $db->prepare("SELECT id FROM backpacks WHERE id = ? AND user_id = ?");
            $stmt->execute([$backpack_id, $user_id]);
            if (!$stmt->fetch()) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Backpack not found']);
                exit;
            }
            
            // Insert item
            $stmt = $db->prepare("
                INSERT INTO backpack_gear (backpack_id, custom_name, custom_weight, custom_category, quantity, section, gear_id, custom_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // Validate gear_id exists if provided
            if ($gear_id !== null && $gear_id !== '') {
                try {
                    $checkStmt = $db->prepare("SELECT id FROM gear_items WHERE id = ?");
                    $checkStmt->execute([$gear_id]);
                    if (!$checkStmt->fetch()) {
                        error_log("Invalid gear_id: $gear_id for custom item: $custom_name");
                        $gear_id = null; // Set to null if doesn't exist
                    }
                } catch (Exception $e) {
                    error_log("Error validating custom gear_id $gear_id: " . $e->getMessage());
                    $gear_id = null; // Set to null on error
                }
            } else {
                $gear_id = null;
            }
            
            $stmt->execute([$backpack_id, $custom_name, $custom_weight, $custom_category, $quantity, $section, $gear_id, $notes ?? '']);
            
            // Update backpack's updated_at timestamp
            $stmt = $db->prepare("UPDATE backpacks SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$backpack_id]);
            
            ob_clean();
            echo json_encode(['success' => true, 'item_id' => $db->lastInsertId()]);
            exit;
            
        } elseif ($method === 'DELETE') {
            // Remove item from backpack
            $item_id = $_GET['item_id'] ?? null;
            $backpack_id = $_GET['backpack_id'] ?? null;
            
            if (!$item_id || !$backpack_id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Missing item_id or backpack_id']);
                exit;
            }
            
            // Verify backpack belongs to user and item exists
            $stmt = $db->prepare("
                DELETE FROM backpack_gear 
                WHERE id = ? AND backpack_id IN (SELECT id FROM backpacks WHERE id = ? AND user_id = ?)
            ");
            $stmt->execute([$item_id, $backpack_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update backpack's updated_at timestamp
                $stmt = $db->prepare("UPDATE backpacks SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$backpack_id]);
                
                ob_clean();
                echo json_encode(['success' => true]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Item not found']);
            }
            exit;
        }
    }
    
    // Handle direct action-based requests (for standalone pages)
    $action = $_POST['action'] ?? null;
    if ($action) {
        switch ($action) {
            case 'create_trip':
                handleCreateTripAction($db, $user_id);
                break;
                
            case 'update_trip':
                handleUpdateTripAction($db, $user_id);
                break;
            
            default:
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Unknown action: ' . $action
                ]);
                exit;
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
