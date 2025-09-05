<?php
/**
 * Achievement API Routes
 * 
 * Handles all achievement-related API endpoints including checking,
 * awarding, dismissing, and retrieving achievement data.
 */

// Ensure clean output - no HTML errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output buffer to catch any warnings
ob_start();

require_once dirname(__DIR__, 2) . '/app/config.php';
require_once dirname(__DIR__, 2) . '/app/models/Achievement.php';

use BTT\Models\Achievement;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header early
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Initialize database connection
try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Initialize Achievement model
$achievement = new Achievement($db, $userId);

// Ensure user has stats record
try {
    $achievement->initializeUserStats();
} catch (Exception $e) {
    error_log("Failed to initialize user stats: " . $e->getMessage());
}

// Route requests based on method and action
switch ($method) {
    case 'GET':
        handleGetRequests($achievement, $action);
        break;
        
    case 'POST':
        handlePostRequests($achievement, $action, $db);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

/**
 * Handle GET requests
 */
function handleGetRequests($achievement, $action) {
    switch ($action) {
        case 'unshown':
            // Get all unshown achievements for the user
            $unshownAchievements = $achievement->getUnshownAchievements();
            sendJsonResponse([
                'achievements' => $unshownAchievements,
                'count' => count($unshownAchievements)
            ]);
            break;
            
        case 'gallery':
            // Get user's achievement gallery
            $gallery = $achievement->getUserAchievements();
            sendJsonResponse($gallery);
            break;
            
        case 'stats':
            // Get user's achievement stats
            $stmt = $GLOBALS['db']->prepare("
                SELECT * FROM user_stats WHERE user_id = ?
            ");
            $stmt->execute([$GLOBALS['userId']);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            sendJsonResponse(['stats' => $stats]);
            break;
            
        case 'definitions':
            // Get all achievement definitions
            $stmt = $GLOBALS['db']->query("
                SELECT * FROM achievement_definitions 
                WHERE active = 1 
                ORDER BY category, display_order
            ");
            $definitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendJsonResponse(['achievements' => $definitions]);
            break;
            
        default:
            // Return user's earned achievements by default
            $gallery = $achievement->getUserAchievements();
            sendJsonResponse($gallery['earned'] ?? []);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequests($achievement, $action, $db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'check':
            // Check for new achievements based on context
            if (empty($input['context'])) {
                sendJsonResponse(['error' => 'Context required'], 400);
                return;
            }
            
            try {
                $earnedAchievements = $achievement->checkAchievements($input['context']);
                
                // Get progress updates for remaining achievements
                $progressUpdates = getProgressUpdates($db, $GLOBALS['userId'], $input['context']);
                
                sendJsonResponse([
                    'earned' => $earnedAchievements,
                    'progress' => $progressUpdates
                ]);
                
            } catch (Exception $e) {
                error_log("Achievement check error: " . $e->getMessage());
                sendJsonResponse(['error' => 'Failed to check achievements'], 500);
            }
            break;
            
        case 'shown':
            // Mark achievements as shown
            if (empty($input['achievement_ids'])) {
                sendJsonResponse(['error' => 'Achievement IDs required'], 400);
                return;
            }
            
            $achievement->markAsShown($input['achievement_ids']);
            sendJsonResponse(['success' => true]);
            break;
            
        case 'trigger':
            // Manually trigger achievement check (for specific events)
            $event = $input['event'] ?? '';
            $data = $input['data'] ?? [];
            
            $context = array_merge(['action' => $event], $data);
            $earnedAchievements = $achievement->checkAchievements($context);
            
            sendJsonResponse([
                'earned' => $earnedAchievements,
                'event' => $event
            ]);
            break;
            
        case 'progress':
            // Update achievement progress
            if (empty($input['achievement_code']) || empty($input['metric'])) {
                sendJsonResponse(['error' => 'Achievement code and metric required'], 400);
                return;
            }
            
            updateAchievementProgress(
                $db,
                $GLOBALS['userId'],
                $input['achievement_code'],
                $input['metric'],
                $input['value'] ?? 1
            );
            
            sendJsonResponse(['success' => true]);
            break;
            
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Get progress updates for achievements
 */
function getProgressUpdates($db, $userId, $context) {
    $progress = [];
    
    // Get achievements that track progress
    $stmt = $db->prepare("
        SELECT ad.*, ap.metric_value as current_value
        FROM achievement_definitions ad
        LEFT JOIN user_achievements ua ON ad.id = ua.achievement_id AND ua.user_id = ?
        LEFT JOIN achievement_progress ap ON ad.id = ap.achievement_id AND ap.user_id = ?
        WHERE ua.id IS NULL AND ad.active = 1 AND ad.trigger_type IN ('counter', 'threshold')
    ");
    $stmt->execute([$userId, $userId]);
    $trackableAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trackableAchievements as $ach) {
        $config = json_decode($ach['trigger_config'], true);
        $threshold = $config['threshold'] ?? 0;
        $currentValue = $ach['current_value'] ?? 0;
        
        // Update based on context if relevant
        if (isset($config['stat'])) {
            $stmt = $db->prepare("SELECT {$config['stat']} FROM user_stats WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $currentValue = $result[$config['stat']] ?? 0;
            }
        }
        
        if ($threshold > 0) {
            $progress[] = [
                'code' => $ach['code'],
                'name' => $ach['name'],
                'current' => $currentValue,
                'target' => $threshold,
                'percentage' => min(100, round(($currentValue / $threshold) * 100, 1))
            ];
        }
    }
    
    return $progress;
}

/**
 * Update achievement progress for multi-step achievements
 */
function updateAchievementProgress($db, $userId, $achievementCode, $metric, $value) {
    // Get achievement ID
    $stmt = $db->prepare("SELECT id FROM achievement_definitions WHERE code = ?");
    $stmt->execute([$achievementCode]);
    $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$achievement) {
        throw new Exception("Achievement not found: $achievementCode");
    }
    
    // Update or insert progress
    $stmt = $db->prepare("
        INSERT INTO achievement_progress (user_id, achievement_id, metric_key, metric_value)
        VALUES (?, ?, ?, ?)
        ON CONFLICT(user_id, achievement_id, metric_key) 
        DO UPDATE SET metric_value = metric_value + ?, updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([
        $userId,
        $achievement['id'],
        $metric,
        $value,
        $value
    ]);
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    ob_end_clean(); // Clean any buffered output
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}