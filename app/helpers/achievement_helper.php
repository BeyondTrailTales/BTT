<?php
/**
 * Achievement Helper
 * 
 * Safe wrapper functions for achievement system that prevent site breakage
 */

/**
 * Ensure user has a stats entry
 */
function ensure_user_stats($userId, $db) {
    try {
        // Check if user stats exist
        $stmt = $db->prepare("SELECT user_id FROM user_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            // Create initial stats entry
            $stmt = $db->prepare("
                INSERT INTO user_stats (user_id, updated_at) 
                VALUES (?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$userId]);
        }
    } catch (Exception $e) {
        error_log("Failed to ensure user stats: " . $e->getMessage());
    }
}

/**
 * Safely check and award achievements
 * Returns empty array on any error to prevent site breakage
 */
function check_achievements_safe($userId, $context, $db = null) {
    try {
        // Skip if no user ID
        if (!$userId) {
            return [];
        }
        
        // Check if achievement tables exist
        if (!achievement_tables_exist($db)) {
            return [];
        }
        
        // Get database connection
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Ensure user has stats entry
        ensure_user_stats($userId, $db);
        
        // Check if Achievement model exists
        $achievementModelPath = dirname(__DIR__) . '/models/Achievement.php';
        if (!file_exists($achievementModelPath)) {
            return [];
        }
        
        // Include and use Achievement model
        require_once $achievementModelPath;
        $achievement = new \BTT\Models\Achievement($db, $userId);
        
        // Check achievements
        $earned = $achievement->checkAchievements($context);
        
        return $earned ?: [];
        
    } catch (Exception $e) {
        // Log error but don't break the site
        error_log("Achievement system error: " . $e->getMessage());
        return [];
    }
}

/**
 * Safely get user achievements
 */
function get_user_achievements_safe($userId, $db = null) {
    try {
        if (!$userId || !achievement_tables_exist($db)) {
            return [];
        }
        
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
        }
        
        $stmt = $db->prepare("
            SELECT ad.*, ua.earned_at, ua.shown_at
            FROM user_achievements ua
            JOIN achievement_definitions ad ON ua.achievement_id = ad.id
            WHERE ua.user_id = ?
            ORDER BY ua.earned_at DESC
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
    } catch (Exception $e) {
        error_log("Achievement retrieval error: " . $e->getMessage());
        return [];
    }
}

/**
 * Safely get unshown achievements
 */
function get_unshown_achievements_safe($userId, $db = null) {
    try {
        if (!$userId || !achievement_tables_exist($db)) {
            return [];
        }
        
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
        }
        
        $stmt = $db->prepare("
            SELECT ad.*, ua.earned_at, ua.id as user_achievement_id
            FROM user_achievements ua
            JOIN achievement_definitions ad ON ua.achievement_id = ad.id
            WHERE ua.user_id = ? AND ua.shown_at IS NULL
            ORDER BY ua.earned_at ASC
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
    } catch (Exception $e) {
        error_log("Unshown achievement retrieval error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if achievement tables exist
 */
function achievement_tables_exist($db = null) {
    static $tablesExist = null;
    
    // Cache the result
    if ($tablesExist !== null) {
        return $tablesExist;
    }
    
    try {
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
        }
        
        // Check if achievement tables exist
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='achievement_definitions'");
        $tablesExist = ($stmt->fetch() !== false);
        
        return $tablesExist;
        
    } catch (Exception $e) {
        $tablesExist = false;
        return false;
    }
}

/**
 * Mark achievement as shown
 */
function mark_achievement_shown_safe($userAchievementId, $db = null) {
    try {
        if (!$userAchievementId || !achievement_tables_exist($db)) {
            return false;
        }
        
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
        }
        
        $stmt = $db->prepare("UPDATE user_achievements SET shown_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$userAchievementId]);
        
    } catch (Exception $e) {
        error_log("Achievement shown update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user level and XP
 */
function get_user_level_safe($userId, $db = null) {
    try {
        if (!$userId || !achievement_tables_exist($db)) {
            return ['level' => 1, 'xp' => 0, 'next_level_xp' => 100];
        }
        
        if (!$db) {
            require_once dirname(__DIR__) . '/config.php';
            $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
        }
        
        $stmt = $db->prepare("SELECT level, total_xp FROM user_stats WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats) {
            return ['level' => 1, 'xp' => 0, 'next_level_xp' => 100];
        }
        
        // Calculate XP for next level
        $nextLevelXp = calculate_level_xp($stats['level'] + 1);
        
        return [
            'level' => $stats['level'],
            'xp' => $stats['total_xp'],
            'next_level_xp' => $nextLevelXp
        ];
        
    } catch (Exception $e) {
        error_log("User level retrieval error: " . $e->getMessage());
        return ['level' => 1, 'xp' => 0, 'next_level_xp' => 100];
    }
}

/**
 * Calculate XP required for a level
 */
function calculate_level_xp($level) {
    // Simple exponential curve
    return floor(100 * pow(1.5, $level - 1));
}