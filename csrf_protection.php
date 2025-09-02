<?php
/**
 * CSRF Protection System
 * 
 * This class provides Cross-Site Request Forgery protection
 * through secure token generation and validation.
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenLifetime = 3600; // 1 hour
    
    /**
     * Generate a new CSRF token
     * 
     * @return string The generated token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $expiry = time() + self::$tokenLifetime;
        
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'expiry' => $expiry
        ];
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName]) || !is_array($_SESSION[self::$tokenName])) {
            return false;
        }
        
        $sessionData = $_SESSION[self::$tokenName];
        
        // Check if token exists and hasn't expired
        if (!isset($sessionData['token']) || !isset($sessionData['expiry'])) {
            return false;
        }
        
        if (time() > $sessionData['expiry']) {
            unset($_SESSION[self::$tokenName]);
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionData['token'], $token);
    }
    
    /**
     * Get HTML input field for CSRF token
     * 
     * @return string HTML input field
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }
    
    /**
     * Validate CSRF token from POST/GET data
     * 
     * @param array $data Usually $_POST or $_GET
     * @return bool True if valid, false otherwise
     */
    public static function validateFromData($data) {
        if (!isset($data[self::$tokenName])) {
            return false;
        }
        
        return self::validateToken($data[self::$tokenName]);
    }
    
    /**
     * Clean expired tokens
     */
    public static function cleanExpiredTokens() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[self::$tokenName]) && 
            is_array($_SESSION[self::$tokenName]) && 
            isset($_SESSION[self::$tokenName]['expiry']) && 
            time() > $_SESSION[self::$tokenName]['expiry']) {
            unset($_SESSION[self::$tokenName]);
        }
    }
    
    /**
     * Get current token value (if exists and valid)
     * 
     * @return string|null Token value or null if invalid/expired
     */
    public static function getCurrentToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName]) || !is_array($_SESSION[self::$tokenName])) {
            return null;
        }
        
        $sessionData = $_SESSION[self::$tokenName];
        
        if (!isset($sessionData['token']) || !isset($sessionData['expiry'])) {
            return null;
        }
        
        if (time() > $sessionData['expiry']) {
            unset($_SESSION[self::$tokenName]);
            return null;
        }
        
        return $sessionData['token'];
    }
}
?>