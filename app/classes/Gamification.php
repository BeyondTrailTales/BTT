<?php
/**
 * Gamification System for BeyondTrailTales
 * Handles XP, levels, badges, streaks, and achievements
 * 
 * @author BeyondTrailTales Team
 * @version 1.0.0
 */

namespace BTT\Classes;

class Gamification {
    
    // XP values for different actions
    const XP_VALUES = [
        'create_backpack' => 50,
        'add_item' => 10,
        'remove_item' => 5,
        'complete_trip' => 200,
        'daily_login' => 25,
        'create_trip' => 75,
        'update_backpack' => 15,
        'share_trip' => 30,
        'first_backpack' => 100,  // Bonus for first backpack
        'first_trip' => 150,       // Bonus for first trip
    ];
    
    // Level thresholds (cumulative XP needed)
    const LEVEL_THRESHOLDS = [
        1 => 0,
        2 => 100,
        3 => 250,
        4 => 500,
        5 => 850,
        6 => 1300,
        7 => 1850,
        8 => 2500,
        9 => 3250,
        10 => 4100,
        11 => 5050,
        12 => 6100,
        13 => 7250,
        14 => 8500,
        15 => 9850,
        16 => 11300,
        17 => 12850,
        18 => 14500,
        19 => 16250,
        20 => 18100,
    ];
    
    // Badge definitions
    const BADGES = [
        'first_steps' => [
            'id' => 'first_steps',
            'name' => 'First Steps',
            'description' => 'Create your first backpack',
            'icon' => '🏃',
            'condition' => 'backpack_count',
            'threshold' => 1,
            'xp_reward' => 50
        ],
        'pack_master' => [
            'id' => 'pack_master',
            'name' => 'Pack Master',
            'description' => 'Create 5 backpacks',
            'icon' => '🎒',
            'condition' => 'backpack_count',
            'threshold' => 5,
            'xp_reward' => 100
        ],
        'trail_blazer' => [
            'id' => 'trail_blazer',
            'name' => 'Trail Blazer',
            'description' => 'Complete your first trip',
            'icon' => '🥾',
            'condition' => 'trip_count',
            'threshold' => 1,
            'xp_reward' => 75
        ],
        'summit_seeker' => [
            'id' => 'summit_seeker',
            'name' => 'Summit Seeker',
            'description' => 'Complete 10 trips',
            'icon' => '🏔️',
            'condition' => 'trip_count',
            'threshold' => 10,
            'xp_reward' => 200
        ],
        'week_warrior' => [
            'id' => 'week_warrior',
            'name' => 'Week Warrior',
            'description' => 'Maintain a 7-day streak',
            'icon' => '🔥',
            'condition' => 'streak_days',
            'threshold' => 7,
            'xp_reward' => 150
        ],
        'month_master' => [
            'id' => 'month_master',
            'name' => 'Month Master',
            'description' => 'Maintain a 30-day streak',
            'icon' => '🌟',
            'condition' => 'streak_days',
            'threshold' => 30,
            'xp_reward' => 500
        ],
        'gear_guru' => [
            'id' => 'gear_guru',
            'name' => 'Gear Guru',
            'description' => 'Pack 100 total items',
            'icon' => '⚙️',
            'condition' => 'total_items_packed',
            'threshold' => 100,
            'xp_reward' => 150
        ],
        'weight_watcher' => [
            'id' => 'weight_watcher',
            'name' => 'Weight Watcher',
            'description' => 'Keep a pack under 10kg',
            'icon' => '⚖️',
            'condition' => 'lightweight_pack',
            'threshold' => 1,
            'xp_reward' => 75
        ],
        'forest_friend' => [
            'id' => 'forest_friend',
            'name' => 'Forest Friend',
            'description' => 'Reach level 5',
            'icon' => '🌲',
            'condition' => 'level',
            'threshold' => 5,
            'xp_reward' => 100
        ],
        'mountain_monarch' => [
            'id' => 'mountain_monarch',
            'name' => 'Mountain Monarch',
            'description' => 'Reach level 10',
            'icon' => '👑',
            'condition' => 'level',
            'threshold' => 10,
            'xp_reward' => 250
        ]
    ];
    
    private $userData;
    private $dataFile;
    
