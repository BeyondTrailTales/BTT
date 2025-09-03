<?php
/**
 * BeyondTrailTales Database Session Handler
 * 
 * Custom session handler using SQLite for secure session storage
 * Implements SessionHandlerInterface with enhanced security features
 */

namespace App\Services;

use PDO;
use PDOException;
use SessionHandlerInterface;

class DbSessionHandler implements SessionHandlerInterface {
    private $db;
    private $table = 'sessions';
    private $gcProbability = 1; // 1% chance of GC on each request
    private $maxLifetime = 7200; // 2 hours default
    private $userId = null;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db = null) {
        if ($db === null) {
            // Create a direct PDO connection to SQLite
            $dbPath = dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            
            try {
                $this->db = new PDO('sqlite:' . $dbPath);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                error_log("Failed to connect to database: " . $e->getMessage());
                // Fall back to using the Database class
                require_once dirname(dirname(__DIR__)) . '/api/classes/Database.php';
                $dbInstance = \Database::getInstance();
                if ($dbInstance) {
                    $this->db = $dbInstance->getConnection();
                }
            }
        } else {
            $this->db = $db;
        }
        
        // Set custom max lifetime from config if available
        if (defined('BTT_SESSION_LIFETIME')) {
            $this->maxLifetime = BTT_SESSION_LIFETIME;
        }
    }
    
    /**
     * Initialize session
     * 
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     */
    public function open($savePath, $sessionName): bool {
        // Run garbage collection based on probability
        if (mt_rand(1, 100) <= $this->gcProbability) {
            $this->gc($this->maxLifetime);
        }
        
        return true;
    }
    
    /**
     * Close session
     * 
     * @return bool
     */
    public function close(): bool {
        return true;
    }
    
    /**
     * Read session data
     * 
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId): string {
        try {
            // Check if database connection exists
            if (!$this->db) {
                error_log("Session read error: Database connection is null");
                return '';
            }
            
            $stmt = $this->db->prepare("
                SELECT payload, user_id 
                FROM {$this->table} 
                WHERE id = :id 
                AND last_activity > :expiry
            ");
            
            $stmt->execute([
                'id' => $sessionId,
                'expiry' => time() - $this->maxLifetime
            ]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Store user_id for later use
                $this->userId = $row['user_id'];
                return $row['payload'] ?? '';
            }
            
            return '';
        } catch (PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Write session data
     * 
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    public function write($sessionId, $data): bool {
        try {
            // Parse session data to extract user_id if logged in
            $userId = $this->extractUserId($data);
            
            // Get client information
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Check if session exists
            $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE id = :id");
            $stmt->execute(['id' => $sessionId]);
            
            if ($stmt->fetch()) {
                // Update existing session
                $sql = "
                    UPDATE {$this->table} 
                    SET payload = :payload,
                        user_id = :user_id,
                        ip_address = :ip_address,
                        user_agent = :user_agent,
                        last_activity = :last_activity,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ";
            } else {
                // Insert new session
                $sql = "
                    INSERT INTO {$this->table} 
                    (id, payload, user_id, ip_address, user_agent, last_activity, created_at, updated_at)
                    VALUES 
                    (:id, :payload, :user_id, :ip_address, :user_agent, :last_activity, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ";
            }
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $sessionId,
                'payload' => $data,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => substr($userAgent, 0, 500), // Limit user agent length
                'last_activity' => time()
            ]);
            
            // Log authentication events
            if ($userId !== $this->userId) {
                $this->logAuthEvent($userId, $ipAddress, $userAgent);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy session
     * 
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute(['id' => $sessionId]);
        } catch (PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Garbage collection
     * 
     * @param int $maxLifetime
     * @return int|false Number of deleted sessions or false on error
     */
    #[\ReturnTypeWillChange]
    public function gc($maxLifetime) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM {$this->table} 
                WHERE last_activity < :expiry
            ");
            
            $stmt->execute(['expiry' => time() - $maxLifetime]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Session GC error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy all sessions for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function destroyUserSessions($userId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
            return $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("User sessions destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active sessions for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserSessions($userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, ip_address, user_agent, last_activity, created_at
                FROM {$this->table}
                WHERE user_id = :user_id
                AND last_activity > :expiry
                ORDER BY last_activity DESC
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'expiry' => time() - $this->maxLifetime
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validate session against IP and User Agent
     * 
     * @param string $sessionId
     * @return bool
     */
    public function validateSession($sessionId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT ip_address, user_agent
                FROM {$this->table}
                WHERE id = :id
                AND last_activity > :expiry
            ");
            
            $stmt->execute([
                'id' => $sessionId,
                'expiry' => time() - $this->maxLifetime
            ]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return false;
            }
            
            // Validate IP address (optional - can be strict or loose)
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (defined('BTT_SESSION_IP_CHECK') && BTT_SESSION_IP_CHECK) {
                if ($row['ip_address'] !== $currentIp) {
                    // Log potential session hijacking
                    error_log("Session IP mismatch for session $sessionId");
                    return false;
                }
            }
            
            // Validate User Agent (loose check - browser family)
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (defined('BTT_SESSION_AGENT_CHECK') && BTT_SESSION_AGENT_CHECK) {
                if (!$this->compareUserAgents($row['user_agent'], $currentAgent)) {
                    // Log potential session hijacking
                    error_log("Session User-Agent mismatch for session $sessionId");
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract user_id from session data
     * 
     * @param string $data Serialized session data
     * @return int|null
     */
    private function extractUserId($data) {
        // Try to extract user_id from serialized session data
        // PHP session format: variable_name|serialized_data;
        
        if (empty($data)) {
            return null;
        }
        
        // Look for user_id in session data
        if (preg_match('/user_id\|i:(\d+);/', $data, $matches)) {
            return (int)$matches[1];
        }
        
        // Alternative: decode if using custom format
        $sessionData = @unserialize($data);
        if ($sessionData !== false && isset($sessionData['user_id'])) {
            return (int)$sessionData['user_id'];
        }
        
        return null;
    }
    
    /**
     * Compare user agents for session validation
     * 
     * @param string $stored
     * @param string $current
     * @return bool
     */
    private function compareUserAgents($stored, $current): bool {
        // Simple comparison - can be made more sophisticated
        // Extract browser family and major version
        
        $storedBrowser = $this->extractBrowserInfo($stored);
        $currentBrowser = $this->extractBrowserInfo($current);
        
        return $storedBrowser === $currentBrowser;
    }
    
    /**
     * Extract browser info from user agent
     * 
     * @param string $userAgent
     * @return string
     */
    private function extractBrowserInfo($userAgent): string {
        // Simplified browser detection
        if (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }
        
        return 'Unknown';
    }
    
    /**
     * Log authentication events
     * 
     * @param int|null $userId
     * @param string $ipAddress
     * @param string $userAgent
     */
    private function logAuthEvent($userId, $ipAddress, $userAgent) {
        if ($userId && $userId !== $this->userId) {
            try {
                $action = $this->userId === null ? 'login' : 'logout';
                
                $stmt = $this->db->prepare("
                    INSERT INTO user_activity_log 
                    (user_id, action, ip_address, user_agent, created_at)
                    VALUES 
                    (:user_id, :action, :ip_address, :user_agent, CURRENT_TIMESTAMP)
                ");
                
                $stmt->execute([
                    'user_id' => $userId ?: $this->userId,
                    'action' => $action,
                    'ip_address' => $ipAddress,
                    'user_agent' => substr($userAgent, 0, 500)
                ]);
            } catch (PDOException $e) {
                error_log("Auth event logging error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Regenerate session ID while preserving data
     * 
     * @param bool $deleteOld
     * @return bool
     */
    public function regenerateId($deleteOld = true): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($deleteOld);
        }
        return false;
    }
}
