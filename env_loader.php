<?php
/**
 * Environment Variables Loader
 * 
 * This class loads environment variables from a .env file
 * and makes them available as constants throughout the application.
 */

class EnvLoader {
    private static $envVars = [];
    private static $loaded = false;
    
    /**
     * Load environment variables from .env file
     * 
     * @param string $filePath Path to the .env file
     * @return bool Success status
     */
    public static function load($filePath = '.env') {
        if (self::$loaded) {
            return true;
        }
        
        if (!file_exists($filePath)) {
            throw new Exception("Environment file not found: " . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments and empty lines
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^["\'](.+)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Store in array and define as constant
                self::$envVars[$key] = $value;
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
        
        self::$loaded = true;
        return true;
    }
    
    /**
     * Get environment variable value
     * 
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed Variable value or default
     */
    public static function get($key, $default = null) {
        return self::$envVars[$key] ?? $default;
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has($key) {
        return isset(self::$envVars[$key]);
    }
    
    /**
     * Get all environment variables
     * 
     * @return array
     */
    public static function all() {
        return self::$envVars;
    }
}

// Auto-load environment variables
try {
    EnvLoader::load(__DIR__ . '/.env');
} catch (Exception $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Environment loading error: ' . $e->getMessage());
    } else {
        die('Configuration error occurred');
    }
}
?>