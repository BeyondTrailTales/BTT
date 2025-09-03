<?php
/**
 * Gear Model
 * Handles default gear data from JSON file
 * 
 * @version 2.0.0
 */

namespace App\Models;

class Gear {
    private static $defaultGear = null;
    private static $defaultGearFile;
    private static $cacheTime = 3600; // 1 hour cache
    private static $lastLoadTime = 0;
    
    public function __construct() {
        if (self::$defaultGearFile === null) {
            self::$defaultGearFile = dirname(dirname(__DIR__)) . '/assets/data/gear-default.json';
        }
    }
    
    /**
     * Get all default gear items
     */
    public static function getDefaultGear($forceRefresh = false) {
        // Check cache
        if (!$forceRefresh && 
            self::$defaultGear !== null && 
            (time() - self::$lastLoadTime) < self::$cacheTime) {
            return self::$defaultGear;
        }
        
        // Load from file
        if (self::$defaultGearFile === null || !file_exists(self::$defaultGearFile)) {
            self::$defaultGearFile = dirname(dirname(__DIR__)) . '/assets/data/gear-default.json';
        }
        
        if (!file_exists(self::$defaultGearFile)) {
            error_log("Default gear file not found: " . self::$defaultGearFile);
            return [];
        }
        
        $json = file_get_contents(self::$defaultGearFile);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid JSON in default gear file: " . json_last_error_msg());
            return [];
        }
        
        self::$defaultGear = isset($data['items']) ? $data['items'] : [];
        self::$lastLoadTime = time();
        
        return self::$defaultGear;
    }
    
    /**
     * Get default gear by category
     */
    public static function getByCategory($category) {
        $gear = self::getDefaultGear();
        return array_filter($gear, function($item) use ($category) {
            return $item['category'] === $category;
        });
    }
    
    /**
     * Get single default gear item by ID
     */
    public static function getById($id) {
        $gear = self::getDefaultGear();
        foreach ($gear as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }
    
    /**
     * Get all unique categories from default gear
     */
    public static function getCategories() {
        $gear = self::getDefaultGear();
        $categories = array_unique(array_column($gear, 'category'));
        sort($categories);
        return $categories;
    }
    
    /**
     * Get all unique tags from default gear
     */
    public static function getAllTags() {
        $gear = self::getDefaultGear();
        $allTags = [];
        
        foreach ($gear as $item) {
            if (isset($item['tags']) && is_array($item['tags'])) {
                $allTags = array_merge($allTags, $item['tags']);
            }
        }
        
        return array_unique($allTags);
    }
    
    /**
     * Filter gear by multiple criteria
     */
    public static function filter($criteria = []) {
        $gear = self::getDefaultGear();
        
        // Category filter
        if (!empty($criteria['category'])) {
            $gear = array_filter($gear, function($item) use ($criteria) {
                return $item['category'] === $criteria['category'];
            });
        }
        
        // Weight range filter
        if (isset($criteria['min_weight'])) {
            $gear = array_filter($gear, function($item) use ($criteria) {
                return $item['weight_g'] >= $criteria['min_weight'];
            });
        }
        
        if (isset($criteria['max_weight'])) {
            $gear = array_filter($gear, function($item) use ($criteria) {
                return $item['weight_g'] <= $criteria['max_weight'];
            });
        }
        
        // Search filter
        if (!empty($criteria['search'])) {
            $search = strtolower($criteria['search']);
            $gear = array_filter($gear, function($item) use ($search) {
                $searchIn = strtolower(
                    $item['name'] . ' ' . 
                    $item['category'] . ' ' . 
                    implode(' ', $item['tags'] ?? []) . ' ' . 
                    ($item['notes'] ?? '')
                );
                return strpos($searchIn, $search) !== false;
            });
        }
        
        // Tag filter
        if (!empty($criteria['tags']) && is_array($criteria['tags'])) {
            $gear = array_filter($gear, function($item) use ($criteria) {
                if (!isset($item['tags'])) return false;
                return !empty(array_intersect($item['tags'], $criteria['tags']));
            });
        }
        
        return array_values($gear);
    }
    
    /**
     * Validate default gear data structure
     */
    public static function validate($item) {
        $errors = [];
        
        // Required fields
        if (empty($item['id'])) {
            $errors['id'] = 'ID is required';
        }
        
        if (empty($item['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($item['category'])) {
            $errors['category'] = 'Category is required';
        }
        
        if (!isset($item['weight_g']) || !is_numeric($item['weight_g'])) {
            $errors['weight_g'] = 'Weight must be a number';
        } elseif ($item['weight_g'] < 0) {
            $errors['weight_g'] = 'Weight cannot be negative';
        }
        
        // Check for brand names (should not exist in default gear)
        $brandPatterns = [
            '/\b(zpacks|hyperlite|rei|patagonia|north face|columbia|msr|jetboil|sawyer|katadyn|thermarest|sea to summit|smartwater|nitecore|petzl|garmin|anker|dr\.?\s*bronners?|band-?aid)\b/i'
        ];
        
        foreach ($brandPatterns as $pattern) {
            if (preg_match($pattern, $item['name'])) {
                $errors['name'] = 'Default gear should not contain brand names';
                break;
            }
        }
        
        // Check for price (should not exist)
        if (isset($item['price'])) {
            $errors['price'] = 'Default gear should not have prices';
        }
        
        return empty($errors) ? true : $errors;
    }
}
