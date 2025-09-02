<?php
// pages/ping.php - SÉCURISÉ
require_once __DIR__ . '/../includes/security.php';

$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide";
    } else {
        $ip = Security::cleanInput($_POST['ip'] ?? '');
        
        // ✅ VALIDATION STRICTE IP
        if (!Security::validateIP($ip)) {
            $error = "Adresse IP invalide";
            
            // Log tentative d'injection
            $stmt = $db->prepare("INSERT INTO security_logs (event_type, description, ip_address, severity) VALUES (?, ?, ?, ?)");
            $stmt->execute(['command_injection_attempt', "IP suspecte: $ip", $_SERVER['REMOTE_ADDR'], 'high']);
        } else {
            // ✅ EXÉCUTION SÉCURISÉE
            $result = Security::safePing($ip);
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <label>Adresse IP :</label>
    <input type="text" name="ip" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" required>
    <button type="submit">Tester</button>
</form>

<?php if ($result): ?>
    <pre><?= htmlspecialchars($result['output']) ?></pre>
<?php endif; ?>