<?php
/**
 * Trips API Routes
 * 
 * Handles all trip-related API endpoints
 * Now with user scoping - users only see/manage their own trips
 */

// AuthService is already loaded via bootstrap in api/config.php
use App\Services\AuthService;

function handleTripsRoute($method, $id) {
    // Check authentication for all routes
    if (!AuthService::isAuthenticated()) {
        Response::unauthorized('Authentication required');
    }
    switch ($method) {
        case 'GET':
            if ($id) {
                getTripById($id);
            } else {
                getAllTrips();
            }
            break;
            
        case 'POST':
            createTrip();
            break;
            
        case 'PUT':
            if (!$id) {
                Response::error('Trip ID is required for update', 400);
            }
            updateTrip($id);
            break;
            
        case 'DELETE':
            if (!$id) {
                Response::error('Trip ID is required for delete', 400);
            }
            deleteTrip($id);
            break;
            
        default:
            Response::methodNotAllowed();
    }
}

/**
 * Get all trips for the current user with optional backpack filter
 */
function getAllTrips() {
    try {
        require_once dirname(__DIR__) . '/../app/classes/Validator.php';
        
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        $backpack_id = isset($_GET['backpack_id']) ? Validator::sanitizeInt($_GET['backpack_id'], 1) : null;
        
        if ($db->isSQLite()) {
            $sql = "
                SELECT t.*, b.name as backpack_name, b.base_weight 
                FROM trips t
                LEFT JOIN backpacks b ON t.backpack_id = b.id
                WHERE t.user_id = :user_id
            ";
            
            $params = ['user_id' => $user['id']];
            
            if ($backpack_id) {
                $sql .= " AND t.backpack_id = :backpack_id";
                $params['backpack_id'] = $backpack_id;
            }
            
            $sql .= " ORDER BY t.created_at DESC";
            
            $trips = $db->fetchAll($sql, $params);
        } else {
            // JSON fallback
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            
            // Add backpack data
            foreach ($trips as &$trip) {
                if (isset($trip['backpack_id'])) {
                    foreach ($backpacks as $backpack) {
                        if ($backpack['id'] == $trip['backpack_id']) {
                            $trip['backpack_name'] = $backpack['name'];
                            $trip['base_weight'] = $backpack['base_weight'];
                            break;
                        }
                    }
                }
            }
            
            // Filter by backpack if requested
            if ($backpack_id) {
                $trips = array_filter($trips, function($trip) use ($backpack_id) {
                    return isset($trip['backpack_id']) && $trip['backpack_id'] == $backpack_id;
                });
                $trips = array_values($trips);
            }
        }
        
        Response::success($trips);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch trips: ' . $e->getMessage());
    }
}

/**
 * Get single trip by ID (only if owned by current user)
 */
