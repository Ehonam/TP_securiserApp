<?php
// pages/comments.php - SÉCURISÉ contre XSS
require_once __DIR__ . '/../includes/security.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide";
    } else {
        // ✅ NETTOYAGE SÉCURISÉ
        $name = Security::cleanInput($_POST['name'] ?? '');
        $comment = Security::cleanInput($_POST['comment'] ?? '');
        
        if (empty($name) || empty($comment)) {
            $error = "Tous les champs sont obligatoires";
        } else {
            // ✅ DÉTECTION XSS
            if (Security::detectXSS($name) || Security::detectXSS($comment)) {
                $error = "Contenu potentiellement malveillant détecté";
                
                // Log tentative XSS
                $stmt = $db->prepare("INSERT INTO security_logs (event_type, description, ip_address, severity) VALUES (?, ?, ?, ?)");
                $stmt->execute(['xss_attempt', "XSS dans commentaire", $_SERVER['REMOTE_ADDR'], 'high']);
            } else {
                // ✅ INSERTION SÉCURISÉE
                $stmt = $db->prepare("INSERT INTO comments (name, comment, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$name, $comment, $_SERVER['REMOTE_ADDR']]);
                $message = "Commentaire ajouté avec succès";
            }
        }
    }
}

// Récupération sécurisée
$stmt = $db->prepare("SELECT * FROM comments WHERE publish = 1 ORDER BY created_at DESC");
$stmt->execute();
$comments = $stmt->fetchAll();
?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
    <label>Nom :</label>
    <input type="text" name="name" maxlength="50" required>
    <label>Commentaire :</label>
    <textarea name="comment" maxlength="200" required></textarea>
    <button type="submit">Envoyer</button>
</form>

<?php foreach ($comments as $comment): ?>
    <div>
        <strong><?= Security::displaySafe($comment['name']) ?></strong>
        <p><?= Security::displaySafe($comment['comment']) ?></p>
    </div>
<?php endforeach; ?>