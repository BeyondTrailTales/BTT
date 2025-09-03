<?php
/**
 * Storage Abstraction Layer
 * Handles both SQLite and JSON storage seamlessly
 */

namespace BTT\Test\Tools;

require_once dirname(dirname(__DIR__)) . '/app/config.php';

class Storage {
    private static $instance = null;
    private $driver;
    private $db = null;
    private $jsonPath;
    
    private function __construct() {
        // Detect storage driver from config
        $this->driver = $this->detectStorageDriver();
        
        if ($this->driver === 'sqlite') {
            $this->initSQLite();
        } else {
            $this->initJSON();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function detectStorageDriver() {
        // Check if SQLite is configured and available
        if (defined('BTT_DB_PATH') && file_exists(BTT_DB_PATH)) {
            return 'sqlite';
        }
        
        // Check for Database class and its configuration
        $dbClass = dirname(dirname(__DIR__)) . '/api/classes/Database.php';
        if (file_exists($dbClass)) {
            require_once $dbClass;
            if (class_exists('Database')) {
                $db = \Database::getInstance();
                if ($db->isSQLite()) {
                    return 'sqlite';
                }
            }
        }
        
        // Default to JSON
        return 'json';
    }
    
    private function initSQLite() {
        try {
            $dbPath = defined('BTT_DB_PATH') ? BTT_DB_PATH : dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            $this->db = new \PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            throw new \Exception('Failed to initialize SQLite: ' . $e->getMessage());
        }
    }
    
    private function initJSON() {
        $this->jsonPath = dirname(dirname(__DIR__)) . '/storage/json/';
        if (!is_dir($this->jsonPath)) {
            mkdir($this->jsonPath, 0755, true);
        }
    }
    
    public function getDriver() {
        return $this->driver;
    }
    
    public function getStorageInfo() {
        $info = [
            'driver' => $this->driver,
            'path' => null,
            'size' => null,
            'tables' => [],
            'counts' => []
        ];
        
        if ($this->driver === 'sqlite') {
            $dbPath = defined('BTT_DB_PATH') ? BTT_DB_PATH : dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            $info['path'] = $dbPath;
            $info['size'] = file_exists($dbPath) ? filesize($dbPath) : 0;
            
            // Get table list
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $info['tables'] = $tables;
            
            // Get counts
            foreach ($tables as $table) {
                $stmt = $this->db->query("SELECT COUNT(*) FROM $table");
                $info['counts'][$table] = $stmt->fetchColumn();
            }
        } else {
            $info['path'] = $this->jsonPath;
            $info['size'] = $this->getDirectorySize($this->jsonPath);
            
            // Get JSON files
            $files = glob($this->jsonPath . '*.json');
            foreach ($files as $file) {
                $name = basename($file, '.json');
                $info['tables'][] = $name;
                
                $data = json_decode(file_get_contents($file), true);
                $info['counts'][$name] = is_array($data) ? count($data) : 0;
            }
        }
        
        return $info;
    }
    
    // CRUD Operations
    public function create($entity, $data) {
        if ($this->driver === 'sqlite') {
            return $this->createSQLite($entity, $data);
        } else {
            return $this->createJSON($entity, $data);
        }
    }
    
    public function read($entity, $id = null) {
        if ($this->driver === 'sqlite') {
            return $this->readSQLite($entity, $id);
        } else {
            return $this->readJSON($entity, $id);
        }
    }
    
    public function update($entity, $id, $data) {
        if ($this->driver === 'sqlite') {
            return $this->updateSQLite($entity, $id, $data);
        } else {
            return $this->updateJSON($entity, $id, $data);
        }
    }
    
    public function delete($entity, $id) {
        if ($this->driver === 'sqlite') {
            return $this->deleteSQLite($entity, $id);
        } else {
            return $this->deleteJSON($entity, $id);
        }
    }
    
    // SQLite implementations
    private function createSQLite($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ":$col"; }, $columns);
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    private function readSQLite($table, $id = null) {
        if ($id === null) {
            $stmt = $this->db->query("SELECT * FROM $table");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }
    
    private function updateSQLite($table, $id, $data) {
        $sets = array_map(function($col) { return "$col = :$col"; }, array_keys($data));
        
        $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':id', $id);
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        return $stmt->execute();
    }
    
    private function deleteSQLite($table, $id) {
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
    
    // JSON implementations
    private function createJSON($entity, $data) {
        $file = $this->jsonPath . $entity . '.json';
        $items = [];
        
        if (file_exists($file)) {
            $items = json_decode(file_get_contents($file), true) ?: [];
        }
        
        // Auto-increment ID
        $maxId = 0;
        foreach ($items as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = $item['id'];
            }
        }
        
        $data['id'] = $maxId + 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $items[] = $data;
        file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT));
        
        return $data['id'];
    }
    
    private function readJSON($entity, $id = null) {
        $file = $this->jsonPath . $entity . '.json';
        
        if (!file_exists($file)) {
            return $id === null ? [] : null;
        }
        
        $items = json_decode(file_get_contents($file), true) ?: [];
        
        if ($id === null) {
            return $items;
        }
        
        foreach ($items as $item) {
            if (isset($item['id']) && $item['id'] == $id) {
                return $item;
            }
        }
        
        return null;
    }
    
    private function updateJSON($entity, $id, $data) {
        $file = $this->jsonPath . $entity . '.json';
        
        if (!file_exists($file)) {
            return false;
        }
        
        $items = json_decode(file_get_contents($file), true) ?: [];
        
        foreach ($items as &$item) {
            if (isset($item['id']) && $item['id'] == $id) {
                $item = array_merge($item, $data);
                $item['updated_at'] = date('Y-m-d H:i:s');
                file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT));
                return true;
            }
        }
        
        return false;
    }
    
    private function deleteJSON($entity, $id) {
        $file = $this->jsonPath . $entity . '.json';
        
        if (!file_exists($file)) {
            return false;
        }
        
        $items = json_decode(file_get_contents($file), true) ?: [];
        $filtered = array_filter($items, function($item) use ($id) {
            return !isset($item['id']) || $item['id'] != $id;
        });
        
        if (count($filtered) < count($items)) {
            file_put_contents($file, json_encode(array_values($filtered), JSON_PRETTY_PRINT));
            return true;
        }
        
        return false;
    }
    
    // Utility methods
    private function getDirectorySize($dir) {
        $size = 0;
        $files = glob($dir . '*', GLOB_MARK);
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                $size += $this->getDirectorySize($file);
            } else {
                $size += filesize($file);
            }
        }
        
        return $size;
    }
    
    public function clearTestData() {
        if ($this->driver === 'sqlite') {
            // Clear test-specific data
            $tables = ['backpacks', 'trips', 'gamification'];
            foreach ($tables as $table) {
                try {
                    $this->db->exec("DELETE FROM $table WHERE id > 100"); // Keep seeded data
                } catch (\Exception $e) {
                    // Table might not exist
                }
            }
        } else {
            // Backup and clear JSON files
            $files = glob($this->jsonPath . '*.json');
            foreach ($files as $file) {
                $backup = $file . '.backup';
                copy($file, $backup);
                file_put_contents($file, '[]');
            }
        }
    }
}
