<?php
/**
 * Test de connexion MariaDB
 * Fichier temporaire pour diagnostiquer les problèmes de connexion
 */

echo "<h2>Test de connexion MariaDB</h2>";

// Load environment variables
require_once __DIR__ . '/env_loader.php';

echo "<h3>Configuration détectée:</h3>";
echo "<ul>";
echo "<li>Host: " . (defined('DB_HOST') ? DB_HOST : 'Non défini') . "</li>";
echo "<li>Port: " . (defined('DB_PORT') ? DB_PORT : 'Non défini') . "</li>";
echo "<li>Database: " . (defined('DB_NAME') ? DB_NAME : 'Non défini') . "</li>";
echo "<li>User: " . (defined('DB_USER') ? DB_USER : 'Non défini') . "</li>";
echo "<li>Password: " . (defined('DB_PASS') ? (empty(DB_PASS) ? 'Vide' : 'Défini') : 'Non défini') . "</li>";
echo "</ul>";

echo "<h3>Test de connexion:</h3>";

try {
    $host = DB_HOST;
    $port = DB_PORT;
    $dbname = DB_NAME;
    $username = DB_USER;
    $password = DB_PASS;
    $charset = DB_CHARSET;
    
    // Test 1: Connexion sans base de données
    echo "<p><strong>Étape 1:</strong> Test de connexion au serveur MariaDB...</p>";
    $dsn1 = "mysql:host=$host;port=$port;charset=$charset";
    $pdo1 = new PDO($dsn1, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✅ Connexion au serveur MariaDB réussie!</p>";
    
    // Test 2: Vérifier si la base existe
    echo "<p><strong>Étape 2:</strong> Vérification de l'existence de la base '$dbname'...</p>";
    $stmt = $pdo1->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<p style='color: green;'>✅ Base de données '$dbname' trouvée!</p>";
        
        // Test 3: Connexion avec la base
        echo "<p><strong>Étape 3:</strong> Connexion à la base de données...</p>";
        $dsn2 = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        $pdo2 = new PDO($dsn2, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        echo "<p style='color: green;'>✅ Connexion à la base '$dbname' réussie!</p>";
        
        // Test 4: Vérifier les tables
        echo "<p><strong>Étape 4:</strong> Vérification des tables...</p>";
        $stmt = $pdo2->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "<p style='color: orange;'>⚠️ Aucune table trouvée. Vous devez importer le fichier phpsec.sql</p>";
            echo "<p><strong>Instructions:</strong></p>";
            echo "<ol>";
            echo "<li>Ouvrez phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
            echo "<li>Sélectionnez la base 'phpsec'</li>";
            echo "<li>Onglet 'Importer'</li>";
            echo "<li>Choisissez le fichier 'phpsec.sql' depuis votre dossier projet</li>";
            echo "<li>Cliquez sur 'Exécuter'</li>";
            echo "</ol>";
        } else {
            echo "<p style='color: green;'>✅ Tables trouvées: " . implode(', ', $tables) . "</p>";
            
            $expectedTables = ['users', 'comments', 'login_attempts', 'csrf_tokens'];
            $missingTables = array_diff($expectedTables, $tables);
            
            if (!empty($missingTables)) {
                echo "<p style='color: orange;'>⚠️ Tables manquantes: " . implode(', ', $missingTables) . "</p>";
                echo "<p>Vous devez réimporter le fichier phpsec.sql mis à jour.</p>";
            } else {
                echo "<p style='color: green;'>✅ Toutes les tables nécessaires sont présentes!</p>";
                
                // Test 5: Test d'une requête simple
                echo "<p><strong>Étape 5:</strong> Test d'une requête...</p>";
                $stmt = $pdo2->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch();
                echo "<p style='color: green;'>✅ Requête test réussie! Nombre d'utilisateurs: " . $result['count'] . "</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Base de données '$dbname' introuvable!</p>";
        echo "<p><strong>Solution:</strong></p>";
        echo "<ol>";
        echo "<li>Ouvrez phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
        echo "<li>Créez une nouvelle base de données nommée 'phpsec'</li>";
        echo "<li>Sélectionnez cette base</li>";
        echo "<li>Importez le fichier 'phpsec.sql'</li>";
        echo "</ol>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
    
    echo "<h3>Solutions possibles:</h3>";
    echo "<ul>";
    echo "<li><strong>Vérifiez que WAMP est démarré</strong> (icône verte dans la barre des tâches)</li>";
    echo "<li><strong>Vérifiez que MariaDB est démarré</strong> dans WAMP</li>";
    echo "<li><strong>Vérifiez le port:</strong> Dans WAMP → Outils → Port utilisé par MariaDB</li>";
    echo "<li><strong>Testez la connexion manuelle:</strong> Ouvrez phpMyAdmin</li>";
    echo "</ul>";
    
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<p style='color: orange;'><strong>Le serveur MariaDB ne répond pas.</strong> Vérifiez que tous les services WAMP sont verts.</p>";
    }
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p style='color: orange;'><strong>Erreur d'authentification.</strong> Vérifiez le nom d'utilisateur et mot de passe dans le fichier .env</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>← Retour à l'application</a></p>";
echo "<p><em>Une fois la connexion fonctionnelle, vous pouvez supprimer ce fichier test_connection.php</em></p>";
?>