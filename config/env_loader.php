<?php
// config/env_loader.php - Cas 2 : Fonction

class EnvFileMissingException extends Exception {}

function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        throw new EnvFileMissingException("Fichier .env manquant");
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($key, $value) = explode('=', $line, 2);
        define(trim($key), trim($value));
    }
}

// Utilisation
loadEnv(__DIR__ . '/../.env');

// config/env_loader.php - Cas 3 : Classe avancée
class EnvFileMissingExceptionAdvanced extends Exception {}

class Environment {
    private static $variables = [];
    private static $loaded = false;
    
    public static function load($filePath) {
        if (self::$loaded) return;
        
        if (!file_exists($filePath)) {
            throw new EnvFileMissingExceptionAdvanced("Fichier .env manquant");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            
            list($key, $value) = explode('=', $line, 2);
            self::$variables[trim($key)] = trim($value);
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        return self::$variables[$key] ?? $default;
    }
    
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
    
    public static function getBool($key, $default = false) {
        $value = strtolower(self::get($key, $default));
        return in_array($value, ['true', '1', 'yes', 'on']);
    }
}

// Utilisation
Environment::load(__DIR__ . '/../.env');
$dbHost = Environment::get('DB_HOST');

