<?php
/**
 * Test Suite Logger
 * Centralized logging for all test operations
 */

namespace BTT\Test\Tools;

class TestLogger {
    private static $instance = null;
    private $logFile;
    private $maxFileSize = 1048576; // 1MB
    private $maxFiles = 5;
    private $correlationId;
    
    private function __construct() {
        $this->logFile = dirname(dirname(__DIR__)) . '/storage/logs/test-suite.log';
        $this->correlationId = uniqid('test-', true);
        $this->ensureLogDirectory();
        $this->rotateLogsIfNeeded();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setCorrelationId($id) {
        $this->correlationId = $id;
    }
    
    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($context) ? json_encode($context) : '{}';
        
        $logEntry = sprintf(
            "[%s] [%s] [%s] %s | Context: %s\n",
            $timestamp,
            $level,
            $this->correlationId,
            $message,
            $contextJson
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function rotateLogsIfNeeded() {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $fileSize = filesize($this->logFile);
        if ($fileSize < $this->maxFileSize) {
            return;
        }
        
        // Rotate existing logs
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile); // Delete oldest
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current log to .1
        rename($this->logFile, $this->logFile . '.1');
    }
    
    public function getRecentLogs($limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -$limit);
        
        $logs = [];
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)\] (.*?) \| Context: (.*)/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'correlation_id' => $matches[3],
                    'message' => $matches[4],
                    'context' => json_decode($matches[5], true)
                ];
            }
        }
        
        return array_reverse($logs);
    }
}
