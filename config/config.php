<?php
// config/config.php - Configuration par constantes
use EnvLoader\EnvLoader;

// If you still need to include the file manually (e.g., no autoloader), use:
// require_once __DIR__ . '/env_loader.php';
define('DB_HOST', 'localhost');
define('DB_NAME', 'phpsec');
define('DB_USER', 'root');
define('DB_PASS', 'mot_de_passe_securise');
define('DB_CHARSET', 'utf8mb4');

// Constantes de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'PHPSEC_SESSID');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

define('DEBUG_MODE', false);