    public function __construct($userId = 'default') {
        $this->dataFile = \BTT_JSON_PATH . '/gamification_' . $userId . '.json';
        $this->loadUserData();
    }
    
    /**
     * Load user gamification data from JSON file
     */
    private function loadUserData() {
        if (file_exists($this->dataFile)) {
            $data = file_get_contents($this->dataFile);
            $this->userData = json_decode($data, true);
        } else {
            // Initialize new user data
            $this->userData = [
                'xp' => 0,
                'level' => 1,
                'badges' => [],
                'streak_days' => 0,
                'last_visit' => null,
                'stats' => [
                    'backpack_count' => 0,
                    'trip_count' => 0,
                    'total_items_packed' => 0,
                    'lightweight_pack' => 0,
                ],
                'recent_achievements' => [],
                'xp_history' => []
            ];
            $this->saveUserData();
        }
    }
    
    /**
     * Save user gamification data to JSON file
     */
    private function saveUserData() {
        $json = json_encode($this->userData, JSON_PRETTY_PRINT);
        file_put_contents($this->dataFile, $json);
    }
    
    /**
     * Award XP for an action
     */
    public function awardXP($action, $amount = null) {
        $xpGained = $amount ?? self::XP_VALUES[$action] ?? 0;
        
        if ($xpGained > 0) {
            $oldLevel = $this->userData['level'];
            $this->userData['xp'] += $xpGained;
            
            // Add to XP history
            $this->userData['xp_history'][] = [
                'action' => $action,
                'xp' => $xpGained,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Keep only last 50 XP events
            if (count($this->userData['xp_history']) > 50) {
                $this->userData['xp_history'] = array_slice($this->userData['xp_history'], -50);
            }
            
            // Check for level up
            $newLevel = $this->calculateLevel($this->userData['xp']);
            if ($newLevel > $oldLevel) {
                $this->userData['level'] = $newLevel;
                $this->checkBadges(); // Check level-based badges
                
                return [
                    'xp_gained' => $xpGained,
                    'total_xp' => $this->userData['xp'],
                    'level_up' => true,
                    'new_level' => $newLevel,
                    'old_level' => $oldLevel
                ];
            }
            
            $this->saveUserData();
            
            return [
                'xp_gained' => $xpGained,
                'total_xp' => $this->userData['xp'],
                'level_up' => false,
                'level' => $this->userData['level']
            ];
        }
        
        return null;
    }
    
    /**
     * Calculate level from XP
     */
    private function calculateLevel($xp) {
        $level = 1;
        foreach (self::LEVEL_THRESHOLDS as $lvl => $threshold) {
            if ($xp >= $threshold) {
                $level = $lvl;
            } else {
                break;
            }
        }
        return $level;
    }
    
    /**
     * Get XP needed for next level
     */
    public function getXPForNextLevel() {
        $currentLevel = $this->userData['level'];
        $nextLevel = $currentLevel + 1;
        
        if (isset(self::LEVEL_THRESHOLDS[$nextLevel])) {
            return self::LEVEL_THRESHOLDS[$nextLevel] - $this->userData['xp'];
        }
        
        return 0; // Max level reached
    }
    
    /**
     * Get XP progress percentage to next level
     */
    public function getXPProgress() {
        $currentLevel = $this->userData['level'];
        $nextLevel = $currentLevel + 1;
        
        if (!isset(self::LEVEL_THRESHOLDS[$nextLevel])) {
            return 100; // Max level
        }
        
        $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel];
        $nextThreshold = self::LEVEL_THRESHOLDS[$nextLevel];
        $levelXP = $nextThreshold - $currentThreshold;
        $currentLevelXP = $this->userData['xp'] - $currentThreshold;
        
        return min(100, round(($currentLevelXP / $levelXP) * 100));
    }
    
