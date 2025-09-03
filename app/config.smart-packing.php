<?php
/**
 * Smart Packing Assistant V2 Configuration
 * 
 * Feature flags and environment settings for the enhanced
 * Smart Packing Assistant with improved UX/UI and ADA compliance
 * 
 * @version 2.0.0
 * @since 2025-01
 */

return [
    /**
     * Feature Flags
     * These control the availability of various Smart Packing features
     */
    'features' => [
        // Master switch for Smart Packing V2
        'smart_packing_v2' => env('BTT_FEATURE_SMART_PACKING_V2', true),
        
        // Individual feature toggles
        'ai_suggestions' => env('BTT_FEATURE_AI_SUGGESTIONS', true),
        'auto_categorization' => env('BTT_FEATURE_AUTO_CATEGORIZATION', true),
        'weight_optimization' => env('BTT_FEATURE_WEIGHT_OPTIMIZATION', true),
        'missing_items_detection' => env('BTT_FEATURE_MISSING_ITEMS', true),
        'efficiency_scoring' => env('BTT_FEATURE_EFFICIENCY_SCORING', true),
        'offline_mode' => env('BTT_FEATURE_OFFLINE_MODE', true),
        'voice_input' => env('BTT_FEATURE_VOICE_INPUT', false),
        'collaborative_packing' => env('BTT_FEATURE_COLLABORATIVE', false),
    ],
    
    /**
     * AI Provider Configuration
     */
    'ai' => [
        // Provider selection: none|openai|azure|mcp|rule_based
        'provider' => env('BTT_AI_PROVIDER', 'rule_based'),
        
        // API Keys (should be in environment variables)
        'openai_key' => env('OPENAI_API_KEY', ''),
        'azure_key' => env('AZURE_API_KEY', ''),
        
        // API Settings
        'timeout_ms' => env('BTT_AI_TIMEOUT_MS', 10000),
        'max_retries' => env('BTT_AI_MAX_RETRIES', 3),
        'cache_ttl' => env('BTT_AI_CACHE_TTL', 3600), // 1 hour
        
        // Safety Settings
        'max_tokens' => env('BTT_AI_MAX_TOKENS', 500),
        'temperature' => env('BTT_AI_TEMPERATURE', 0.7),
        'scrub_pii' => env('BTT_AI_SCRUB_PII', true),
    ],
    
    /**
     * Performance Settings
     */
    'performance' => [
        'lazy_load_threshold' => env('BTT_LAZY_LOAD_THRESHOLD', 20),
        'batch_size' => env('BTT_BATCH_SIZE', 50),
        'debounce_ms' => env('BTT_DEBOUNCE_MS', 300),
        'worker_enabled' => env('BTT_USE_WEB_WORKERS', true),
        'cache_enabled' => env('BTT_CACHE_ENABLED', true),
    ],
    
    /**
     * UI/UX Settings
     */
    'ui' => [
        // Theme settings
        'default_theme' => env('BTT_DEFAULT_THEME', 'forest-light'),
        'allow_theme_switch' => env('BTT_ALLOW_THEME_SWITCH', true),
        
        // Animation settings
        'animations_enabled' => env('BTT_ANIMATIONS_ENABLED', true),
        'respect_reduced_motion' => env('BTT_RESPECT_REDUCED_MOTION', true),
        
        // Layout settings
        'default_layout' => env('BTT_DEFAULT_LAYOUT', 'responsive'),
        'mobile_first' => env('BTT_MOBILE_FIRST', true),
        'touch_enabled' => env('BTT_TOUCH_ENABLED', true),
    ],
    
    /**
     * Accessibility Settings
     */
    'accessibility' => [
        'high_contrast_available' => env('BTT_HIGH_CONTRAST', true),
        'keyboard_navigation' => env('BTT_KEYBOARD_NAV', true),
        'screen_reader_optimized' => env('BTT_SCREEN_READER', true),
        'focus_indicators' => env('BTT_FOCUS_INDICATORS', true),
        'skip_links' => env('BTT_SKIP_LINKS', true),
        'aria_live_regions' => env('BTT_ARIA_LIVE', true),
    ],
    
    /**
     * Data and Storage Settings
     */
    'storage' => [
        'local_storage_enabled' => env('BTT_LOCAL_STORAGE', true),
        'session_storage_enabled' => env('BTT_SESSION_STORAGE', true),
        'indexeddb_enabled' => env('BTT_INDEXEDDB', false),
        'cache_prefix' => env('BTT_CACHE_PREFIX', 'btt_sp_'),
        'max_cache_size_mb' => env('BTT_MAX_CACHE_MB', 10),
    ],
    
    /**
     * Weight and Units Configuration
     */
    'units' => [
        'weight_system' => env('BTT_WEIGHT_SYSTEM', 'metric'), // metric|imperial
        'default_weight_unit' => env('BTT_DEFAULT_WEIGHT', 'kg'), // kg|g|lb|oz
        'allow_unit_switch' => env('BTT_ALLOW_UNIT_SWITCH', true),
        'precision' => env('BTT_WEIGHT_PRECISION', 1), // decimal places
    ],
    
    /**
     * Trip Types and Categories
     */
    'trip_types' => [
        'day-hike' => [
            'name' => 'Day Hike',
            'icon' => '🥾',
            'max_weight_kg' => 10,
            'typical_duration_hours' => 8,
        ],
        'weekend-backpacking' => [
            'name' => 'Weekend Backpacking',
            'icon' => '🏕️',
            'max_weight_kg' => 15,
            'typical_duration_hours' => 48,
        ],
        'thru-hike' => [
            'name' => 'Thru-Hike',
            'icon' => '🏔️',
            'max_weight_kg' => 9,
            'typical_duration_hours' => 720, // 30 days
        ],
        'car-camping' => [
            'name' => 'Car Camping',
            'icon' => '🚗',
            'max_weight_kg' => 50,
            'typical_duration_hours' => 72,
        ],
    ],
    
    /**
     * Backpack Sections Configuration
     */
    'sections' => [
        'lid' => [
            'name' => 'Top Lid',
            'icon' => '🎒',
            'color' => '#3b82f6',
            'max_weight_kg' => 1.0,
            'description' => 'Quick access items',
        ],
        'main' => [
            'name' => 'Main Compartment',
            'icon' => '📦',
            'color' => '#10b981',
            'max_weight_kg' => 6.0,
            'description' => 'Core gear and clothing',
        ],
        'front' => [
            'name' => 'Front Pocket',
            'icon' => '🗂️',
            'color' => '#f59e0b',
            'max_weight_kg' => 1.5,
            'description' => 'Navigation and safety',
        ],
        'side' => [
            'name' => 'Side Pockets',
            'icon' => '💧',
            'color' => '#8b5cf6',
            'max_weight_kg' => 2.0,
            'description' => 'Water and snacks',
        ],
        'bottom' => [
            'name' => 'Bottom Section',
            'icon' => '⛺',
            'color' => '#ef4444',
            'max_weight_kg' => 4.0,
            'description' => 'Heavy items and shelter',
        ],
    ],
    
    /**
     * Gamification Settings
     */
    'gamification' => [
        'enabled' => env('BTT_GAMIFICATION', true),
        'xp_per_item' => env('BTT_XP_PER_ITEM', 10),
        'xp_per_optimization' => env('BTT_XP_OPTIMIZATION', 50),
        'badges_enabled' => env('BTT_BADGES', true),
        'streaks_enabled' => env('BTT_STREAKS', true),
        'leaderboard_enabled' => env('BTT_LEADERBOARD', false),
    ],
    
    /**
     * Notification Settings
     */
    'notifications' => [
        'toast_enabled' => env('BTT_TOAST_NOTIFICATIONS', true),
        'sound_enabled' => env('BTT_SOUND_NOTIFICATIONS', false),
        'haptic_enabled' => env('BTT_HAPTIC_FEEDBACK', true),
        'duration_ms' => env('BTT_NOTIFICATION_DURATION', 3000),
    ],
];

/**
 * Helper function to get environment variable with default
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        
        return $value;
    }
}
