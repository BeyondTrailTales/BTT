<?php
/**
 * UserGearPrefs Model
 * Handles user preferences for gear library
 * 
 * @version 2.0.0
 */

namespace App\Models;

use PDO;
use PDOException;

class UserGearPrefs {
    private $db;
    private $table = 'user_gear_preferences';
    
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
     * Get user preferences
     */
    public function getPreferences($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prefs) {
            // Create default preferences if none exist
            $this->createDefaultPreferences($userId);
            return $this->getDefaultPreferences();
        }
        
        // Decode JSON fields
        if (isset($prefs['hidden_default_ids'])) {
            $prefs['hidden_default_ids'] = json_decode($prefs['hidden_default_ids'], true) ?: [];
        }
        
        if (isset($prefs['filters'])) {
            $prefs['filters'] = json_decode($prefs['filters'], true) ?: [];
        }
        
        return $prefs;
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences($userId, $data) {
        // Get existing preferences
        $existing = $this->getPreferences($userId);
        
        // Build update query
        $updateFields = [];
        $params = ['user_id' => $userId];
        
        if (isset($data['view_mode'])) {
            if (!in_array($data['view_mode'], ['default', 'custom', 'both'])) {
                return ['success' => false, 'error' => 'Invalid view mode'];
            }
            $updateFields[] = "view_mode = :view_mode";
            $params['view_mode'] = $data['view_mode'];
        }
        
        if (isset($data['hidden_default_ids'])) {
            $updateFields[] = "hidden_default_ids = :hidden_default_ids";
            $params['hidden_default_ids'] = json_encode($data['hidden_default_ids']);
        }
        
        if (isset($data['preferred_units'])) {
            if (!in_array($data['preferred_units'], ['g', 'oz'])) {
                return ['success' => false, 'error' => 'Invalid units'];
            }
            $updateFields[] = "preferred_units = :preferred_units";
            $params['preferred_units'] = $data['preferred_units'];
        }
        
        if (isset($data['last_sort'])) {
            $updateFields[] = "last_sort = :last_sort";
            $params['last_sort'] = $data['last_sort'];
        }
        
        if (isset($data['filters'])) {
            $updateFields[] = "filters = :filters";
            $params['filters'] = json_encode($data['filters']);
        }
        
        if (isset($data['list_view'])) {
            if (!in_array($data['list_view'], ['cards', 'list'])) {
                return ['success' => false, 'error' => 'Invalid list view'];
            }
            $updateFields[] = "list_view = :list_view";
            $params['list_view'] = $data['list_view'];
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updateFields[] = "updated_at = datetime('now')";
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . 
               " WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $this->getPreferences($userId)
            ];
        } catch (PDOException $e) {
            error_log("Failed to update preferences: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update preferences'
            ];
        }
    }
    
    /**
     * Hide a default gear item
     */
    public function hideDefaultItem($userId, $itemId) {
        $prefs = $this->getPreferences($userId);
        $hiddenIds = $prefs['hidden_default_ids'] ?? [];
        
        if (!in_array($itemId, $hiddenIds)) {
            $hiddenIds[] = $itemId;
            
            return $this->updatePreferences($userId, [
                'hidden_default_ids' => $hiddenIds
            ]);
        }
        
        return ['success' => true, 'message' => 'Item already hidden'];
    }
    
    /**
     * Show (unhide) a default gear item
     */
    public function showDefaultItem($userId, $itemId) {
        $prefs = $this->getPreferences($userId);
        $hiddenIds = $prefs['hidden_default_ids'] ?? [];
        
        $hiddenIds = array_values(array_diff($hiddenIds, [$itemId]));
        
        return $this->updatePreferences($userId, [
            'hidden_default_ids' => $hiddenIds
        ]);
    }
    
    /**
     * Reset all hidden items
     */
    public function resetHiddenItems($userId) {
        return $this->updatePreferences($userId, [
            'hidden_default_ids' => []
        ]);
    }
    
    /**
     * Get default preferences
     */
    private function getDefaultPreferences() {
        return [
            'view_mode' => 'both',
            'hidden_default_ids' => [],
            'preferred_units' => 'g',
            'last_sort' => 'name_asc',
            'filters' => [],
            'list_view' => 'cards'
        ];
    }
    
    /**
     * Create default preferences for new user
     */
    private function createDefaultPreferences($userId) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, view_mode, hidden_default_ids, preferred_units, last_sort, filters, list_view)
                VALUES 
                (:user_id, 'both', '[]', 'g', 'name_asc', '{}', 'cards')";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return true;
        } catch (PDOException $e) {
            // If insert fails, user might already have prefs
            error_log("Failed to create default preferences: " . $e->getMessage());
            return false;
        }
    }
}
