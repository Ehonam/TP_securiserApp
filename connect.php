<?php
// connect.php sécurisé - Cas 1
require_once __DIR__ . '/config/config.php';
// If config.php defines a namespace or class, import it like:
// use Config\ConfigClass;

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $db = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die("Erreur BDD : " . $e->getMessage());
    } else {
        die("Erreur de connexion à la base de données");
    }
}