function getTripById($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        
        if ($db->isSQLite()) {
            $sql = "
                SELECT t.*, b.name as backpack_name, b.base_weight 
                FROM trips t
                LEFT JOIN backpacks b ON t.backpack_id = b.id
                WHERE t.id = :id AND t.user_id = :user_id
            ";
            
            $trip = $db->fetchOne($sql, ['id' => $id, 'user_id' => $user['id']]);
        } else {
            // JSON fallback
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            $trip = null;
            
            foreach ($trips as $t) {
                if ($t['id'] == $id) {
                    $trip = $t;
                    
                    // Add backpack data
                    if (isset($trip['backpack_id'])) {
                        $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
                        foreach ($backpacks as $backpack) {
                            if ($backpack['id'] == $trip['backpack_id']) {
                                $trip['backpack_name'] = $backpack['name'];
                                $trip['base_weight'] = $backpack['base_weight'];
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }
        
        if (!$trip) {
            Response::notFound('Trip not found');
        }
        
        error_log("\n=== GET TRIP BY ID DEBUG ===");
        error_log("Trip ID: $id");
        error_log("Trip photo_path: " . ($trip['photo_path'] ?? 'NULL'));
        error_log("Trip photo_alt_text: " . ($trip['photo_alt_text'] ?? 'NULL'));
        error_log("Full trip data: " . print_r($trip, true));
        error_log("=== GET TRIP BY ID DEBUG END ===\n");
        
        Response::success($trip);
        
    } catch (Exception $e) {
        Response::serverError('Failed to fetch trip: ' . $e->getMessage());
    }
}

/**
 * Create new trip with optional photo upload
 */
function createTrip() {
    try {
        require_once dirname(__DIR__) . '/../app/classes/Validator.php';
        
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $data = get_request_data();
        
        // Validate required fields
        $errors = Validator::validateRequired($data, ['title']);
        if (!empty($errors)) {
            Response::validationError($errors);
        }
        
        // Handle photo upload
        $photo_path = null;
        $photo_alt_text = null;
        
        // DEBUG: Log $_FILES contents
        error_log("DEBUG createTrip: _FILES = " . print_r($_FILES, true));
        error_log("DEBUG createTrip: _POST = " . print_r($_POST, true));
        error_log("DEBUG createTrip: data = " . print_r($data, true));
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Validate alt text is provided with photo
            if (empty($data['photo_alt_text'])) {
                Response::validationError(['photo_alt_text' => 'Alt text is required when uploading a photo (ADA compliance)']);
            }
            
            $upload_result = handlePhotoUpload($_FILES['photo']);
            if ($upload_result['success']) {
                $photo_path = $upload_result['path'];
                $photo_alt_text = trim($data['photo_alt_text']);
            } else {
                Response::validationError(['photo' => $upload_result['error']]);
            }
        }
        
        // Prepare trip data with backpacker fields - with proper validation
        $trip_data = [
            'user_id' => $user['id'],  // Set the user_id for the new trip
            'title' => Validator::sanitizeString($data['title'], 255),
            'location' => Validator::sanitizeString($data['location'] ?? null, 255),
            'start_date' => Validator::validateDate($data['start_date'] ?? null),
            'end_date' => Validator::validateDate($data['end_date'] ?? null),
            'description' => Validator::sanitizeString($data['description'] ?? null, 5000),
            'photo_path' => $photo_path,
            'photo_alt_text' => Validator::sanitizeString($photo_alt_text, 500),
            'backpack_id' => Validator::sanitizeInt($data['backpack_id'] ?? null, 1),
            
            // Note: default_image fields are deprecated, using photo upload instead
            
            // Backpacker specific fields
            'distance' => isset($data['distance']) ? floatval($data['distance']) : 0,
            'distance_unit' => isset($data['distance_unit']) ? $data['distance_unit'] : 'miles',
            'elevation_gain' => isset($data['elevation_gain']) ? floatval($data['elevation_gain']) : 0,
            'difficulty' => isset($data['difficulty']) ? $data['difficulty'] : null,
            'trip_type' => isset($data['trip_type']) ? $data['trip_type'] : null,
            'permit_required' => isset($data['permit_required']) ? (int)$data['permit_required'] : 0,
            'permit_info' => isset($data['permit_info']) ? trim($data['permit_info']) : null,
            'water_sources' => isset($data['water_sources']) ? trim($data['water_sources']) : null,
            'camping_type' => isset($data['camping_type']) ? $data['camping_type'] : null,
            'expected_weather' => isset($data['expected_weather']) ? trim($data['expected_weather']) : null,
            'trail_conditions' => isset($data['trail_conditions']) ? trim($data['trail_conditions']) : null,
            'emergency_contact' => isset($data['emergency_contact']) ? trim($data['emergency_contact']) : null,
            'trailhead_parking' => isset($data['trailhead_parking']) ? trim($data['trailhead_parking']) : null,
            
            // Note fields
            'pre_trip_notes' => isset($data['pre_trip_notes']) ? trim($data['pre_trip_notes']) : null,
            'post_trip_notes' => isset($data['post_trip_notes']) ? trim($data['post_trip_notes']) : null,
            'lessons_learned' => isset($data['lessons_learned']) ? trim($data['lessons_learned']) : null,
            
            // Additional fields
            'favorite' => isset($data['favorite']) ? (int)$data['favorite'] : 0,
            'completed' => isset($data['completed']) ? (int)$data['completed'] : 0,
            'permit_cost' => isset($data['permit_cost']) ? floatval($data['permit_cost']) : null,
            'parking_cost' => isset($data['parking_cost']) ? floatval($data['parking_cost']) : null,
            'cell_coverage' => isset($data['cell_coverage']) ? trim($data['cell_coverage']) : null,
            'crowd_level' => isset($data['crowd_level']) ? trim($data['crowd_level']) : null,
            
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate backpack exists if provided
        if ($trip_data['backpack_id']) {
            if (!backpackExists($trip_data['backpack_id'])) {
                Response::validationError(['backpack_id' => 'Invalid backpack ID']);
            }
        }
        
        // Insert trip
        $db = Database::getInstance();
        $trip_id = $db->insert('trips', $trip_data);
        
        // Fetch created trip
        getTripById($trip_id);
        
    } catch (Exception $e) {
        Response::serverError('Failed to create trip: ' . $e->getMessage());
    }
}

/**
 * Update existing trip (only if owned by current user)
 */
function updateTrip($id) {
    error_log("\n=== UPDATE TRIP DEBUG START ===");
    error_log("Trip ID: $id");
    error_log("_FILES: " . print_r($_FILES, true));
    error_log("_POST: " . print_r($_POST, true));
    error_log("php://input: " . file_get_contents('php://input'));
    
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        
        // Check if trip exists and is owned by current user
        if ($db->isSQLite()) {
            $existing = $db->fetchOne(
                "SELECT * FROM trips WHERE id = :id AND user_id = :user_id", 
                ['id' => $id, 'user_id' => $user['id']]
            );
        } else {
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            $existing = null;
            foreach ($trips as $trip) {
                if ($trip['id'] == $id) {
                    $existing = $trip;
                    break;
                }
            }
        }
        
        if (!$existing) {
            Response::notFound('Trip not found');
        }
        
        $data = get_request_data();
        error_log("Parsed request data: " . print_r($data, true));
        $update_data = [];
        
        // Update only provided fields (including backpacker fields)
        $allowed_fields = [
            'title', 'location', 'start_date', 'end_date', 'description', 'backpack_id',
            'distance', 'distance_unit', 'elevation_gain', 'difficulty', 'trip_type',
            'permit_required', 'permit_info', 'water_sources', 'camping_type',
            'expected_weather', 'trail_conditions', 'emergency_contact', 'trailhead_parking',
            'pre_trip_notes', 'post_trip_notes', 'lessons_learned',  // Note fields
            'favorite', 'completed', 'permit_cost', 'parking_cost', 'cell_coverage', 'crowd_level'  // Additional fields
        ];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'backpack_id') {
                    // Handle backpack_id specially to avoid FK constraint issues
                    $value = $data[$field];
                    $update_data[$field] = ($value !== '' && $value != 0) ? intval($value) : null;
                } elseif ($field === 'permit_required' || $field === 'favorite' || $field === 'completed') {
                    $update_data[$field] = intval($data[$field]);
                } elseif ($field === 'distance' || $field === 'elevation_gain' || $field === 'permit_cost' || $field === 'parking_cost') {
                    $update_data[$field] = floatval($data[$field]);
                } else {
                    $update_data[$field] = trim($data[$field]);
                }
            }
        }
        
        // Handle photo removal - Enhanced debugging
        error_log("=== PHOTO REMOVAL DEBUG ===");
        error_log("remove_photo in data: " . (isset($data['remove_photo']) ? $data['remove_photo'] : 'NOT SET'));
        error_log("remove_photo type: " . gettype($data['remove_photo'] ?? null));
        error_log("Comparison result: " . ($data['remove_photo'] === '1' ? 'TRUE' : 'FALSE'));
        
        if (isset($data['remove_photo']) && $data['remove_photo'] === '1') {
            error_log("✅ Photo removal condition MET - processing removal");
            if ($existing['photo_path']) {
                error_log("Deleting existing photo file: " . $existing['photo_path']);
                deletePhotoFile($existing['photo_path']);
                $update_data['photo_path'] = null;
                $update_data['photo_alt_text'] = null;
                error_log("✅ Photo removal data prepared - photo_path=NULL, photo_alt_text=NULL");
            } else {
                error_log("ℹ️ No existing photo to remove");
            }
        } else {
            error_log("❌ Photo removal condition NOT MET - skipping removal");
        }
        error_log("=== PHOTO REMOVAL DEBUG END ==="); 
        
        // Handle photo upload/update
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            error_log("Photo file found! Processing upload...");
            
            // Validate alt text is provided with photo
            if (empty($data['photo_alt_text'])) {
                error_log("ERROR: Missing photo_alt_text");
                Response::validationError(['photo_alt_text' => 'Alt text is required when uploading a photo (ADA compliance)']);
            }
            
            error_log("Alt text provided: " . $data['photo_alt_text']);
            $upload_result = handlePhotoUpload($_FILES['photo']);
            error_log("Photo upload result: " . print_r($upload_result, true));
            
            if ($upload_result['success']) {
                // Delete old photo if exists
                if ($existing['photo_path']) {
                    error_log("Deleting old photo: " . $existing['photo_path']);
                    deletePhotoFile($existing['photo_path']);
                }
                
                $update_data['photo_path'] = $upload_result['path'];
                $update_data['photo_alt_text'] = trim($data['photo_alt_text']);
                error_log("Photo data to be saved: photo_path=" . $update_data['photo_path'] . ", photo_alt_text=" . $update_data['photo_alt_text']);
            } else {
                Response::validationError(['photo' => $upload_result['error']]);
            }
        } elseif (isset($data['photo_alt_text']) && $existing['photo_path']) {
            // Update alt text only
            $update_data['photo_alt_text'] = trim($data['photo_alt_text']);
        }
        
        // Validate backpack exists if provided
        if (isset($update_data['backpack_id']) && $update_data['backpack_id']) {
            if (!backpackExists($update_data['backpack_id'])) {
                Response::validationError(['backpack_id' => 'Invalid backpack ID']);
            }
        }
        
        if (empty($update_data)) {
            Response::error('No fields to update', 400);
        }
        
        $update_data['updated_at'] = date('Y-m-d H:i:s');
        
        error_log("Final update_data: " . print_r($update_data, true));
        error_log("Columns being updated: " . implode(', ', array_keys($update_data)));
        
        // Update trip
        $result = $db->update('trips', $update_data, 'id = :id', ['id' => $id]);
        error_log("Database update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        // Verify the update
        $verify = $db->fetchOne("SELECT photo_path, photo_alt_text FROM trips WHERE id = :id", ['id' => $id]);
        error_log("Verification after update - photo_path: " . ($verify['photo_path'] ?? 'NULL') . ", photo_alt_text: " . ($verify['photo_alt_text'] ?? 'NULL'));
        
        error_log("=== UPDATE TRIP DEBUG END ===\n");
        
        // Return updated trip
        getTripById($id);
        
    } catch (Exception $e) {
        Response::serverError('Failed to update trip: ' . $e->getMessage());
    }
}

