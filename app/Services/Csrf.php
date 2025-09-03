<?php
/**
 * BeyondTrailTales CSRF Protection Service
 * 
 * Provides CSRF token generation, validation, and rotation
 * Following OWASP guidelines and Context7 best practices
 */

namespace App\Services;

class Csrf {
    private const TOKEN_LENGTH = 32;
    private const TOKEN_SESSION_KEY = 'csrf_token';
    private const TOKEN_EXPIRY_KEY = 'csrf_token_expiry';
    private const TOKEN_LIFETIME = 3600; // 1 hour
    
    /**
     * Generate a new CSRF token
     * 
     * @param bool $force Force generation of a new token even if one exists
     * @return string The generated token
     */
    public static function generateToken($force = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if we have a valid token
        if (!$force && self::hasValidToken()) {
            return $_SESSION[self::TOKEN_SESSION_KEY];
        }
        
        // Generate new token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Store in session with expiry
        $_SESSION[self::TOKEN_SESSION_KEY] = $token;
        $_SESSION[self::TOKEN_EXPIRY_KEY] = time() + self::TOKEN_LIFETIME;
        
        return $token;
    }
    
    /**
     * Get the current CSRF token
     * 
     * @return string|null The current token or null if none exists
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (self::hasValidToken()) {
            return $_SESSION[self::TOKEN_SESSION_KEY];
        }
        
        // Generate a new token if none exists or expired
        return self::generateToken();
    }
    
    /**
     * Validate a CSRF token
     * 
     * @param string $token The token to validate
     * @param bool $consumeToken Whether to invalidate the token after validation
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token, $consumeToken = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token is provided
        if (empty($token)) {
            return false;
        }
        
        // Check if we have a session token
        if (!isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            return false;
        }
        
        // Check if token has expired
        if (!self::hasValidToken()) {
            return false;
        }
        
        // Timing-safe comparison
        $isValid = hash_equals($_SESSION[self::TOKEN_SESSION_KEY], $token);
        
        // Consume token if requested (single-use tokens for sensitive operations)
        if ($isValid && $consumeToken) {
            self::rotateToken();
        }
        
        return $isValid;
    }
    
    /**
     * Rotate the CSRF token (generate new one)
     * 
     * @return string The new token
     */
    public static function rotateToken() {
        return self::generateToken(true);
    }
    
    /**
     * Clear the CSRF token
     */
    public static function clearToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::TOKEN_SESSION_KEY]);
        unset($_SESSION[self::TOKEN_EXPIRY_KEY]);
    }
    
    /**
     * Get the CSRF token from the request
     * 
     * @return string|null The token from the request or null if not found
     */
    public static function getTokenFromRequest() {
        // Check header first (for AJAX requests)
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        if (isset($headers['X-Csrf-Token'])) {
            return $headers['X-Csrf-Token'];
        }
        
        // Check POST data
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // Check JSON body for API requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $rawData = file_get_contents('php://input');
                $data = json_decode($rawData, true);
                if (isset($data['csrf_token'])) {
                    return $data['csrf_token'];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Validate the CSRF token from the current request
     * 
     * @param bool $consumeToken Whether to invalidate the token after validation
     * @return bool True if valid, false otherwise
     */
    public static function validateRequest($consumeToken = false) {
        $token = self::getTokenFromRequest();
        return self::validateToken($token, $consumeToken);
    }
    
    /**
     * Generate a hidden input field with the CSRF token
     * 
     * @return string HTML input field
     */
    public static function getHiddenField() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Generate a meta tag with the CSRF token
     * 
     * @return string HTML meta tag
     */
    public static function getMetaTag() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Check if we have a valid token in session
     * 
     * @return bool True if valid token exists
     */
    private static function hasValidToken() {
        if (!isset($_SESSION[self::TOKEN_SESSION_KEY]) || !isset($_SESSION[self::TOKEN_EXPIRY_KEY])) {
            return false;
        }
        
        // Check expiry
        if ($_SESSION[self::TOKEN_EXPIRY_KEY] < time()) {
            // Token expired, clear it
            self::clearToken();
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify request method needs CSRF protection
     * 
     * @param string $method The HTTP method
     * @return bool True if method needs CSRF protection
     */
    public static function methodNeedsProtection($method = null) {
        if ($method === null) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }
        
        $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        return in_array(strtoupper($method), $protectedMethods, true);
    }
    
    /**
     * Get JavaScript code to include CSRF token in AJAX requests
     * 
     * @return string JavaScript code
     */
    public static function getAjaxScript() {
        $token = self::getToken();
        return "
        <script>
            // CSRF token for AJAX requests
            window.csrfToken = '" . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . "';
            
            // Add CSRF token to all AJAX requests
            if (typeof $ !== 'undefined' && $.ajaxSetup) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-Token': window.csrfToken
                    }
                });
            }
            
            // For fetch API
            window.fetchWithCsrf = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = window.csrfToken;
                return fetch(url, options);
            };
        </script>
        ";
    }
}
