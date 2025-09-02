<?php
// pages/change_password.php - SÉCURISÉ avec CSRF
require_once __DIR__ . '/../includes/security.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ VÉRIFICATION TOKEN CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide - Rechargez la page";
        
        // Log tentative CSRF
        $stmt = $db->prepare("INSERT INTO security_logs (event_type, description, ip_address, user_id, severity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['csrf_attack_attempt', 'Token CSRF invalide', $_SERVER['REMOTE_ADDR'], $_SESSION['user_id'], 'high']);
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "Tous les champs sont obligatoires";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Les mots de passe ne correspondent pas";
        } else {
            // ✅ VÉRIFIER MOT DE PASSE ACTUEL
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($currentPassword, $user['password'])) {
                $error = "Mot de passe actuel incorrect";
            } else {
                // ✅ VALIDATION FORCE MOT DE PASSE
                $validation = Security::validatePasswordStrength($newPassword);
                if ($validation !== true) {
                    $error = "Mot de passe trop faible : " . implode(', ', $validation);
                } else {
                    // ✅ HACHAGE SÉCURISÉ + MISE À JOUR
                    $hashedPassword = Security::hashPassword($newPassword);
                    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                    
                    $message = "Mot de passe modifié avec succès";
                    
                    // Générer nouveau token après succès
                    unset($_SESSION['csrf_token']);
                }
            }
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>

<form method="POST">
    <!-- ✅ TOKEN CSRF OBLIGATOIRE -->
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    
    <label>Mot de passe actuel :</label>
    <input type="password" name="current_password" required>
    
    <label>Nouveau mot de passe :</label>
    <input type="password" name="new_password" required minlength="8">
    
    <label>Confirmer :</label>
    <input type="password" name="confirm_password" required>
    
    <button type="submit">Changer</button>
</form>