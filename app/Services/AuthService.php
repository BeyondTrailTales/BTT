<?php
/**
 * BeyondTrailTales Authentication Service
 * 
 * Comprehensive authentication service with secure password handling,
 * session management, and remember me functionality
 */

namespace App\Services;

use PDO;
use PDOException;
use Exception;

class AuthService {
    private static $db = null;
    private static $sessionHandler = null;
    private static $rateLimiter = null;
    
    // Configuration constants
    private const PASSWORD_MIN_LENGTH = 8; // Simplified for testing
    private const PASSWORD_BCRYPT_COST = 12;
    private const REMEMBER_TOKEN_LENGTH = 64;
    private const REMEMBER_COOKIE_NAME = 'BTT_REMEMBER';
    private const REMEMBER_COOKIE_DAYS = 30;
    private const EMAIL_VERIFICATION_HOURS = 24;
    private const PASSWORD_RESET_HOURS = 1;
    
    /**
     * Initialize the service
     */
    private static function init() {
        if (self::$db === null) {
            // Create a direct PDO connection to SQLite
            $dbPath = dirname(dirname(__DIR__)) . '/storage/sqlite/btt.db';
            
            try {
                self::$db = new \PDO('sqlite:' . $dbPath);
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$db->exec('PRAGMA foreign_keys = ON');
            } catch (\PDOException $e) {
                // Fall back to Database class
                require_once dirname(dirname(__DIR__)) . '/api/classes/Database.php';
                $dbInstance = \Database::getInstance();
                if ($dbInstance) {
                    self::$db = $dbInstance->getConnection();
                }
                
                if (!self::$db) {
                    throw new \Exception('Failed to initialize database connection: ' . $e->getMessage());
                }
            }
        }
        
        if (self::$sessionHandler === null) {
            require_once __DIR__ . '/DbSessionHandler.php';
            self::$sessionHandler = new DbSessionHandler(self::$db);
        }
    }
    
    /**
     * Register a new user
     * 
     * @param string $email
     * @param string $username
     * @param string $password
     * @return array Result with success status and message
     */
    public static function register($email, $username, $password) {
        self::init();
        
        try {
            // Validate input
            $validation = self::validateRegistration($email, $username, $password);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            // Check if email already exists
            $stmt = self::$db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'errors' => ['email' => 'Email already registered']];
            }
            
