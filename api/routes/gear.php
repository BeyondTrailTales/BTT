<?php
/**
 * Gear API Routes V2
 * 
 * Manages both default and custom gear with user preferences
 * @version 2.0.0
 */

// Include helper functions and models
require_once __DIR__ . '/backpacks_helpers.php';
require_once dirname(__DIR__) . '/classes/Response.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Gear.php';
require_once dirname(dirname(__DIR__)) . '/app/models/UserGear.php';
require_once dirname(dirname(__DIR__)) . '/app/models/UserGearPrefs.php';

use App\Models\Gear;
use App\Models\UserGear;
use App\Models\UserGearPrefs;

// Helper function to get request data
if (!function_exists('get_request_data')) {
    function get_request_data() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not JSON, try to get from $_POST
            return $_POST;
        }
        
        return $data;
    }
}

function handleGearRoute($method, $path, $db = null) {
    // Parse the path for sub-routes
    $segments = array_filter(explode('/', $path));
    $route = $segments[0] ?? 'gear';
    $id = $segments[1] ?? null;
    $action = $segments[2] ?? null;
    $subId = $segments[3] ?? null;
    
    // Get user ID from session or use default for testing
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? 1; // Default to user 1 for testing
    
    // Handle preferences routes
    if ($id === 'prefs') {
        handlePrefsRoute($method, $userId, $db);
        return;
    }
    
    // Handle default gear hide/show routes
    if ($id === 'default' && $action) {
        handleDefaultGearRoute($method, $subId, $action, $userId, $db);
        return;
    }
    
    // Main gear CRUD routes
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
            
        case 'PATCH':
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
            Response::methodNotAllowed();
    }
}

/**
 * Get all gear items (merged default + custom based on preferences)
 */
function getAllGear($userId, $db) {
    try {
        // Get user preferences
        $prefsModel = new UserGearPrefs($db);
        $prefs = $prefsModel->getPreferences($userId);
        
        // Initialize result array
        $allGear = [];
        
        // Get view mode from query params or use user preference
        $viewMode = $_GET['view'] ?? $prefs['view_mode'] ?? 'both';
        
        // Get filters from query params
        $filters = [
            'category' => $_GET['category'] ?? null,
            'search' => $_GET['search'] ?? null,
            'min_weight' => isset($_GET['minWeight']) ? floatval($_GET['minWeight']) : null,
            'max_weight' => isset($_GET['maxWeight']) ? floatval($_GET['maxWeight']) : null,
            'tags' => isset($_GET['tags']) ? explode(',', $_GET['tags']) : null
        ];
        
        // Handle hidden items view
        if ($viewMode === 'hidden') {
            // Only show hidden default items
            $hiddenIds = $prefs['hidden_default_ids'] ?? [];
            if (!empty($hiddenIds)) {
                $defaultGear = Gear::filter($filters);
                $defaultGear = array_filter($defaultGear, function($item) use ($hiddenIds) {
                    return in_array($item['id'], $hiddenIds);
                });
                
                // Mark as default and hidden
                foreach ($defaultGear as &$item) {
                    $item['is_default'] = true;
                    $item['is_custom'] = false;
                    $item['is_hidden'] = true;
                    $item['qty'] = $item['qty_default'] ?? 1;
                }
                
                $allGear = array_merge($allGear, $defaultGear);
            }
        }
        // Include default gear if needed (not hidden)
        elseif ($viewMode === 'default' || $viewMode === 'both') {
            $defaultGear = Gear::filter($filters);
            
            // Filter out hidden items
            $hiddenIds = $prefs['hidden_default_ids'] ?? [];
            $defaultGear = array_filter($defaultGear, function($item) use ($hiddenIds) {
                return !in_array($item['id'], $hiddenIds);
            });
            
            // Mark as default and add to results
            foreach ($defaultGear as &$item) {
                $item['is_default'] = true;
                $item['is_custom'] = false;
                $item['is_hidden'] = false;
                $item['qty'] = $item['qty_default'] ?? 1;
            }
            
            $allGear = array_merge($allGear, $defaultGear);
        }
        
        // Include custom gear if needed
        if ($viewMode === 'custom' || $viewMode === 'both') {
            $userGearModel = new UserGear($db);
            $customGear = $userGearModel->getUserGear($userId);
            
            // Apply filters to custom gear
            if (!empty($filters['category'])) {
                $customGear = array_filter($customGear, function($item) use ($filters) {
                    return $item['category'] === $filters['category'];
                });
            }
            
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $customGear = array_filter($customGear, function($item) use ($search) {
                    $searchIn = strtolower(
                        $item['name'] . ' ' .
                        $item['category'] . ' ' .
                        implode(' ', $item['tags'] ?? []) . ' ' .
                        ($item['notes'] ?? '')
                    );
                    return strpos($searchIn, $search) !== false;
                });
            }
            
            if ($filters['min_weight'] !== null) {
                $customGear = array_filter($customGear, function($item) use ($filters) {
                    return $item['weight_g'] >= $filters['min_weight'];
                });
            }
            
            if ($filters['max_weight'] !== null) {
                $customGear = array_filter($customGear, function($item) use ($filters) {
                    return $item['weight_g'] <= $filters['max_weight'];
                });
            }
            
            $allGear = array_merge($allGear, array_values($customGear));
        }
        
        // Sort results
        $sort = $_GET['sort'] ?? $prefs['last_sort'] ?? 'name';
        $dir = $_GET['dir'] ?? 'asc';
        
        usort($allGear, function($a, $b) use ($sort, $dir) {
            $result = 0;
            switch ($sort) {
                case 'weight':
                    $result = $a['weight_g'] <=> $b['weight_g'];
                    break;
                case 'category':
                    $result = strcmp($a['category'], $b['category']);
                    break;
                case 'name':
                default:
                    $result = strcmp($a['name'], $b['name']);
                    break;
            }
            return $dir === 'desc' ? -$result : $result;
        });
        
        // Calculate stats
        $stats = [
            'total_items' => count($allGear),
            'total_weight' => array_sum(array_map(function($item) {
                return $item['weight_g'] * ($item['qty'] ?? 1);
            }, $allGear)),
            'view_mode' => $viewMode
        ];
        
        Response::success([
            'items' => array_values($allGear),
            'stats' => $stats,
            'preferences' => [
                'view_mode' => $viewMode,
                'preferred_units' => $prefs['preferred_units'] ?? 'g'
            ]
        ]);
    } catch (Exception $e) {
        Response::serverError('Failed to fetch gear: ' . $e->getMessage());
    }
}


