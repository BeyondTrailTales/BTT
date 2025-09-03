<?php
/**
 * Database Class - SQLite Connection Management
 * 
 * Singleton pattern for database connections
 * Following Context7 best practices
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $is_sqlite = true;
    
    private function __construct() {
        if (STORAGE_ENGINE === 'sqlite' && extension_loaded('pdo_sqlite')) {
            $this->initSQLite();
        } else {
            $this->is_sqlite = false;
            $this->initJSON();
        }
    }
    
    /**
     * Initialize SQLite connection
     */
    private function initSQLite() {
        try {
            $this->connection = new PDO('sqlite:' . DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            // Optimize for performance
            $this->connection->exec('PRAGMA journal_mode = WAL');
            $this->connection->exec('PRAGMA synchronous = NORMAL');
            
        } catch (PDOException $e) {
            btt_log("Database connection failed: " . $e->getMessage(), 'ERROR');
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Initialize JSON storage fallback
     */
    private function initJSON() {
        // Ensure JSON files exist
        $json_files = [
            'trips' => BTT_JSON_PATH . '/trips.json',
            'backpacks' => BTT_JSON_PATH . '/backpacks.json'
        ];
        
        foreach ($json_files as $name => $path) {
            if (!file_exists($path)) {
                file_put_contents($path, '[]', LOCK_EX);
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Check if using SQLite
     */
    public function isSQLite() {
        return $this->is_sqlite;
    }
    
    /**
     * Execute a query (SQLite)
     */
    public function query($sql, $params = []) {
        if (!$this->is_sqlite) {
            throw new Exception("Query method only available for SQLite");
        }
        
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->connection->errorInfo()[2]);
        }
        
        foreach ($params as $key => $value) {
            $param_key = is_numeric($key) ? $key + 1 : ':' . $key;
            $stmt->bindValue($param_key, $value);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Query failed: " . $stmt->errorInfo()[2]);
        }
        
        return $stmt;
    }
    
    /**
     * Get all results from a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get single result from a query
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data) {
        if ($this->is_sqlite) {
            // Validate table name to prevent SQL injection
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                throw new InvalidArgumentException('Invalid table name');
            }
            
            $columns = array_keys($data);
            // Validate column names
            foreach ($columns as $col) {
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                    throw new InvalidArgumentException('Invalid column name: ' . $col);
                }
            }
            
            $placeholders = array_map(function($col) { return ':' . $col; }, $columns);
            
            $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $this->query($sql, $data);
            return $this->connection->lastInsertId();
        } else {
            return $this->insertJSON($table, $data);
        }
    }
    
    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = []) {
        if ($this->is_sqlite) {
            // Validate table name
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                throw new InvalidArgumentException('Invalid table name');
            }
            
            $set = [];
            foreach ($data as $column => $value) {
                // Validate column names
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                    throw new InvalidArgumentException('Invalid column name: ' . $column);
                }
                $set[] = "`$column` = :set_$column";
            }
            
            $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
            
            $params = [];
            foreach ($data as $column => $value) {
                $params['set_' . $column] = $value;
            }
            
            $params = array_merge($params, $whereParams);
            
            return $this->query($sql, $params);
        } else {
            return $this->updateJSON($table, $data, $where, $whereParams);
        }
    }
    
    /**
     * Delete data
     */
    public function delete($table, $where, $params = []) {
        if ($this->is_sqlite) {
            // Validate table name
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                throw new InvalidArgumentException('Invalid table name');
            }
            
            $sql = "DELETE FROM `$table` WHERE $where";
            return $this->query($sql, $params);
        } else {
            return $this->deleteJSON($table, $where, $params);
        }
    }
    
    /**
     * JSON Storage Methods
     */
    private function getJSONData($table) {
        $file = BTT_JSON_PATH . "/$table.json";
        $data = file_get_contents($file);
        return json_decode($data, true) ?? [];
    }
    
    private function saveJSONData($table, $data) {
        $file = BTT_JSON_PATH . "/$table.json";
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    private function insertJSON($table, $data) {
        $records = $this->getJSONData($table);
        
        // Generate ID
        $max_id = 0;
        foreach ($records as $record) {
            if (isset($record['id']) && $record['id'] > $max_id) {
                $max_id = $record['id'];
            }
        }
        
        $data['id'] = $max_id + 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $records[] = $data;
        $this->saveJSONData($table, $records);
        
        return $data['id'];
    }
    
    private function updateJSON($table, $data, $where, $whereParams) {
        $records = $this->getJSONData($table);
        $updated = false;
        
        // Simple ID-based update for MVP
        if (preg_match('/id\s*=\s*:id/', $where) && isset($whereParams['id'])) {
            $id = $whereParams['id'];
            
            foreach ($records as &$record) {
                if ($record['id'] == $id) {
                    foreach ($data as $key => $value) {
                        $record[$key] = $value;
                    }
                    $record['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }
        }
        
        if ($updated) {
            $this->saveJSONData($table, $records);
        }
        
        return $updated;
    }
    
    private function deleteJSON($table, $where, $params) {
        $records = $this->getJSONData($table);
        $filtered = [];
        
        // Simple ID-based delete for MVP
        if (preg_match('/id\s*=\s*:id/', $where) && isset($params['id'])) {
            $id = $params['id'];
            
            foreach ($records as $record) {
                if ($record['id'] != $id) {
                    $filtered[] = $record;
                }
            }
        }
        
        $this->saveJSONData($table, $filtered);
        return count($records) > count($filtered);
    }
    
    /**
     * Begin transaction (SQLite only)
     */
    public function beginTransaction() {
        if ($this->is_sqlite) {
            $this->connection->exec('BEGIN TRANSACTION');
        }
    }
    
    /**
     * Commit transaction (SQLite only)
     */
    public function commit() {
        if ($this->is_sqlite) {
            $this->connection->exec('COMMIT');
        }
    }
    
    /**
     * Rollback transaction (SQLite only)
     */
    public function rollback() {
        if ($this->is_sqlite) {
            $this->connection->exec('ROLLBACK');
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->is_sqlite && $this->connection) {
            $this->connection = null; // PDO closes on null
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}
