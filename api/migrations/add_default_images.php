<?php
/**
 * Migration: Add default image fields to trips table
 * 
 * Adds default_image_url and default_image_alt columns to support
 * pre-selected trail images from the gallery
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';

function runMigration() {
    try {
        $db = Database::getInstance();
        
        if (!$db->isSQLite()) {
            // For JSON storage, we don't need schema changes
            return [
                'success' => true,
                'message' => 'Using JSON storage - fields will be added automatically'
            ];
        }
        
        $conn = $db->getConnection();
        
        // Check if columns already exist
        $result = $conn->query("PRAGMA table_info(trips)");
        $columns = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        
        // Add default_image_url column if it doesn't exist
        if (!in_array('default_image_url', $columns)) {
            $conn->exec("ALTER TABLE trips ADD COLUMN default_image_url TEXT");
            echo "Added default_image_url column\n";
        } else {
            echo "default_image_url column already exists\n";
        }
        
        // Add default_image_alt column if it doesn't exist
        if (!in_array('default_image_alt', $columns)) {
            $conn->exec("ALTER TABLE trips ADD COLUMN default_image_alt TEXT");
            echo "Added default_image_alt column\n";
        } else {
            echo "default_image_alt column already exists\n";
        }
        
        return [
            'success' => true,
            'message' => 'Migration completed successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage()
        ];
    }
}

// Run migration
$result = runMigration();

if (PHP_SAPI === 'cli') {
    // Command line output
    echo "\n" . ($result['success'] ? "✓ " : "✗ ") . $result['message'] . "\n\n";
} else {
    // Web output
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
}
