<?php
/**
 * Backpack Model
 * Handles backpack data operations and totals computation
 */

namespace App\Models;

use Database;
use PDO;

class Backpack {
    
    /**
     * Compute totals for a backpack from its items
     * 
     * @param Database|PDO $db Database connection
     * @param int $backpackId Backpack ID
     * @return array Array with total_items and total_weight_g
     */
    public static function computeTotals($db, int $backpackId): array {
        // Handle both Database class and PDO directly
        $conn = ($db instanceof Database) ? $db->getConnection() : $db;
        
        $sql = "
            SELECT
                COALESCE(SUM(COALESCE(bg.quantity, 1)), 0) AS total_items,
                COALESCE(SUM(
                    COALESCE(bg.custom_weight, gi.weight, 0) * COALESCE(bg.quantity, 1)
                ), 0) AS total_weight_g
            FROM backpack_gear bg
            LEFT JOIN gear_items gi ON bg.gear_id = gi.id
            WHERE bg.backpack_id = :id
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $backpackId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: ['total_items' => 0, 'total_weight_g' => 0];
    }
    
    /**
     * Update totals for a backpack
     * 
     * @param Database|PDO $db Database connection
     * @param int $backpackId Backpack ID
     * @param array|null $totals Optional pre-computed totals, will compute if not provided
     * @return bool Success status
     */
    public static function updateTotals($db, int $backpackId, ?array $totals = null): bool {
        // Handle both Database class and PDO directly
        $conn = ($db instanceof Database) ? $db->getConnection() : $db;
        
        // Compute totals if not provided
        if ($totals === null) {
            $totals = self::computeTotals($db, $backpackId);
        }
        
        $sql = "UPDATE backpacks 
                SET total_items = :ti, 
                    total_weight_g = :tw, 
                    updated_at = datetime('now') 
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':ti' => $totals['total_items'],
            ':tw' => $totals['total_weight_g'],
            ':id' => $backpackId
        ]);
    }
    
    /**
     * Get backpack with items for a specific user
     * 
     * @param Database|PDO $db Database connection
     * @param int $backpackId Backpack ID
     * @param int $userId User ID
     * @param bool $includeItems Whether to include items
     * @return array|null Backpack data or null if not found
     */
    public static function getWithItems($db, int $backpackId, int $userId, bool $includeItems = true): ?array {
        $conn = ($db instanceof Database) ? $db->getConnection() : $db;
        
        // Get the backpack
        $sql = "SELECT * FROM backpacks WHERE id = :id AND user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $backpackId, ':user_id' => $userId]);
        $backpack = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backpack) {
            return null;
        }
        
        if ($includeItems) {
            // Get items
            $itemsSql = "
                SELECT 
                    bg.id as item_id,
                    bg.gear_id,
                    bg.quantity,
                    bg.section,
                    bg.worn,
                    bg.consumable,
                    COALESCE(bg.custom_name, gi.name) as name,
                    COALESCE(bg.custom_weight, gi.weight) as weight_g,
                    COALESCE(bg.custom_category, gi.category) as category,
                    COALESCE(bg.custom_notes, gi.notes) as description,
                    COALESCE(bg.custom_brand, gi.brand) as brand
                FROM backpack_gear bg
                LEFT JOIN gear_items gi ON bg.gear_id = gi.id
                WHERE bg.backpack_id = :id
                ORDER BY bg.section, bg.position
            ";
            
            $stmt = $conn->prepare($itemsSql);
            $stmt->execute([':id' => $backpackId]);
            $backpack['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $backpack;
    }
    
    /**
     * Create items from a trip's backpack for the packing list
     * 
     * @param Database|PDO $db Database connection
     * @param int $tripId Trip ID
     * @param int $backpackId Backpack ID
     * @return bool Success status
     */
    public static function copyItemsToTripPacking($db, int $tripId, int $backpackId): bool {
        $conn = ($db instanceof Database) ? $db->getConnection() : $db;
        
        try {
            // First clear any existing packing items for this trip
            $clearSql = "DELETE FROM trip_packing WHERE trip_id = :trip_id";
            $stmt = $conn->prepare($clearSql);
            $stmt->execute([':trip_id' => $tripId]);
            
            // Copy items from backpack to trip packing list
            $copySql = "
                INSERT INTO trip_packing (
                    trip_id, 
                    item_name, 
                    weight_g, 
                    quantity, 
                    category, 
                    packed,
                    backpack_gear_id
                )
                SELECT 
                    :trip_id,
                    COALESCE(bg.custom_name, gi.name, 'Unnamed Item'),
                    COALESCE(bg.custom_weight, gi.weight, 0),
                    COALESCE(bg.quantity, 1),
                    COALESCE(bg.custom_category, gi.category, bg.section, 'other'),
                    0, -- not packed initially
                    bg.id
                FROM backpack_gear bg
                LEFT JOIN gear_items gi ON bg.gear_id = gi.id
                WHERE bg.backpack_id = :backpack_id
            ";
            
            $stmt = $conn->prepare($copySql);
            return $stmt->execute([
                ':trip_id' => $tripId,
                ':backpack_id' => $backpackId
            ]);
            
        } catch (\Exception $e) {
            error_log("Error copying items to trip packing: " . $e->getMessage());
            return false;
        }
    }
}
