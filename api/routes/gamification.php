<?php
/**
 * Gamification API Routes
 * Handles XP, levels, badges, and achievements
 */

// Include configuration
require_once dirname(__DIR__, 2) . '/app/config.php';

// Include the Gamification class
require_once dirname(__DIR__, 2) . '/app/classes/Gamification.php';

use BTT\Classes\Gamification;

// Get user ID from session or use default
session_start();
$userId = $_SESSION['user_id'] ?? 'default';

// Initialize gamification system
$gamification = new Gamification($userId);

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        handleGetRequests($gamification, $action);
        break;
        
    case 'POST':
        handlePostRequests($gamification, $action);
        break;
        
    default:
        sendJsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Handle GET requests
 */
function handleGetRequests($gamification, $action) {
    switch($action) {
        case 'status':
            // Get current user status
            $data = $gamification->getUserData();
            sendJsonResponse($data);
            break;
            
        case 'badges':
            // Get all badges with earned status
            $badges = $gamification->getAllBadges();
            sendJsonResponse(['badges' => $badges]);
            break;
            
        case 'leaderboard':
            // Get leaderboard (placeholder for now)
            sendJsonResponse([
                'leaderboard' => [
                    ['rank' => 1, 'name' => 'You', 'level' => $gamification->getUserData()['level'], 'xp' => $gamification->getUserData()['xp']]
                ]
            ]);
            break;
            
        default:
            // Return full status by default
            $data = $gamification->getUserData();
            sendJsonResponse($data);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequests($gamification, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch($action) {
        case 'award_xp':
            // Award XP for an action
            if (empty($input['action'])) {
                sendJsonResponse(['error' => 'Action required'], 400);
                return;
            }
            
            $result = $gamification->awardXP($input['action'], $input['amount'] ?? null);
            
            if ($result) {
                // Check if level up occurred
                if ($result['level_up']) {
                    $result['message'] = "Level up! You're now level {$result['new_level']}!";
                    $result['type'] = 'level_up';
                }
                sendJsonResponse($result);
            } else {
                sendJsonResponse(['error' => 'Failed to award XP'], 400);
            }
            break;
            
        case 'update_streak':
            // Update daily streak
            $streak = $gamification->updateStreak();
            sendJsonResponse([
                'streak_days' => $streak,
                'message' => $streak > 1 ? "Streak continues! {$streak} days!" : "Welcome back! Start your streak!"
            ]);
            break;
            
        case 'update_stats':
            // Update user stats
            if (empty($input['stat'])) {
                sendJsonResponse(['error' => 'Stat name required'], 400);
                return;
            }
            
            $newBadges = $gamification->updateStats(
                $input['stat'], 
                $input['value'] ?? 1,
                $input['increment'] ?? true
            );
            
            $response = ['success' => true];
            
            if (!empty($newBadges)) {
                $response['new_badges'] = $newBadges;
                $response['message'] = 'New badge earned: ' . $newBadges[0]['name'];
            }
            
            sendJsonResponse($response);
            break;
            
        case 'backpack_created':
            // Handle backpack creation
            $xpResult = $gamification->awardXP('create_backpack');
            $newBadges = $gamification->updateStats('backpack_count');
            
            // Check if this was their first backpack
            $stats = $gamification->getUserData()['stats'];
            if ($stats['backpack_count'] == 1) {
                $gamification->awardXP('first_backpack');
            }
            
            $response = ['xp' => $xpResult];
            if (!empty($newBadges)) {
                $response['new_badges'] = $newBadges;
            }
            
            sendJsonResponse($response);
            break;
            
        case 'trip_created':
            // Handle trip creation
            $xpResult = $gamification->awardXP('create_trip');
            $newBadges = $gamification->updateStats('trip_count');
            
            // Check if this was their first trip
            $stats = $gamification->getUserData()['stats'];
            if ($stats['trip_count'] == 1) {
                $gamification->awardXP('first_trip');
            }
            
            $response = ['xp' => $xpResult];
            if (!empty($newBadges)) {
                $response['new_badges'] = $newBadges;
            }
            
            sendJsonResponse($response);
            break;
            
        case 'reset':
            // Reset user data (for testing)
            if (\BTT_ENV === 'development') {
                $gamification->resetUserData();
                sendJsonResponse(['success' => true, 'message' => 'User data reset']);
            } else {
                sendJsonResponse(['error' => 'Not allowed in production'], 403);
            }
            break;
            
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
