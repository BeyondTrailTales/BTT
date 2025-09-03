<?php
/**
 * Seed Default Gear Data
 * 
 * Populates the gear_defaults table with curated gear items
 */

// Load configuration
require_once dirname(dirname(__DIR__)) . '/app/config.php';

class DefaultGearSeeder {
    private $db;
    private $seedFile;
    
    public function __construct() {
        $this->seedFile = BTT_STORAGE_PATH . '/sqlite/seeds/default_gear.json';
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            $dbFile = BTT_SQLITE_PATH;
            $this->db = new PDO('sqlite:' . $dbFile);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    public function seed() {
        echo "Seeding default gear...\n";
        
        // Check if seed file exists
        if (!file_exists($this->seedFile)) {
            echo "Seed file not found: {$this->seedFile}\n";
            return false;
        }
        
        // Load seed data
        $json = file_get_contents($this->seedFile);
        $gearItems = json_decode($json, true);
        
        if (!$gearItems) {
            echo "Failed to parse seed file\n";
            return false;
        }
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            $inserted = 0;
            $updated = 0;
            
            // Prepare upsert statement
            $sql = "INSERT INTO gear_defaults (
                        name, category, weight_g, unit, icon, 
                        description, brand, price, is_active
                    ) VALUES (
                        :name, :category, :weight_g, :unit, :icon,
                        :description, :brand, :price, 1
                    )
                    ON CONFLICT(name, category) DO UPDATE SET
                        weight_g = excluded.weight_g,
                        unit = excluded.unit,
                        icon = excluded.icon,
                        description = excluded.description,
                        brand = excluded.brand,
                        price = excluded.price,
                        updated_at = CURRENT_TIMESTAMP";
            
            // SQLite doesn't support ON CONFLICT with multiple columns
            // So we'll use a different approach
            $checkSql = "SELECT id FROM gear_defaults WHERE name = :name AND category = :category";
            $insertSql = "INSERT INTO gear_defaults (
                            name, category, weight_g, unit, icon, 
                            description, brand, price, is_active
                        ) VALUES (
                            :name, :category, :weight_g, :unit, :icon,
                            :description, :brand, :price, 1
                        )";
            $updateSql = "UPDATE gear_defaults SET
                            weight_g = :weight_g,
                            unit = :unit,
                            icon = :icon,
                            description = :description,
                            brand = :brand,
                            price = :price,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE name = :name AND category = :category";
            
            $checkStmt = $this->db->prepare($checkSql);
            $insertStmt = $this->db->prepare($insertSql);
            $updateStmt = $this->db->prepare($updateSql);
            
            foreach ($gearItems as $item) {
                // Check if item exists
                $checkStmt->execute([
                    ':name' => $item['name'],
                    ':category' => $item['category']
                ]);
                
                $exists = $checkStmt->fetchColumn();
                
                $params = [
                    ':name' => $item['name'],
                    ':category' => $item['category'],
                    ':weight_g' => $item['weight_g'] ?? null,
                    ':unit' => $item['unit'] ?? 'g',
                    ':icon' => $item['icon'] ?? null,
                    ':description' => $item['description'] ?? null,
                    ':brand' => $item['brand'] ?? null,
                    ':price' => $item['price'] ?? null
                ];
                
                if ($exists) {
                    // Update existing
                    $updateStmt->execute($params);
                    $updated++;
                    echo "Updated: {$item['name']} ({$item['category']})\n";
                } else {
                    // Insert new
                    $insertStmt->execute($params);
                    $inserted++;
                    echo "Inserted: {$item['name']} ({$item['category']})\n";
                }
            }
            
            $this->db->commit();
            
            echo "\nSeeding complete: $inserted inserted, $updated updated\n";
            
            // Show category breakdown
            $this->showCategoryBreakdown();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            echo "Seeding failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function showCategoryBreakdown() {
        echo "\nGear by category:\n";
        
        $sql = "SELECT category, COUNT(*) as count, 
                       SUM(weight_g) as total_weight,
                       AVG(weight_g) as avg_weight
                FROM gear_defaults 
                WHERE is_active = 1
                GROUP BY category 
                ORDER BY category";
        
        $stmt = $this->db->query($sql);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as $cat) {
            $avgWeight = $cat['avg_weight'] ? round($cat['avg_weight']) . 'g' : 'N/A';
            echo "  - {$cat['category']}: {$cat['count']} items (avg: {$avgWeight})\n";
        }
        
        // Total count
        $total = $this->db->query("SELECT COUNT(*) FROM gear_defaults WHERE is_active = 1")->fetchColumn();
        echo "\nTotal active items: $total\n";
    }
    
    public function clear() {
        echo "Clearing all default gear...\n";
        
        try {
            $count = $this->db->exec("DELETE FROM gear_defaults");
            echo "Removed $count items\n";
            return true;
        } catch (Exception $e) {
            echo "Failed to clear: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $seeder = new DefaultGearSeeder();
    
    // Check for command line arguments
    $command = $argv[1] ?? 'seed';
    
    switch ($command) {
        case 'clear':
            $seeder->clear();
            break;
        case 'refresh':
            $seeder->clear();
            $seeder->seed();
            break;
        case 'seed':
        default:
            $seeder->seed();
            break;
    }
} else {
    // Web execution
    try {
        $seeder = new DefaultGearSeeder();
        $seeder->seed();
        echo "<pre>Default gear seeded successfully.</pre>";
    } catch (Exception $e) {
        echo "<pre>Seeding error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