/**
 * Handle preferences routes
 */
function handlePrefsRoute($method, $userId, $db) {
    $prefsModel = new UserGearPrefs($db);
    
    switch ($method) {
        case 'GET':
            $prefs = $prefsModel->getPreferences($userId);
            Response::success($prefs);
            break;
            
        case 'PUT':
        case 'PATCH':
            $data = get_request_data();
            $result = $prefsModel->updatePreferences($userId, $data);
            
            if ($result['success']) {
                Response::success($result['data']);
            } else {
                Response::error($result['error'], 400);
            }
            break;
            
        default:
            Response::methodNotAllowed();
    }
}

/**
 * Handle default gear hide/show routes
 */
function handleDefaultGearRoute($method, $itemId, $action, $userId, $db) {
    $prefsModel = new UserGearPrefs($db);
    
    if ($action === 'hide') {
        if ($method === 'POST') {
            $result = $prefsModel->hideDefaultItem($userId, $itemId);
            if ($result['success']) {
                Response::success($result['data'] ?? ['message' => 'Item hidden']);
            } else {
                Response::error($result['error'], 400);
            }
        } elseif ($method === 'DELETE') {
            $result = $prefsModel->showDefaultItem($userId, $itemId);
            if ($result['success']) {
                Response::success($result['data'] ?? ['message' => 'Item shown']);
            } else {
                Response::error($result['error'], 400);
            }
        } else {
            Response::methodNotAllowed();
        }
    } else {
        Response::notFound('Invalid action');
    }
}

/**
 * Create new gear item
 */
function createGearItem($userId, $db) {
    try {
        $data = get_request_data();
        $userGearModel = new UserGear($db);
        
        $result = $userGearModel->create($userId, $data);
        
        if ($result['success']) {
            Response::success($result['data'], 'Gear item created successfully');
        } else {
            Response::validationError($result['errors'] ?? ['error' => $result['error']]);
        }
    } catch (Exception $e) {
        Response::serverError('Failed to create gear item: ' . $e->getMessage());
    }
}

/**
 * Update existing gear item
 */
function updateGearItem($id, $userId, $db) {
    try {
        // Check if it's a default item (prevent editing)
        if (strpos($id, 'def-') === 0) {
            Response::error('Cannot edit default gear items', 403);
            return;
        }
        
        $data = get_request_data();
        $userGearModel = new UserGear($db);
        
        $result = $userGearModel->update($id, $userId, $data);
        
        if ($result['success']) {
            Response::success($result['data'], 'Gear item updated successfully');
        } else {
            Response::error($result['error'], 400);
        }
    } catch (Exception $e) {
        Response::serverError('Failed to update gear item: ' . $e->getMessage());
    }
}

/**
 * Get single gear item by ID
 */
function getGearById($id, $userId, $db) {
    try {
        // Check if it's a default item
        if (strpos($id, 'def-') === 0) {
            $item = Gear::getById($id);
            if ($item) {
                $item['is_default'] = true;
                $item['is_custom'] = false;
                Response::success($item);
            } else {
                Response::notFound('Gear item not found');
            }
        } else {
            // It's a custom item
            $userGearModel = new UserGear($db);
            $item = $userGearModel->getById($id, $userId);
            
            if ($item) {
                Response::success($item);
            } else {
                Response::notFound('Gear item not found');
            }
        }
    } catch (Exception $e) {
        Response::serverError('Failed to fetch gear item: ' . $e->getMessage());
    }
}

/**
 * Delete gear item
 */
function deleteGearItem($id, $userId, $db) {
    try {
        // Check if it's a default item (prevent deletion)
        if (strpos($id, 'def-') === 0) {
            Response::error('Cannot delete default gear items. You can hide them instead.', 403);
            return;
        }
        
        $userGearModel = new UserGear($db);
        $permanent = isset($_GET['permanent']) && $_GET['permanent'] === 'true';
        
        $result = $userGearModel->delete($id, $userId, $permanent);
        
        if ($result['success']) {
            Response::success(null, 'Gear item deleted successfully');
        } else {
            Response::error($result['error'], 400);
        }
    } catch (Exception $e) {
        Response::serverError('Failed to delete gear item: ' . $e->getMessage());
    }
}
?>
