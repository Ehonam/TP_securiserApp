<?php
require_once __DIR__ . '/../includes/security.php';
$csrf_token = Security::generateCSRFToken();

// Descriptions et conseils
$strPreco = "<ul>
                <li>Mise en place d'un jeton CSRF unique par session</li>
                <li>Vérification du mot de passe actuel pour une sécurité accrue</li>
             </ul>";
$strDesc = "La faille CSRF (Cross-Site Request Forgery) est une attaque qui exploite la confiance d'un site web envers un utilisateur authentifié. Un attaquant peut envoyer une requête HTTP malveillante depuis le navigateur de la victime vers un site tiers, à son insu, pour effectuer des actions telles que la modification de données, des achats non autorisés ou le changement de mot de passe.";
$strTip = "Pour exploiter une faille CSRF, un attaquant peut falsifier une requête via une URL manipulée ou utiliser des outils comme Burp Suite.";
?>

<div class="col-md-8">
    <h2>Protection contre les attaques CSRF</h2>
    <?php
    require_once "_partial/desc.php";
    // $db est initialisé globalement via index.php -> connect.php

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérification du jeton CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            echo "<div class='alert alert-danger'>Erreur : Jeton CSRF invalide.</div>";
        } elseif (empty($_POST['password_new']) || empty($_POST['password_conf'])) {
            echo "<div class='alert alert-danger'>Erreur : Les champs de mot de passe sont obligatoires.</div>";
        } elseif ($_POST['password_new'] !== $_POST['password_conf']) {
            echo "<div class='alert alert-danger'>Erreur : Les mots de passe ne correspondent pas.</div>";
        } else {
            try {
                // Hashage sécurisé du mot de passe
                $hashedPassword = password_hash($_POST['password_new'], PASSWORD_ARGON2ID);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, 1]);

                // Regénérer le jeton CSRF après une action réussie
                Security::generateCSRFToken();
                echo "<div class='alert alert-success'>Mot de passe modifié avec succès.</div>";
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Erreur lors de la mise à jour : " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    ?>
    <form action="" method="POST">
        <input type="hidden" name="page" value="csrf">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="password_new">Nouveau mot de passe :</label>
            <input class="form-control" type="password" name="password_new" id="password_new" required>
        </div>
        <div class="form-group">
            <label for="password_conf">Confirmer le mot de passe :</label>
            <input class="form-control" type="password" name="password_conf" id="password_conf" required>
        </div>
        <div class="form-group">
            <button class="btn btn-primary" type="submit">Changer</button>
        </div>
    </form>

    <?php
    require_once "_partial/soluce.php";
    ?>
</div>