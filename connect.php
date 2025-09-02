<?php
/**
 * Database Connection for Security Training Application
 * 
 * EDUCATIONAL NOTE: In production, credentials should be:
 * - Stored in environment variables or config files
 * - Use strong passwords
 * - Enable SSL connections
 * - Limit database user permissions
 */

// Load environment configuration
require_once __DIR__ . '/env_loader.php';

// Database configuration from environment variables
$dbConfig = [
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHARSET,
    'port' => DB_PORT ?? 3307 // Ajout du port

];

try {
    // Include port in DSN for MariaDB
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']} COLLATE utf8mb4_unicode_ci"
    ];
    
    $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
} catch (PDOException $e) {
    // Enhanced error handling for MariaDB connection issues
    $errorMsg = "Database connection failed: " . $e->getMessage();
    
    // Add helpful hints for common MariaDB connection issues
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        $errorMsg .= "\n\nVérifications à effectuer:\n";
        $errorMsg .= "- MariaDB est-il démarré dans WAMP ?\n";
        $errorMsg .= "- Le port 3307 est-il correct ?\n";
        $errorMsg .= "- La base 'phpsec' existe-t-elle ?\n";
    }
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("<pre>$errorMsg</pre>");
    } else {
        error_log($errorMsg);
        die("Erreur de connexion à la base de données");
    }
}
?>