<?php
	$strPreco 	= "	<ul>
						<li>Mise en place d'un jeton CSRF</li>
						<li>Demander le mot de passe actuel pour vérification</li>
						<li>Utiliser POST au lieu de GET</li>
						<li>Hacher les mots de passe</li>
					</ul>";
	$strDesc	= "La faille CSRF est une attaque dans laquelle un attaquant exploite la confiance entre un utilisateur et un site web. L'attaque consiste à envoyer une requête HTTP depuis le navigateur de la victime vers un site web tiers, sans que la victime en soit consciente. Cette requête est souvent une action malveillante, telle que supprimer des données, effectuer un achat ou changer un mot de passe.";
	$strTip		= "Changer les données dans l'url ou utiliser Burp Suite";
	
	// Load CSRF protection
	require_once __DIR__ . '/../csrf_protection.php';
?>


<div class="col-md-8">
	<h2>CSRF</h2>
	<?php
		include("_partial/desc.php");
		include("connect.php");
		
		$message = '';
		$messageType = '';
		
		// Process form submission (POST only)
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_new'])) {
			// Validate CSRF token
			if (!CSRFProtection::validateFromData($_POST)) {
				$message = 'Erreur de sécurité: Token CSRF invalide. Veuillez recharger la page.';
				$messageType = 'danger';
			} else {
				$passwordNew = trim($_POST['password_new']);
				$passwordConf = trim($_POST['password_conf']);
				$currentPassword = trim($_POST['current_password'] ?? '');
				
				if (empty($passwordNew)) {
					$message = "Vous devez renseigner un nouveau mot de passe";
					$messageType = 'warning';
				} else if (empty($currentPassword)) {
					$message = "Vous devez renseigner votre mot de passe actuel";
					$messageType = 'warning';
				} else if ($passwordNew !== $passwordConf) {
					$message = "Le mot de passe et sa confirmation ne correspondent pas";
					$messageType = 'warning';
				} else if (strlen($passwordNew) < 8) {
					$message = "Le mot de passe doit contenir au moins 8 caractères";
					$messageType = 'warning';
				} else {
					// Verify current password (assuming user ID 1)
					$stmt = $db->prepare("SELECT password FROM users WHERE id = 1");
					$stmt->execute();
					$user = $stmt->fetch();
					
					if ($user && password_verify($currentPassword, $user['password'])) {
						// Hash the new password
						$hashedPassword = password_hash($passwordNew, PASSWORD_DEFAULT);
						
						// Update password using prepared statement
						$updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = 1");
						if ($updateStmt->execute([$hashedPassword])) {
							$message = "Mot de passe modifié avec succès!";
							$messageType = 'success';
						} else {
							$message = "Erreur lors de la mise à jour du mot de passe";
							$messageType = 'danger';
						}
					} else {
						$message = "Mot de passe actuel incorrect";
						$messageType = 'danger';
					}
				}
			}
		}
		
		if (!empty($message)) {
			echo "<div class='alert alert-{$messageType}'>{$message}</div>";
		}
	?>
	<form action="#" method="POST">
		<input type="hidden" name="page" value="csrf">
		<?php echo CSRFProtection::getTokenField(); ?>
		
		<p>
			<label>Mot de passe actuel :</label>
			<input class="form-control" type="password" name="current_password" required>
		</p>
		<p>
			<label>Nouveau mot de passe :</label>
			<input class="form-control" type="password" name="password_new" minlength="8" required>
			<small class="form-text text-muted">Minimum 8 caractères</small>
		</p>
		<p>
			<label>Confirmer le mot de passe :</label>
			<input class="form-control" type="password" name="password_conf" required>
		</p>
		<p>
			<input class="form-control btn btn-primary" type="submit" value="Changer le mot de passe">
		</p>
	</form>
	
	<?php
		include ("_partial/soluce.php"); 
	?>	
</div>