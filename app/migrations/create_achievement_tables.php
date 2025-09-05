<?php
/**
 * Migration: Create Achievement System Tables
 * 
 * This migration creates the necessary tables for the achievement system:
 * - achievement_definitions: Stores all possible achievements
 * - user_achievements: Tracks which achievements users have earned
 * - achievement_progress: Tracks progress towards multi-step achievements
 * - user_stats: Enhanced stats tracking for achievement triggers
 */

require_once dirname(__DIR__, 2) . '/app/config.php';

try {
    $db = new PDO('sqlite:' . BTT_SQLITE_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start transaction
    $db->beginTransaction();
    
    // 1. Create achievement_definitions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS achievement_definitions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(10),
            category VARCHAR(50),
            rarity VARCHAR(20) DEFAULT 'common',
            xp_reward INTEGER DEFAULT 0,
            trigger_type VARCHAR(50),
            trigger_config TEXT,
            display_order INTEGER DEFAULT 0,
            active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // 2. Create user_achievements table
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_achievements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            achievement_id INTEGER NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            shown_at DATETIME DEFAULT NULL,
            progress FLOAT DEFAULT 0,
            metadata TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (achievement_id) REFERENCES achievement_definitions(id),
            UNIQUE(user_id, achievement_id)
        )
    ");
    
    // 3. Create achievement_progress table
    $db->exec("
        CREATE TABLE IF NOT EXISTS achievement_progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            achievement_id INTEGER NOT NULL,
            metric_key VARCHAR(50),
            metric_value FLOAT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (achievement_id) REFERENCES achievement_definitions(id),
            UNIQUE(user_id, achievement_id, metric_key)
        )
    ");
    
    // 4. Create or update user_stats table
    $db->exec("
        CREATE TABLE IF NOT EXISTS user_stats (
            user_id INTEGER PRIMARY KEY,
            total_trips INTEGER DEFAULT 0,
            total_backpacks INTEGER DEFAULT 0,
            total_gear_items INTEGER DEFAULT 0,
            total_distance_km FLOAT DEFAULT 0,
            total_elevation_m INTEGER DEFAULT 0,
            lightest_pack_kg FLOAT,
            heaviest_pack_kg FLOAT,
            longest_trip_days INTEGER DEFAULT 0,
            current_streak_days INTEGER DEFAULT 0,
            longest_streak_days INTEGER DEFAULT 0,
            last_activity_date DATE,
            total_xp INTEGER DEFAULT 0,
            current_level INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create indices for performance
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_achievements_user_id ON user_achievements(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_achievements_shown_at ON user_achievements(shown_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_achievement_progress_user_id ON achievement_progress(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_achievement_definitions_category ON achievement_definitions(category)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_achievement_definitions_active ON achievement_definitions(active)");
    
    // Insert initial achievement definitions
    $achievements = [
        // Trip Achievements
        ['first_journey', 'First Journey', 'Complete your first trip', '🥾', 'trips', 'common', 100, 'counter', '{"stat": "total_trips", "threshold": 1}'],
        ['weekend_warrior', 'Weekend Warrior', 'Complete 5 weekend trips', '🏕️', 'trips', 'common', 150, 'counter', '{"stat": "weekend_trips", "threshold": 5}'],
        ['month_long_trek', 'Month Long Trek', 'Complete a trip lasting 30+ days', '📅', 'trips', 'epic', 500, 'specific', '{"min_days": 30}'],
        ['century_club', 'Century Club', 'Log 100km total distance', '💯', 'trips', 'rare', 300, 'threshold', '{"stat": "total_distance_km", "threshold": 100}'],
        ['mountain_goat', 'Mountain Goat', 'Climb 10,000m total elevation', '🐐', 'trips', 'epic', 750, 'threshold', '{"stat": "total_elevation_m", "threshold": 10000}'],
        ['four_seasons', 'Four Seasons', 'Complete trips in all four seasons', '🌦️', 'trips', 'rare', 400, 'calculated', '{"type": "seasons"}'],
        ['solo_explorer', 'Solo Explorer', 'Complete 10 solo trips', '🚶', 'trips', 'rare', 350, 'counter', '{"stat": "solo_trips", "threshold": 10}'],
        ['group_leader', 'Group Leader', 'Lead 5 group trips', '👥', 'trips', 'rare', 400, 'counter', '{"stat": "group_trips_led", "threshold": 5}'],
        ['international_trekker', 'International Trekker', 'Complete trips in 5 different countries', '🌍', 'trips', 'legendary', 1000, 'calculated', '{"type": "countries", "threshold": 5}'],
        ['trail_angel', 'Trail Angel', 'Help 10 other hikers plan their trips', '😇', 'trips', 'epic', 600, 'counter', '{"stat": "trips_shared", "threshold": 10}'],
        
        // Backpack Achievements
        ['first_pack', 'First Pack', 'Create your first backpack', '🎒', 'backpacks', 'common', 50, 'counter', '{"stat": "total_backpacks", "threshold": 1}'],
        ['pack_collector', 'Pack Collector', 'Create 10 different packs', '📦', 'backpacks', 'rare', 250, 'counter', '{"stat": "total_backpacks", "threshold": 10}'],
        ['ultralight_master', 'Ultralight Master', 'Create a pack under 4.5kg base weight', '🪶', 'backpacks', 'epic', 500, 'specific', '{"max_weight_kg": 4.5}'],
        ['heavy_hauler', 'Heavy Hauler', 'Manage a pack over 20kg successfully', '💪', 'backpacks', 'rare', 300, 'specific', '{"min_weight_kg": 20}'],
        ['perfectionist', 'Perfectionist', 'Achieve 100% pack optimization score', '💎', 'backpacks', 'legendary', 1000, 'specific', '{"optimization_score": 100}'],
        ['quick_packer', 'Quick Packer', 'Create a complete pack in under 5 minutes', '⚡', 'backpacks', 'rare', 200, 'specific', '{"time_minutes": 5}'],
        ['gear_minimalist', 'Gear Minimalist', 'Complete a trip with less than 20 items', '🎯', 'backpacks', 'epic', 600, 'specific', '{"max_items": 20}'],
        ['organization_pro', 'Organization Pro', 'Use all 10 pack sections effectively', '📊', 'backpacks', 'rare', 300, 'specific', '{"sections_used": 10}'],
        ['weight_watcher', 'Weight Watcher', 'Reduce pack weight by 2kg from first to latest', '📉', 'backpacks', 'rare', 400, 'calculated', '{"type": "weight_reduction", "amount_kg": 2}'],
        ['pack_sharer', 'Pack Sharer', 'Share 5 packs with the community', '🤝', 'backpacks', 'common', 200, 'counter', '{"stat": "packs_shared", "threshold": 5}'],
        
        // Gear Achievements
        ['gear_head', 'Gear Head', 'Add 100 items to your gear library', '⚙️', 'gear', 'rare', 300, 'counter', '{"stat": "total_gear_items", "threshold": 100}'],
        ['brand_loyalist', 'Brand Loyalist', 'Own 20 items from the same brand', '🏷️', 'gear', 'rare', 250, 'calculated', '{"type": "brand_items", "threshold": 20}'],
        ['cottage_industry', 'Cottage Industry', 'Own gear from 10 cottage manufacturers', '🏡', 'gear', 'epic', 500, 'calculated', '{"type": "cottage_brands", "threshold": 10}'],
        ['vintage_collector', 'Vintage Collector', 'Use gear over 10 years old', '📻', 'gear', 'rare', 300, 'specific', '{"min_age_years": 10}'],
        ['tech_savvy', 'Tech Savvy', 'Track all gear with precise weights', '⚖️', 'gear', 'common', 150, 'calculated', '{"type": "all_weights_tracked"}'],
        ['multi_use_master', 'Multi-Use Master', 'Find 10 multi-use items', '🔧', 'gear', 'rare', 350, 'counter', '{"stat": "multi_use_items", "threshold": 10}'],
        ['repair_expert', 'Repair Expert', 'Log 20 gear repairs', '🔨', 'gear', 'rare', 400, 'counter', '{"stat": "gear_repairs", "threshold": 20}'],
        ['gear_reviewer', 'Gear Reviewer', 'Write 25 gear reviews', '✍️', 'gear', 'epic', 600, 'counter', '{"stat": "gear_reviews", "threshold": 25}'],
        ['budget_conscious', 'Budget Conscious', 'Build a complete kit under $500', '💸', 'gear', 'epic', 500, 'specific', '{"max_cost": 500}'],
        ['premium_gear', 'Premium Gear', 'Own $5000+ worth of gear', '💰', 'gear', 'legendary', 800, 'threshold', '{"stat": "total_gear_value", "threshold": 5000}'],
        
        // Milestone Achievements
        ['early_adopter', 'Early Adopter', 'Join within first month of account creation', '🌟', 'milestones', 'epic', 500, 'specific', '{"type": "account_age"}'],
        ['daily_visitor', 'Daily Visitor', '7-day login streak', '🔥', 'milestones', 'common', 100, 'threshold', '{"stat": "current_streak_days", "threshold": 7}'],
        ['dedicated_planner', 'Dedicated Planner', '30-day login streak', '📆', 'milestones', 'rare', 500, 'threshold', '{"stat": "current_streak_days", "threshold": 30}'],
        ['level_10', 'Level 10', 'Reach experience level 10', '🌲', 'milestones', 'rare', 250, 'threshold', '{"stat": "current_level", "threshold": 10}'],
        ['level_20', 'Level 20', 'Reach experience level 20', '👑', 'milestones', 'legendary', 1000, 'threshold', '{"stat": "current_level", "threshold": 20}'],
        ['helper', 'Community Helper', 'Answer 10 community questions', '💬', 'milestones', 'rare', 300, 'counter', '{"stat": "questions_answered", "threshold": 10}'],
        ['photographer', 'Trip Photographer', 'Upload 50 trip photos', '📸', 'milestones', 'rare', 400, 'counter', '{"stat": "photos_uploaded", "threshold": 50}'],
        ['data_driven', 'Data Driven', 'Export 10 pack lists', '📊', 'milestones', 'common', 200, 'counter', '{"stat": "exports_created", "threshold": 10}'],
        ['efficiency_expert', 'Efficiency Expert', 'Use all keyboard shortcuts', '⌨️', 'milestones', 'rare', 300, 'specific', '{"type": "all_shortcuts_used"}'],
        ['master_planner', 'Master Planner', 'Earn 20 other achievements', '🏆', 'milestones', 'legendary', 2000, 'counter', '{"stat": "total_achievements", "threshold": 20}']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO achievement_definitions (code, name, description, icon, category, rarity, xp_reward, trigger_type, trigger_config, display_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($achievements as $i => $achievement) {
        $achievement[] = $i; // Add display_order
        $stmt->execute($achievement);
    }
    
    // Commit transaction
    $db->commit();
    
    echo "Achievement system tables created successfully!\n";
    echo "Inserted " . count($achievements) . " achievement definitions.\n";
    
} catch (PDOException $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    die("Migration failed: " . $e->getMessage() . "\n");
}