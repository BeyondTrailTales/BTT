<?php
/**
 * BeyondTrailTales - Trip Access Policy
 * 
 * Handles authorization for trip access and modifications
 * Following Context7 best practices
 */

namespace App\Policies;

use PDO;
use Exception;

class TripPolicy {
    
    private static $db = null;
    
    /**
     * Initialize database connection
     */
    private static function init() {
        if (self::$db === null) {
            $dbPath = dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            try {
                self::$db = new PDO('sqlite:' . $dbPath);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                throw new Exception('Failed to connect to database: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Check if user can view a trip
     * 
     * @param int $userId
     * @param int $tripId
     * @return bool
     */
    public static function canView($userId, $tripId) {
        self::init();
        
        try {
            // Check if user is owner
            $stmt = self::$db->prepare("
                SELECT id FROM trips 
                WHERE id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            if ($stmt->fetch()) {
                return true;
            }
            
            // Check if user is a collaborator
            $stmt = self::$db->prepare("
                SELECT id FROM trip_collaborators 
                WHERE trip_id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            error_log("TripPolicy::canView error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can edit a trip
     * 
     * @param int $userId
     * @param int $tripId
     * @return bool
     */
    public static function canEdit($userId, $tripId) {
        self::init();
        
        try {
            // Check if user is owner
            $stmt = self::$db->prepare("
                SELECT id FROM trips 
                WHERE id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            if ($stmt->fetch()) {
                return true;
            }
            
            // Check if user is a collaborator with edit permissions
            $stmt = self::$db->prepare("
                SELECT role FROM trip_collaborators 
                WHERE trip_id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            $collaborator = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($collaborator && in_array($collaborator['role'], ['owner', 'editor'])) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("TripPolicy::canEdit error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can delete a trip
     * 
     * @param int $userId
     * @param int $tripId
     * @return bool
     */
    public static function canDelete($userId, $tripId) {
        self::init();
        
        try {
            // Only owners can delete trips
            $stmt = self::$db->prepare("
                SELECT id FROM trips 
                WHERE id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            error_log("TripPolicy::canDelete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can share a trip
     * 
     * @param int $userId
     * @param int $tripId
     * @return bool
     */
    public static function canShare($userId, $tripId) {
        self::init();
        
        try {
            // Check if user is owner
            $stmt = self::$db->prepare("
                SELECT id FROM trips 
                WHERE id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            if ($stmt->fetch()) {
                return true;
            }
            
            // Check if user is a collaborator with owner role
            $stmt = self::$db->prepare("
                SELECT role FROM trip_collaborators 
                WHERE trip_id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            $collaborator = $stmt->fetch(PDO::FETCH_ASSOC);
            return $collaborator && $collaborator['role'] === 'owner';
            
        } catch (Exception $e) {
            error_log("TripPolicy::canShare error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's role for a trip
     * 
     * @param int $userId
     * @param int $tripId
     * @return string|null 'owner', 'editor', 'viewer', or null
     */
    public static function getUserRole($userId, $tripId) {
        self::init();
        
        try {
            // Check if user is owner
            $stmt = self::$db->prepare("
                SELECT id FROM trips 
                WHERE id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            if ($stmt->fetch()) {
                return 'owner';
            }
            
            // Check collaborator role
            $stmt = self::$db->prepare("
                SELECT role FROM trip_collaborators 
                WHERE trip_id = :trip_id AND user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute(['trip_id' => $tripId, 'user_id' => $userId]);
            
            $collaborator = $stmt->fetch(PDO::FETCH_ASSOC);
            return $collaborator ? $collaborator['role'] : null;
            
        } catch (Exception $e) {
            error_log("TripPolicy::getUserRole error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all users with access to a trip
     * 
     * @param int $tripId
     * @return array
     */
    public static function getTripUsers($tripId) {
        self::init();
        
        try {
            $users = [];
            
            // Get owner
            $stmt = self::$db->prepare("
                SELECT u.id, u.username, u.email, 'owner' as role
                FROM trips t
                JOIN users u ON t.user_id = u.id
                WHERE t.id = :trip_id
            ");
            $stmt->execute(['trip_id' => $tripId]);
            
            if ($owner = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $owner;
            }
            
            // Get collaborators
            $stmt = self::$db->prepare("
                SELECT u.id, u.username, u.email, tc.role
                FROM trip_collaborators tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.trip_id = :trip_id
                ORDER BY tc.created_at
            ");
            $stmt->execute(['trip_id' => $tripId]);
            
            while ($collaborator = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $collaborator;
            }
            
            return $users;
            
        } catch (Exception $e) {
            error_log("TripPolicy::getTripUsers error: " . $e->getMessage());
            return [];
        }
    }
}
