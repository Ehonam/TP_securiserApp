<?php
// pages/login.php - SÉCURISÉ contre Brute Force
require_once __DIR__ . '/../includes/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide";
    } else {
        $login = Security::cleanInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            $error = "Tous les champs obligatoires";
        } else {
            // ✅ VÉRIFIER TENTATIVES BRUTE FORCE
            if (!Security::checkLoginAttempts($login)) {
                $error = "Trop de tentatives. Réessayez dans 15 minutes.";
            } else {
                // ✅ REQUÊTE PRÉPARÉE (anti-injection SQL)
                $stmt = $db->prepare("SELECT id, login, password, name, active, failed_attempts, locked_until FROM users WHERE login = ? AND active = 1");
                $stmt->execute([$login]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // ✅ CONNEXION RÉUSSIE
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_login'] = $user['login'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['login_time'] = time();
                    
                    // Réinitialiser compteurs
                    Security::clearLoginAttempts($login);
                    $stmt = $db->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    header("Location: index.php?page=profile");
                    exit();
                } else {
                    // ✅ CONNEXION ÉCHOUÉE - Logger
                    $error = "Identifiants incorrects";
                    Security::logFailedLogin($login);
                    
                    if ($user) {
                        $newFailedAttempts = $user['failed_attempts'] + 1;
                        $lockUntil = null;
                        
                        // ✅ VERROUILLAGE APRÈS 5 TENTATIVES
                        if ($newFailedAttempts >= 5) {
                            $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 min
                        }
                        
                        $stmt = $db->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?");
                        $stmt->execute([$newFailedAttempts, $lockUntil, $user['id']]);
                    }
                }
            }
        }
    }
    
    // ✅ DÉLAI ANTI-BRUTE FORCE
    sleep(1);
}
?>

<form method="POST" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
    <label>Username:</label>
    <input type="text" name="username" required autocomplete="username">
    <label>Password:</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button type="submit">Se connecter</button>
</form>