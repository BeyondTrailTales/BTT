<?php
/**
 * Achievement Model
 * 
 * Handles all achievement-related operations including checking triggers,
 * awarding achievements, tracking progress, and preventing duplicates.
 */

namespace BTT\Models;

use PDO;
use PDOException;

class Achievement {
    private $db;
    private $userId;
    
    public function __construct($db, $userId = null) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    /**
     * Check if user has earned any new achievements based on context
     * 
     * @param array $context Array containing action and relevant data
     * @return array Array of newly earned achievements
     */
    public function checkAchievements($context) {
        $earnedAchievements = [];
        
        // Get all active achievements
        $stmt = $this->db->prepare("
            SELECT ad.*, ua.id as user_achievement_id
            FROM achievement_definitions ad
            LEFT JOIN user_achievements ua ON ad.id = ua.achievement_id AND ua.user_id = ?
            WHERE ad.active = 1 AND ua.id IS NULL
            ORDER BY ad.display_order
        ");
        $stmt->execute([$this->userId]);
        $availableAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update user stats based on context
        $this->updateUserStats($context);
        
        // Check each available achievement
        foreach ($availableAchievements as $achievement) {
            if ($this->checkAchievementTrigger($achievement, $context)) {
                // Award the achievement
                $this->awardAchievement($achievement);
                $earnedAchievements[] = $achievement;
            }
        }
        
        return $earnedAchievements;
    }
    
    /**
     * Check if an achievement's trigger conditions are met
     */
    private function checkAchievementTrigger($achievement, $context) {
        $triggerConfig = json_decode($achievement['trigger_config'], true);
        
        switch ($achievement['trigger_type']) {
            case 'counter':
                return $this->checkCounterTrigger($triggerConfig);
                
            case 'threshold':
                return $this->checkThresholdTrigger($triggerConfig);
                
            case 'specific':
                return $this->checkSpecificTrigger($triggerConfig, $context);
                
            case 'calculated':
                return $this->checkCalculatedTrigger($triggerConfig, $context);
                
            default:
                return false;
        }
    }
    
    /**
     * Check counter-based triggers (e.g., "complete 5 trips")
     */
    private function checkCounterTrigger($config) {
        $stat = $config['stat'] ?? null;
        $threshold = $config['threshold'] ?? 0;
        
        if (!$stat) return false;
        
        // Get current stat value
        $stmt = $this->db->prepare("SELECT $stat FROM user_stats WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result[$stat] >= $threshold;
    }
    
    /**
     * Check threshold-based triggers (e.g., "reach 100km total distance")
     */
    private function checkThresholdTrigger($config) {
        // Similar to counter but for cumulative values
        return $this->checkCounterTrigger($config);
    }
    
    /**
     * Check specific condition triggers (e.g., "pack under 4.5kg")
     */
    private function checkSpecificTrigger($config, $context) {
        $action = $context['action'] ?? '';
        
        switch ($action) {
            case 'backpack_saved':
                if (isset($config['max_weight_kg']) && isset($context['total_weight'])) {
                    return $context['total_weight'] <= $config['max_weight_kg'];
                }
                if (isset($config['min_weight_kg']) && isset($context['total_weight'])) {
                    return $context['total_weight'] >= $config['min_weight_kg'];
                }
                if (isset($config['max_items']) && isset($context['item_count'])) {
                    return $context['item_count'] <= $config['max_items'];
                }
                if (isset($config['sections_used']) && isset($context['sections_count'])) {
                    return $context['sections_count'] >= $config['sections_used'];
                }
                break;
                
            case 'trip_completed':
                if (isset($config['min_days']) && isset($context['duration_days'])) {
                    return $context['duration_days'] >= $config['min_days'];
                }
                break;
                
            case 'gear_added':
                if (isset($config['min_age_years']) && isset($context['purchase_date'])) {
                    $ageYears = (time() - strtotime($context['purchase_date'])) / (365 * 24 * 60 * 60);
                    return $ageYears >= $config['min_age_years'];
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Check calculated triggers (e.g., "trips in all seasons")
     */
    private function checkCalculatedTrigger($config, $context) {
        $type = $config['type'] ?? '';
        
        switch ($type) {
            case 'seasons':
                return $this->checkAllSeasonsCompleted();
                
            case 'countries':
                $threshold = $config['threshold'] ?? 0;
                return $this->getUniqueCountriesCount() >= $threshold;
                
            case 'brand_items':
                $threshold = $config['threshold'] ?? 0;
                return $this->getMaxBrandItemCount() >= $threshold;
                
            case 'weight_reduction':
                $amount = $config['amount_kg'] ?? 0;
                return $this->checkWeightReduction($amount);
                
            case 'all_weights_tracked':
                return $this->checkAllWeightsTracked();
        }
        
        return false;
    }
    
    /**
     * Award an achievement to the user
     */
    private function awardAchievement($achievement) {
        try {
            $this->db->beginTransaction();
            
            // Insert into user_achievements
            $stmt = $this->db->prepare("
                INSERT INTO user_achievements (user_id, achievement_id, metadata)
                VALUES (?, ?, ?)
            ");
            
            $metadata = json_encode([
                'awarded_at' => date('Y-m-d H:i:s'),
                'context' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            
            $stmt->execute([$this->userId, $achievement['id'], $metadata]);
            
            // Award XP
            if ($achievement['xp_reward'] > 0) {
                $this->awardXP($achievement['xp_reward']);
            }
            
            // Update achievement count
            $this->db->prepare("
                UPDATE user_stats 
                SET total_achievements = total_achievements + 1 
                WHERE user_id = ?
            ")->execute([$this->userId]);
            
            $this->db->commit();
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Failed to award achievement: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Award XP to the user
     */
    private function awardXP($amount) {
        $stmt = $this->db->prepare("
            UPDATE user_stats 
            SET total_xp = total_xp + ?,
                current_level = CASE 
                    WHEN total_xp + ? >= 18100 THEN 20
                    WHEN total_xp + ? >= 16250 THEN 19
                    WHEN total_xp + ? >= 14500 THEN 18
                    WHEN total_xp + ? >= 12850 THEN 17
                    WHEN total_xp + ? >= 11300 THEN 16
                    WHEN total_xp + ? >= 9850 THEN 15
                    WHEN total_xp + ? >= 8500 THEN 14
                    WHEN total_xp + ? >= 7250 THEN 13
                    WHEN total_xp + ? >= 6100 THEN 12
                    WHEN total_xp + ? >= 5050 THEN 11
                    WHEN total_xp + ? >= 4100 THEN 10
                    WHEN total_xp + ? >= 3250 THEN 9
                    WHEN total_xp + ? >= 2500 THEN 8
                    WHEN total_xp + ? >= 1850 THEN 7
                    WHEN total_xp + ? >= 1300 THEN 6
                    WHEN total_xp + ? >= 850 THEN 5
                    WHEN total_xp + ? >= 500 THEN 4
                    WHEN total_xp + ? >= 250 THEN 3
                    WHEN total_xp + ? >= 100 THEN 2
                    ELSE 1
                END
            WHERE user_id = ?
        ");
        
        $params = array_fill(0, 20, $amount);
        $params[] = $this->userId;
        
        $stmt->execute($params);
    }
    
    /**
     * Get unshown achievements for the user
     */
    public function getUnshownAchievements() {
        $stmt = $this->db->prepare("
            SELECT ad.*, ua.earned_at, ua.id as user_achievement_id
            FROM user_achievements ua
            JOIN achievement_definitions ad ON ua.achievement_id = ad.id
            WHERE ua.user_id = ? AND ua.shown_at IS NULL
            ORDER BY ua.earned_at DESC
        ");
        
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark achievements as shown
     */
    public function markAsShown($achievementIds) {
        if (empty($achievementIds)) return;
        
        $placeholders = str_repeat('?,', count($achievementIds) - 1) . '?';
        $stmt = $this->db->prepare("
            UPDATE user_achievements 
            SET shown_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND achievement_id IN ($placeholders)
        ");
        
        $params = array_merge([$this->userId], $achievementIds);
        $stmt->execute($params);
    }
    
    /**
     * Get user's achievement gallery
     */
    public function getUserAchievements() {
        // Get earned achievements
        $stmt = $this->db->prepare("
            SELECT ad.*, ua.earned_at, 1 as earned
            FROM user_achievements ua
            JOIN achievement_definitions ad ON ua.achievement_id = ad.id
            WHERE ua.user_id = ?
            ORDER BY ua.earned_at DESC
        ");
        $stmt->execute([$this->userId]);
        $earned = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available achievements
        $stmt = $this->db->prepare("
            SELECT ad.*, NULL as earned_at, 0 as earned
            FROM achievement_definitions ad
            LEFT JOIN user_achievements ua ON ad.id = ua.achievement_id AND ua.user_id = ?
            WHERE ua.id IS NULL AND ad.active = 1
            ORDER BY ad.category, ad.display_order
        ");
        $stmt->execute([$this->userId]);
        $available = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'earned' => $earned,
            'available' => $available,
            'stats' => [
                'total_earned' => count($earned),
                'total_available' => count($earned) + count($available),
                'completion_percentage' => round((count($earned) / (count($earned) + count($available))) * 100, 1)
            ]
        ];
    }
    
    /**
     * Update user stats based on context
     */
    private function updateUserStats($context) {
        $action = $context['action'] ?? '';
        
        switch ($action) {
            case 'trip_completed':
                $this->incrementStat('total_trips');
                if (isset($context['distance_km'])) {
                    $this->incrementStat('total_distance_km', $context['distance_km']);
                }
                if (isset($context['elevation_m'])) {
                    $this->incrementStat('total_elevation_m', $context['elevation_m']);
                }
                if (isset($context['duration_days']) && $context['duration_days'] > $this->getStat('longest_trip_days')) {
                    $this->setStat('longest_trip_days', $context['duration_days']);
                }
                break;
                
            case 'backpack_created':
                $this->incrementStat('total_backpacks');
                break;
                
            case 'backpack_saved':
                if (isset($context['total_weight'])) {
                    $lightest = $this->getStat('lightest_pack_kg');
                    $heaviest = $this->getStat('heaviest_pack_kg');
                    
                    if (!$lightest || $context['total_weight'] < $lightest) {
                        $this->setStat('lightest_pack_kg', $context['total_weight']);
                    }
                    if (!$heaviest || $context['total_weight'] > $heaviest) {
                        $this->setStat('heaviest_pack_kg', $context['total_weight']);
                    }
                }
                break;
                
            case 'gear_added':
                $this->incrementStat('total_gear_items');
                break;
                
            case 'daily_login':
                $this->updateStreak();
                break;
        }
        
        // Update last activity date
        $this->setStat('last_activity_date', date('Y-m-d'));
    }
    
    /**
     * Helper methods for stats
     */
    private function incrementStat($stat, $amount = 1) {
        // First ensure user stats exist
        $this->ensureUserStats();
        
        $stmt = $this->db->prepare("
            UPDATE user_stats 
            SET $stat = $stat + ?, updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ");
        $stmt->execute([$amount, $this->userId]);
    }
    
    private function setStat($stat, $value) {
        // First ensure user stats exist
        $this->ensureUserStats();
        
        $stmt = $this->db->prepare("
            UPDATE user_stats 
            SET $stat = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE user_id = ?
        ");
        $stmt->execute([$value, $this->userId]);
    }
    
    private function getStat($stat) {
        $stmt = $this->db->prepare("SELECT $stat FROM user_stats WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result[$stat] : null;
    }
    
    /**
     * Update login streak
     */
    private function updateStreak() {
        $lastActivity = $this->getStat('last_activity_date');
        $currentStreak = $this->getStat('current_streak_days') ?? 0;
        $longestStreak = $this->getStat('longest_streak_days') ?? 0;
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($lastActivity === $yesterday) {
            // Continue streak
            $currentStreak++;
        } elseif ($lastActivity !== $today) {
            // Reset streak
            $currentStreak = 1;
        }
        
        // Update longest streak if needed
        if ($currentStreak > $longestStreak) {
            $longestStreak = $currentStreak;
        }
        
        $this->setStat('current_streak_days', $currentStreak);
        $this->setStat('longest_streak_days', $longestStreak);
    }
    
    /**
     * Specific calculation methods for complex achievements
     */
    private function checkAllSeasonsCompleted() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN strftime('%m', start_date) IN ('12', '01', '02') THEN 'winter'
                    WHEN strftime('%m', start_date) IN ('03', '04', '05') THEN 'spring'
                    WHEN strftime('%m', start_date) IN ('06', '07', '08') THEN 'summer'
                    WHEN strftime('%m', start_date) IN ('09', '10', '11') THEN 'fall'
                END as season
            FROM trips 
            WHERE user_id = ? AND status = 'completed'
        ");
        $stmt->execute([$this->userId]);
        $seasons = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return count(array_unique($seasons)) >= 4;
    }
    
    private function getUniqueCountriesCount() {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT country) 
            FROM trips 
            WHERE user_id = ? AND country IS NOT NULL
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn();
    }
    
    private function getMaxBrandItemCount() {
        $stmt = $this->db->prepare("
            SELECT MAX(item_count) FROM (
                SELECT COUNT(*) as item_count 
                FROM gear 
                WHERE user_id = ? 
                GROUP BY brand
            )
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchColumn() ?? 0;
    }
    
    private function checkWeightReduction($targetReduction) {
        $stmt = $this->db->prepare("
            SELECT 
                MIN(total_weight) as lightest,
                MAX(total_weight) as heaviest
            FROM backpacks 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['heaviest'] && $result['lightest']) {
            return ($result['heaviest'] - $result['lightest']) >= $targetReduction;
        }
        
        return false;
    }
    
    private function checkAllWeightsTracked() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN weight IS NOT NULL THEN 1 ELSE 0 END) as tracked
            FROM gear 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['total'] > 0 && $result['total'] == $result['tracked'];
    }
    
    /**
     * Initialize user stats if not exists
     */
    public function initializeUserStats() {
        $stmt = $this->db->prepare("
            INSERT OR IGNORE INTO user_stats (user_id) VALUES (?)
        ");
        $stmt->execute([$this->userId]);
    }
    
    /**
     * Ensure user stats exist
     */
    private function ensureUserStats() {
        $this->initializeUserStats();
    }
}