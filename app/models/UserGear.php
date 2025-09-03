<?php
/**
 * UserGear Model
 * Handles custom user gear items in SQLite database
 * 
 * @version 2.0.0
 */

namespace App\Models;

use PDO;
use PDOException;

class UserGear {
    private $db;
    private $table = 'user_gear';
    
    // Valid categories
    public static $validCategories = [
        'shelter', 'sleep', 'cooking', 'clothing', 'navigation', 
        'hygiene', 'first-aid', 'electronics', 'water', 
        'food-storage', 'repair', 'other'
    ];
    
    public function __construct($database = null) {
        if ($database) {
            $this->db = $database;
        } else {
            $this->connect();
        }
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dbPath = dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all gear items for a user
     */
    public function getUserGear($userId, $includeDeleted = false) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " ORDER BY category, name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if (isset($item['tags'])) {
                $item['tags'] = json_decode($item['tags'], true) ?: [];
            }
            $item['is_custom'] = true;
            $item['is_default'] = false;
        }
        
        return $items;
    }
    
    /**
     * Get single gear item
     */
    public function getById($id, $userId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            if (isset($item['tags'])) {
                $item['tags'] = json_decode($item['tags'], true) ?: [];
            }
            $item['is_custom'] = true;
            $item['is_default'] = false;
        }
        
        return $item;
    }
    
    /**
     * Create new gear item
     */
    public function create($userId, $data) {
        // Validate required fields
        $errors = $this->validate($data);
        if ($errors !== true) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $sql = "INSERT INTO {$this->table} 
                (user_id, name, category, weight_g, tags, notes)
                VALUES 
                (:user_id, :name, :category, :weight_g, :tags, :notes)";
        
        $params = [
            'user_id' => $userId,
            'name' => $this->sanitize($data['name']),
            'category' => $data['category'],
            'weight_g' => floatval($data['weight_g']),
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : '[]',
            'notes' => isset($data['notes']) ? $this->sanitize($data['notes']) : null
        ];
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $id = $this->db->lastInsertId();
            return [
                'success' => true,
                'data' => $this->getById($id, $userId)
            ];
        } catch (PDOException $e) {
            error_log("Failed to create gear item: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create gear item'
            ];
        }
    }
    
    /**
     * Update gear item
     */
    public function update($id, $userId, $data) {
        // Check ownership
        $existing = $this->getById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'error' => 'Item not found'];
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = ['id' => $id, 'user_id' => $userId];
        
        if (isset($data['name'])) {
            $updateFields[] = "name = :name";
            $params['name'] = $this->sanitize($data['name']);
        }
        
        if (isset($data['category'])) {
            if (!in_array($data['category'], self::$validCategories)) {
                return ['success' => false, 'error' => 'Invalid category'];
            }
            $updateFields[] = "category = :category";
            $params['category'] = $data['category'];
        }
        
        if (isset($data['weight_g'])) {
            $updateFields[] = "weight_g = :weight_g";
            $params['weight_g'] = floatval($data['weight_g']);
        }
        
        if (isset($data['tags'])) {
            $updateFields[] = "tags = :tags";
            $params['tags'] = json_encode($data['tags']);
        }
        
        if (isset($data['notes'])) {
            $updateFields[] = "notes = :notes";
            $params['notes'] = $this->sanitize($data['notes']);
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updateFields[] = "updated_at = datetime('now')";
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . 
               " WHERE id = :id AND user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $this->getById($id, $userId)
            ];
        } catch (PDOException $e) {
            error_log("Failed to update gear item: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update gear item'
            ];
        }
    }
    
    /**
     * Delete (archive) gear item
     */
    public function delete($id, $userId, $permanent = false) {
        // Check ownership
        $existing = $this->getById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'error' => 'Item not found'];
        }
        
        if ($permanent) {
            $sql = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        } else {
            $sql = "UPDATE {$this->table} SET deleted_at = datetime('now'), updated_at = datetime('now') 
                    WHERE id = :id AND user_id = :user_id";
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Failed to delete gear item: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete gear item'
            ];
        }
    }
    
    /**
     * Restore deleted item
     */
    public function restore($id, $userId) {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL, updated_at = datetime('now') 
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'data' => $this->getById($id, $userId)
                ];
            } else {
                return ['success' => false, 'error' => 'Item not found or not deleted'];
            }
        } catch (PDOException $e) {
            error_log("Failed to restore gear item: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to restore gear item'
            ];
        }
    }
    
    /**
     * Validate gear data
     */
    private function validate($data) {
        $errors = [];
        
        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be 100 characters or less';
        }
        
        // Category validation
        if (empty($data['category'])) {
            $errors['category'] = 'Category is required';
        } elseif (!in_array($data['category'], self::$validCategories)) {
            $errors['category'] = 'Invalid category';
        }
        
        // Weight validation
        if (!isset($data['weight_g'])) {
            $errors['weight_g'] = 'Weight is required';
        } elseif (!is_numeric($data['weight_g'])) {
            $errors['weight_g'] = 'Weight must be a number';
        } elseif ($data['weight_g'] < 0) {
            $errors['weight_g'] = 'Weight cannot be negative';
        } elseif ($data['weight_g'] > 10000) {
            $errors['weight_g'] = 'Weight cannot exceed 10kg';
        }
        
        
        // Tags validation
        if (isset($data['tags']) && is_array($data['tags']) && count($data['tags']) > 20) {
            $errors['tags'] = 'Maximum 20 tags allowed';
        }
        
        // Notes validation
        if (isset($data['notes']) && strlen($data['notes']) > 500) {
            $errors['notes'] = 'Notes must be 500 characters or less';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Sanitize input
     */
    private function sanitize($string) {
        // Remove HTML tags
        $string = strip_tags($string);
        // Trim whitespace
        $string = trim($string);
        // Convert special characters to HTML entities
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        
        return $string;
    }
    
    /**
     * Get gear statistics for user
     */
    public function getStats($userId) {
        $sql = "SELECT 
                COUNT(*) as total_items,
                SUM(weight_g) as total_weight,
                COUNT(DISTINCT category) as categories,
                AVG(weight_g) as avg_weight
                FROM {$this->table}
                WHERE user_id = :user_id AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
