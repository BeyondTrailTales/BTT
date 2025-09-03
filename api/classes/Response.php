<?php
/**
 * Response Class - Standardized API Responses
 * 
 * Handles all API response formatting
 * Following Context7 best practices
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'An error occurred', $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Log errors
        btt_log("API Error: $message" . ($errors ? " - " . json_encode($errors) : ""), 'ERROR');
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send method not allowed response
     */
    public static function methodNotAllowed($message = 'Method not allowed') {
        self::error($message, 405);
    }
    
    /**
     * Send conflict response
     */
    public static function conflict($message = 'Resource conflict') {
        self::error($message, 409);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send created response
     */
    public static function created($data = null, $message = 'Resource created successfully') {
        self::success($data, $message, 201);
    }
    
    /**
     * Send no content response
     */
    public static function noContent() {
        http_response_code(204);
        exit();
    }
    
    /**
     * Send rate limit exceeded response
     */
    public static function rateLimitExceeded($message = 'Rate limit exceeded') {
        self::error($message, 429);
    }
}