/**
 * Delete trip and associated photo (only if owned by current user)
 */
function deleteTrip($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            Response::unauthorized('User not found');
        }
        
        $db = Database::getInstance();
        
        // Get trip to check for photo and ownership
        if ($db->isSQLite()) {
            $trip = $db->fetchOne(
                "SELECT photo_path FROM trips WHERE id = :id AND user_id = :user_id", 
                ['id' => $id, 'user_id' => $user['id']]
            );
        } else {
            $trips = json_decode(file_get_contents(BTT_JSON_PATH . '/trips.json'), true) ?? [];
            $trip = null;
            foreach ($trips as $t) {
                if ($t['id'] == $id) {
                    $trip = $t;
                    break;
                }
            }
        }
        
        if (!$trip) {
            Response::notFound('Trip not found');
        }
        
        // Delete photo file if exists
        if ($trip['photo_path']) {
            deletePhotoFile($trip['photo_path']);
        }
        
        // Delete trip from database
        $db->delete('trips', 'id = :id', ['id' => $id]);
        
        Response::success(null, 'Trip deleted successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to delete trip: ' . $e->getMessage());
    }
}

/**
 * Handle photo upload
 */
function handlePhotoUpload($file) {
    // Check file size (4MB max)
    if ($file['size'] > BTT_UPLOAD_MAX_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds 4MB limit'];
    }
    
    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, BTT_UPLOAD_ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, and PNG files are allowed'];
    }
    
    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, BTT_UPLOAD_ALLOWED_MIMES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $filename = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    $upload_path = BTT_UPLOAD_PATH . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Return relative path for storage
        return [
            'success' => true,
            'path' => 'assets/img/trips/' . $filename
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file'];
    }
}

/**
 * Delete photo file
 */
function deletePhotoFile($path) {
    if ($path && strpos($path, 'assets/img/trips/') === 0) {
        $full_path = BTT_ROOT . '/' . $path;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }
    }
}

/**
 * Check if backpack exists and belongs to current user
 */
function backpackExists($id) {
    try {
        // Get current user
        $user = AuthService::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $db = Database::getInstance();
        
        if ($db->isSQLite()) {
            $result = $db->fetchOne(
                "SELECT id FROM backpacks WHERE id = :id AND user_id = :user_id", 
                ['id' => $id, 'user_id' => $user['id']]
            );
            return $result !== false;
        } else {
            $backpacks = json_decode(file_get_contents(BTT_JSON_PATH . '/backpacks.json'), true) ?? [];
            foreach ($backpacks as $backpack) {
                if ($backpack['id'] == $id) {
                    return true;
                }
            }
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}