            // Check if username already exists
            $stmt = self::$db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'errors' => ['username' => 'Username already taken']];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::PASSWORD_BCRYPT_COST]);
            
            // Insert user (with email_verified_at set for now to skip verification)
            $stmt = self::$db->prepare("
                INSERT INTO users (email, username, password_hash, email_verified_at, created_at, updated_at)
                VALUES (:email, :username, :password_hash, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                'email' => $email,
                'username' => $username,
                'password_hash' => $passwordHash
            ]);
            
            $userId = self::$db->lastInsertId();
            
            // Generate email verification token
            $verificationToken = self::generateEmailVerificationToken($userId);
            
            // Send verification email using Mailer service
            require_once __DIR__ . '/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendVerificationEmail($email, $username, $verificationToken);
            
            // Log registration
            self::logActivity($userId, 'register', 'User registered');
            
            return [
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    /**
     * Login user
     * 
     * @param string $login Email or username
     * @param string $password
     * @param bool $remember
     * @return array Result with success status
     */
    public static function login($login, $password, $remember = false) {
        self::init();
        
        try {
            // Check rate limiting
            if (self::isRateLimited($login)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            // Find user by email or username
            $stmt = self::$db->prepare("
                SELECT id, email, username, password_hash, email_verified_at
                FROM users 
                WHERE email = :login OR username = :login
                LIMIT 1
            ");
            $stmt->execute(['login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                self::recordLoginAttempt($login, false);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                self::recordLoginAttempt($login, false);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Skip email verification check for now
            // if (empty($user['email_verified_at'])) {
            //     return ['success' => false, 'message' => 'Please verify your email before logging in.'];
            // }
            
            // Clear login attempts
            self::clearLoginAttempts($login);
            
            // Start session if not started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables BEFORE regenerating ID
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['user_name'] = $user['username']; // Add this for dashboard
            
            // Regenerate session ID for security (keep old session data)
            session_regenerate_id(true);
            
            // Force session write to ensure data is saved
            session_write_close();
            session_start();
            
            // Handle remember me
            if ($remember) {
                self::setRememberCookie($user['id']);
            }
            
            // Update last login
            $stmt = self::$db->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            
            // Log activity
            self::logActivity($user['id'], 'login', 'User logged in');
            
            // Get CSRF token for response
            require_once __DIR__ . '/Csrf.php';
            $csrfToken = Csrf::generateToken(true);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ],
                'csrf_token' => $csrfToken
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Logout user
     * 
     * @return bool
     */
    public static function logout() {
        self::init();
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            
            if ($userId) {
                // Clear remember token
                self::clearRememberToken($userId);
                
                // Log activity
                self::logActivity($userId, 'logout', 'User logged out');
            }
            
            // Clear session
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Delete remember cookie
            self::clearRememberCookie();
            
            // Destroy session
            session_destroy();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email with token
     * 
     * @param string $token
     * @return array Result
     */
    public static function verifyEmail($token) {
        self::init();
        
        try {
            // Find token
            $stmt = self::$db->prepare("
                SELECT user_id, expires_at
                FROM email_verifications
                WHERE token_hash = :token_hash
                AND verified_at IS NULL
                LIMIT 1
            ");
            
            $tokenHash = hash('sha256', $token);
            $stmt->execute(['token_hash' => $tokenHash]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verification) {
                return ['success' => false, 'message' => 'Invalid or expired verification token'];
            }
            
            // Check expiry
            if (strtotime($verification['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification token has expired'];
            }
            
            // Mark email as verified
            self::$db->beginTransaction();
            
            $stmt = self::$db->prepare("
                UPDATE users 
                SET email_verified_at = CURRENT_TIMESTAMP 
                WHERE id = :user_id
            ");
            $stmt->execute(['user_id' => $verification['user_id']]);
            
            $stmt = self::$db->prepare("
                UPDATE email_verifications 
                SET verified_at = CURRENT_TIMESTAMP 
                WHERE token_hash = :token_hash
            ");
            $stmt->execute(['token_hash' => $tokenHash]);
            
            self::$db->commit();
            
            // Log activity
            self::logActivity($verification['user_id'], 'verify_email', 'Email verified');
            
            return ['success' => true, 'message' => 'Email verified successfully. You can now log in.'];
            
        } catch (Exception $e) {
            if (self::$db->inTransaction()) {
                self::$db->rollBack();
            }
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed. Please try again.'];
        }
    }
    
    /**
     * Request password reset
     * 
     * @param string $email
     * @return array Result
     */
    public static function requestPasswordReset($email) {
        self::init();
        
        try {
            // Find user
            $stmt = self::$db->prepare("SELECT id, username FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Always return success to prevent email enumeration
            if (!$user) {
                return ['success' => true, 'message' => 'If that email exists, a reset link has been sent.'];
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::PASSWORD_RESET_HOURS . ' hours'));
            
            // Store token
            $stmt = self::$db->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
                VALUES (:user_id, :token_hash, :expires_at, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                'user_id' => $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt
            ]);
            
            // Send reset email using Mailer service
            require_once __DIR__ . '/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendPasswordResetEmail($email, $user['username'], $token);
            
            // Log activity
            self::logActivity($user['id'], 'password_reset_request', 'Password reset requested');
            
            return ['success' => true, 'message' => 'If that email exists, a reset link has been sent.'];
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to process request. Please try again.'];
        }
    }
    
    /**
     * Reset password with token
     * 
     * @param string $token
     * @param string $newPassword
     * @return array Result
     */
    public static function resetPassword($token, $newPassword) {
        self::init();
        
        try {
            // Validate password
            if (strlen($newPassword) < self::PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters'];
            }
            
            // Find token
            $stmt = self::$db->prepare("
                SELECT user_id, expires_at
                FROM password_resets
                WHERE token_hash = :token_hash
                AND used_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ");
            
            $tokenHash = hash('sha256', $token);
            $stmt->execute(['token_hash' => $tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset) {
                return ['success' => false, 'message' => 'Invalid or used reset token'];
            }
            
            // Check expiry
            if (strtotime($reset['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Reset token has expired'];
            }
            
            // Update password
            self::$db->beginTransaction();
            
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => self::PASSWORD_BCRYPT_COST]);
            
            $stmt = self::$db->prepare("
                UPDATE users 
                SET password_hash = :password_hash,
                    remember_token = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id
            ");
            $stmt->execute([
                'password_hash' => $passwordHash,
                'user_id' => $reset['user_id']
            ]);
            
            // Mark token as used
            $stmt = self::$db->prepare("
                UPDATE password_resets 
                SET used_at = CURRENT_TIMESTAMP 
                WHERE token_hash = :token_hash
            ");
            $stmt->execute(['token_hash' => $tokenHash]);
            
            // Invalidate all user sessions
            if (self::$sessionHandler) {
                self::$sessionHandler->destroyUserSessions($reset['user_id']);
            }
            
            self::$db->commit();
            
            // Log activity
            self::logActivity($reset['user_id'], 'password_reset', 'Password reset completed');
            
            return ['success' => true, 'message' => 'Password reset successfully. Please log in with your new password.'];
            
        } catch (Exception $e) {
            if (self::$db->inTransaction()) {
                self::$db->rollBack();
            }
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed. Please try again.'];
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
            return true;
        }
        
        // Check remember me cookie
        if (self::checkRememberCookie()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email']
        ];
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public static function getCurrentUserId() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user ID (alias for getCurrentUserId)
     * Shorter method name for convenience in API routes
     * 
     * @return int|null
     */
    public static function userId() {
        return self::getCurrentUserId();
    }
    
    /**
     * Validate registration input
     */
    private static function validateRegistration($email, $username, $password) {
        $errors = [];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        }
        
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors['username'] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
        }
        
        // Validate password
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Set remember me cookie
     */
    private static function setRememberCookie($userId) {
        $token = bin2hex(random_bytes(self::REMEMBER_TOKEN_LENGTH));
        $tokenHash = hash('sha256', $token);
        
        // Store hashed token in database
        $stmt = self::$db->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
        $stmt->execute(['token' => $tokenHash, 'id' => $userId]);
        
        // Set cookie
        $cookieValue = $userId . ':' . $token;
        $expiry = time() + (self::REMEMBER_COOKIE_DAYS * 24 * 60 * 60);
        
        setcookie(
            self::REMEMBER_COOKIE_NAME,
            $cookieValue,
            $expiry,
            '/BTT/',
            '',
            false, // Set to true for HTTPS
            true   // HttpOnly
        );
    }
    
    /**
     * Check remember me cookie
     */
    private static function checkRememberCookie() {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            return false;
        }
        
        self::init();
        
        try {
            list($userId, $token) = explode(':', $_COOKIE[self::REMEMBER_COOKIE_NAME], 2);
            $tokenHash = hash('sha256', $token);
            
            $stmt = self::$db->prepare("
                SELECT id, username, email 
                FROM users 
                WHERE id = :id AND remember_token = :token
            ");
            $stmt->execute(['id' => $userId, 'token' => $tokenHash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Restore session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Regenerate token for security
                self::setRememberCookie($user['id']);
                
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Remember cookie check error: " . $e->getMessage());
        }
        
        // Invalid cookie, clear it
        self::clearRememberCookie();
        return false;
    }
    
    /**
     * Clear remember token
     */
    private static function clearRememberToken($userId) {
        $stmt = self::$db->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }
    
    /**
     * Clear remember cookie
     */
    private static function clearRememberCookie() {
        if (isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            setcookie(self::REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
        }
    }
    
    /**
     * Generate email verification token
     */
    private static function generateEmailVerificationToken($userId) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::EMAIL_VERIFICATION_HOURS . ' hours'));
        
        $stmt = self::$db->prepare("
            INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, :expires_at, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);
        
        return $token;
    }
    
    /**
     * Check if login is rate limited
     */
    private static function isRateLimited($login) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = self::$db->prepare("
            SELECT attempts, locked_until
            FROM login_attempts
            WHERE (email = :login OR ip_address = :ip)
            AND last_attempt_at > datetime('now', '-15 minutes')
            ORDER BY attempts DESC
            LIMIT 1
        ");
        
        $stmt->execute(['login' => $login, 'ip' => $ipAddress]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($attempt) {
            // Check if locked
            if ($attempt['locked_until'] && strtotime($attempt['locked_until']) > time()) {
                return true;
            }
            
            // Lock after 5 attempts
            if ($attempt['attempts'] >= 5) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Record login attempt
     */
    private static function recordLoginAttempt($login, $success) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (!$success) {
            // Check existing attempts
            $stmt = self::$db->prepare("
                SELECT id, attempts
                FROM login_attempts
                WHERE email = :email AND ip_address = :ip
                ORDER BY last_attempt_at DESC
                LIMIT 1
            ");
            
            $stmt->execute(['email' => $login, 'ip' => $ipAddress]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing && $existing['attempts'] < 5) {
                // Update existing record
                $attempts = $existing['attempts'] + 1;
                $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
                
                $stmt = self::$db->prepare("
                    UPDATE login_attempts 
                    SET attempts = :attempts,
                        locked_until = :locked,
                        last_attempt_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    'attempts' => $attempts,
                    'locked' => $lockedUntil,
                    'id' => $existing['id']
                ]);
            } else {
                // Insert new record
                $stmt = self::$db->prepare("
                    INSERT INTO login_attempts (email, ip_address, attempts, last_attempt_at)
                    VALUES (:email, :ip, 1, CURRENT_TIMESTAMP)
                ");
                
                $stmt->execute(['email' => $login, 'ip' => $ipAddress]);
            }
        }
    }
    
    /**
     * Clear login attempts
     */
    private static function clearLoginAttempts($login) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = self::$db->prepare("
            DELETE FROM login_attempts 
            WHERE email = :email OR ip_address = :ip
        ");
        
        $stmt->execute(['email' => $login, 'ip' => $ipAddress]);
    }
    
    // Email methods have been moved to Mailer service
    
    /**
     * Log user activity
     */
    private static function logActivity($userId, $action, $details = null) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = self::$db->prepare("
                INSERT INTO user_activity_log 
                (user_id, action, ip_address, user_agent, details, created_at)
                VALUES 
                (:user_id, :action, :ip_address, :user_agent, :details, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $ipAddress,
                'user_agent' => substr($userAgent, 0, 500),
                'details' => $details ? json_encode(['message' => $details]) : null
            ]);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
}
