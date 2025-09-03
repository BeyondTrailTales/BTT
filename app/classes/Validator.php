<?php
declare(strict_types=1);

/**
 * Validator Class - Input Validation and Sanitization
 * 
 * Provides comprehensive validation and sanitization methods
 * Following OWASP best practices for security
 */

class Validator
{
    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $input, int $maxLength = 255): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        if (mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);
        
        return $input;
    }
    
    /**
     * Sanitize HTML output to prevent XSS
     */
    public static function escape(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate and sanitize integer
     */
    public static function sanitizeInt($input, ?int $min = null, ?int $max = null): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }
        
        $filtered = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($filtered === false) {
            return null;
        }
        
        if ($min !== null && $filtered < $min) {
            return $min;
        }
        
        if ($max !== null && $filtered > $max) {
            return $max;
        }
        
        return $filtered;
    }
    
    /**
     * Validate and sanitize float
     */
    public static function sanitizeFloat($input, ?float $min = null, ?float $max = null): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }
        
        $filtered = filter_var($input, FILTER_VALIDATE_FLOAT);
        
        if ($filtered === false) {
            return null;
        }
        
        if ($min !== null && $filtered < $min) {
            return $min;
        }
        
        if ($max !== null && $filtered > $max) {
            return $max;
        }
        
        return $filtered;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }
        
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }
        
        return strtolower($email);
    }
    
    /**
     * Validate date format
     */
    public static function validateDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }
        
        $d = DateTime::createFromFormat($format, $date);
        
        if ($d && $d->format($format) === $date) {
            return $date;
        }
        
        return null;
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        
        // Allow relative URLs for internal use
        if (strpos($url, '/') === 0) {
            return self::sanitizeString($url, 500);
        }
        
        $filtered = filter_var($url, FILTER_VALIDATE_URL);
        
        if ($filtered === false) {
            return null;
        }
        
        // Only allow http/https protocols
        $parsed = parse_url($filtered);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }
        
        return $filtered;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes, int $maxSize): array
    {
        $errors = [];
        
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file sent';
                return ['valid' => false, 'errors' => $errors];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File size exceeds limit';
                return ['valid' => false, 'errors' => $errors];
            default:
                $errors[] = 'Unknown upload error';
                return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            $errors[] = 'File type not allowed';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'json' => 'application/json'
        ];
        
        if (isset($mimeMap[$ext]) && $mimeType !== $mimeMap[$ext]) {
            $errors[] = 'File content does not match extension';
            return ['valid' => false, 'errors' => $errors];
        }
        
        return ['valid' => true, 'extension' => $ext, 'mime' => $mimeType];
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        $token = self::generateToken();
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    /**
     * Validate array of required fields
     */
    public static function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize file name
     */
    public static function sanitizeFileName(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Limit length
        if (strlen($filename) > 100) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = substr($name, 0, 100 - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Validate enum value
     */
    public static function validateEnum($value, array $allowedValues)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (!in_array($value, $allowedValues, true)) {
            return null;
        }
        
        return $value;
    }
}