    /**
     * Update daily streak
     */
    public function updateStreak() {
        $today = date('Y-m-d');
        $lastVisit = $this->userData['last_visit'];
        
        if ($lastVisit === null) {
            // First visit
            $this->userData['streak_days'] = 1;
            $this->userData['last_visit'] = $today;
            $this->awardXP('daily_login');
        } elseif ($lastVisit === $today) {
            // Already visited today
            return $this->userData['streak_days'];
        } else {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            if ($lastVisit === $yesterday) {
                // Streak continues
                $this->userData['streak_days']++;
                $this->awardXP('daily_login');
            } else {
                // Streak broken
                $this->userData['streak_days'] = 1;
                $this->awardXP('daily_login', 10); // Reduced XP for broken streak
            }
            
            $this->userData['last_visit'] = $today;
        }
        
        $this->checkBadges(); // Check streak-based badges
        $this->saveUserData();
        
        return $this->userData['streak_days'];
    }
    
    /**
     * Update stats and check for new badges
     */
    public function updateStats($stat, $value = 1, $increment = true) {
        if (!isset($this->userData['stats'][$stat])) {
            $this->userData['stats'][$stat] = 0;
        }
        
        if ($increment) {
            $this->userData['stats'][$stat] += $value;
        } else {
            $this->userData['stats'][$stat] = $value;
        }
        
        $newBadges = $this->checkBadges();
        $this->saveUserData();
        
        return $newBadges;
    }
    
    /**
     * Check if user has earned any new badges
     */
    private function checkBadges() {
        $newBadges = [];
        
        foreach (self::BADGES as $badgeId => $badge) {
            // Skip if already earned
            if (in_array($badgeId, $this->userData['badges'])) {
                continue;
            }
            
            $earned = false;
            
            switch ($badge['condition']) {
                case 'backpack_count':
                case 'trip_count':
                case 'total_items_packed':
                case 'lightweight_pack':
                    if ($this->userData['stats'][$badge['condition']] >= $badge['threshold']) {
                        $earned = true;
                    }
                    break;
                    
                case 'streak_days':
                    if ($this->userData['streak_days'] >= $badge['threshold']) {
                        $earned = true;
                    }
                    break;
                    
                case 'level':
                    if ($this->userData['level'] >= $badge['threshold']) {
                        $earned = true;
                    }
                    break;
            }
            
            if ($earned) {
                $this->userData['badges'][] = $badgeId;
                $this->userData['recent_achievements'][] = [
                    'badge_id' => $badgeId,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                // Award bonus XP for earning badge
                $this->awardXP('badge_earned', $badge['xp_reward']);
                
                $newBadges[] = $badge;
            }
        }
        
        // Keep only last 10 recent achievements
        if (count($this->userData['recent_achievements']) > 10) {
            $this->userData['recent_achievements'] = array_slice(
                $this->userData['recent_achievements'], 
                -10
            );
        }
        
        return $newBadges;
    }
    
    /**
     * Get user's current gamification data
     */
    public function getUserData() {
        return [
            'xp' => $this->userData['xp'],
            'level' => $this->userData['level'],
            'xp_progress' => $this->getXPProgress(),
            'xp_to_next_level' => $this->getXPForNextLevel(),
            'next_level_threshold' => self::LEVEL_THRESHOLDS[$this->userData['level'] + 1] ?? null,
            'streak_days' => $this->userData['streak_days'],
            'badges' => $this->getBadgesWithDetails(),
            'stats' => $this->userData['stats'],
            'recent_achievements' => $this->userData['recent_achievements']
        ];
    }
    
    /**
     * Get badges with full details
     */
    private function getBadgesWithDetails() {
        $badges = [];
        foreach ($this->userData['badges'] as $badgeId) {
            if (isset(self::BADGES[$badgeId])) {
                $badges[] = self::BADGES[$badgeId];
            }
        }
        return $badges;
    }
    
    /**
     * Get all available badges with earned status
     */
    public function getAllBadges() {
        $badges = [];
        foreach (self::BADGES as $badgeId => $badge) {
            $badge['earned'] = in_array($badgeId, $this->userData['badges']);
            $badges[] = $badge;
        }
        return $badges;
    }
    
    /**
     * Reset user data (for testing)
     */
    public function resetUserData() {
        $this->userData = [
            'xp' => 0,
            'level' => 1,
            'badges' => [],
            'streak_days' => 0,
            'last_visit' => null,
            'stats' => [
                'backpack_count' => 0,
                'trip_count' => 0,
                'total_items_packed' => 0,
                'lightweight_pack' => 0,
            ],
            'recent_achievements' => [],
            'xp_history' => []
        ];
        $this->saveUserData();
    }
}
