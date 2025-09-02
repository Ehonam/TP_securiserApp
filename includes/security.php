<?php
class Security {
    public static function validateIP($ip) {
        // Validation stricte
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        
        // Bloquer IPs dangereuses
        $forbidden = ['127.0.0.1', '::1', '0.0.0.0'];
        return !in_array($ip, $forbidden);
    }
    
    public static function safePing($ip) {
        if (!self::validateIP($ip)) {
            return ['success' => false, 'output' => 'IP invalide'];
        }
        
        // Échappement sécurisé
        $command = PHP_OS_FAMILY === 'Windows' ? 
            "ping -n 1 " . escapeshellarg($ip) : 
            "ping -c 1 " . escapeshellarg($ip);
        
        exec($command . " 2>&1", $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", array_slice($output, 0, 10))
        ];
    }

    public static function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function displaySafe($content) {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    public static function detectXSS($input) {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }

    // Génération token CSRF
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Validation token CSRF
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Hachage sécurisé
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    // Validation force mot de passe
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) $errors[] = "Au moins 8 caractères";
        if (!preg_match('/[A-Z]/', $password)) $errors[] = "Une majuscule";
        if (!preg_match('/[a-z]/', $password)) $errors[] = "Une minuscule";
        if (!preg_match('/[0-9]/', $password)) $errors[] = "Un chiffre";
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) $errors[] = "Un caractère spécial";
        
        return empty($errors) ? true : $errors;
    }

    // Logger tentative échouée
    public static function logFailedLogin($identifier) {
        global $db;
        $stmt = $db->prepare("INSERT INTO login_attempts (identifier, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmt->execute([$identifier, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    }

    // Vérifier nombre de tentatives
    public static function checkLoginAttempts($identifier) {
        global $db;
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$identifier]);
        return $stmt->fetchColumn() < 5; // Max 5 tentatives
    }

    // Nettoyer tentatives après succès
    public static function clearLoginAttempts($identifier) {
        global $db;
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE identifier = ?");
        $stmt->execute([$identifier]);
    }

    // Validation complète fichier uploadé
    public static function validateUploadedFile($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $errors = [];
        
        // ✅ VÉRIFIER ERREURS UPLOAD
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors du téléchargement";
            return $errors;
        }
        
        // ✅ VÉRIFIER TAILLE
        if ($file['size'] > $maxSize) {
            $errors[] = "Fichier trop volumineux (max 5MB)";
        }
        
        // ✅ VÉRIFIER TYPE MIME
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Type de fichier non autorisé";
        }
        
        // ✅ VÉRIFIER EXTENSION
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Extension non autorisée";
        }
        
        // ✅ VÉRIFIER CONTENU RÉEL (anti-contournement)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = "Le contenu ne correspond pas à l'extension";
        }
        
        return $errors;
    }

    // Génération nom de fichier sécurisé
    public static function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        
        return $timestamp . '_' . $random . '.' . $extension;
    }
}
