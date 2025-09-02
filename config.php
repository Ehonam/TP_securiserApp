<?php
/**
 * Security Training Application Configuration
 * 
 * EDUCATIONAL NOTE: This file centralizes application configuration.
 * Environment variables are now loaded from .env file for better security.
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Convert string booleans to actual booleans
$appDebug = filter_var(APP_DEBUG ?? 'false', FILTER_VALIDATE_BOOLEAN);
define('APP_DEBUG_BOOL', $appDebug);

// Allowed file types for upload (if implementing file upload security)
$allowedFileTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];

// Page whitelist for navigation security
$allowedPages = [
    'content', 'sql_injection', 'xss', 'csrf', 'command_injection', 
    'file_upload', 'brut_force'
];

// Error messages for different vulnerabilities
$vulnerabilityMessages = [
    'sql_injection' => [
        'title' => 'Injection SQL',
        'description' => 'L\'injection SQL est une technique d\'attaque informatique qui consiste à insérer du code SQL malveillant dans une requête SQL.',
        'tip' => '\' OR \'1\'=\'1',
        'prevention' => [
            'Utiliser des requêtes préparées',
            'Valider et nettoyer les entrées utilisateur',
            'Appliquer le principe du moindre privilège',
            'Utiliser un ORM sécurisé'
        ]
    ],
    'xss' => [
        'title' => 'Cross-Site Scripting (XSS)',
        'description' => 'La faille XSS permet d\'injecter du code JavaScript malveillant dans une page web.',
        'tip' => '<script>alert("XSS");</script>',
        'prevention' => [
            'Échapper les données en sortie (htmlspecialchars)',
            'Valider les entrées utilisateur',
            'Utiliser Content Security Policy (CSP)',
            'Filtrer les balises HTML dangereuses'
        ]
    ],
    'csrf' => [
        'title' => 'Cross-Site Request Forgery (CSRF)',
        'description' => 'Le CSRF force un utilisateur authentifié à exécuter des actions non désirées.',
        'prevention' => [
            'Utiliser des tokens CSRF',
            'Vérifier le référent',
            'Authentification double pour actions sensibles',
            'Utiliser SameSite cookies'
        ]
    ]
];

// Function to get vulnerability info
function getVulnerabilityInfo($type) {
    global $vulnerabilityMessages;
    return $vulnerabilityMessages[$type] ?? null;
}
?>