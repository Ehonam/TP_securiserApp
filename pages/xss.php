<?php
	$strPreco 	= "\t<ul>\n\t\t\t\t\t\t<li>Nettoyage des données saisies</li>\n\t\t\t\t\t\t<li>Utilisation de requêtes préparées</li>\n\t\t\t\t\t</ul>";
	$strDesc	= "La faille XSS est une vulnérabilité de sécurité qui se produit lorsqu'un site web stocke de manière persistante des données d'utilisateur qui contiennent du code malveillant qui sera ensuite affiché sur les pages web pour les utilisateurs légitimes.";
	$strTip		= "insérer du script dans le formulaire : <script>alert(\"coucou\");</script>";

	// $db est initialisé globalement via index.php -> connect.php

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		require_once __DIR__ . '/../includes/security.php';
		if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
			echo "<div class='alert alert-danger'>Token CSRF invalide.</div>";
		} else {
			$name = Security::cleanInput($_POST['name'] ?? '');
			$message = Security::cleanInput($_POST['message'] ?? '');
			if ($name !== '' && $message !== '') {
				$stmt = $db->prepare('INSERT INTO comments (name, comment, ip_address) VALUES (?, ?, ?)');
				$stmt->execute([$name, $message, $_SERVER['REMOTE_ADDR']]);
			}
		}
	}

	$arrStmt = $db->prepare('SELECT * FROM comments WHERE publish = 1 ORDER BY created_at DESC');
	$arrStmt->execute();
	$arrComments = $arrStmt->fetchAll();
?>
<div class="col-md-8">
	<h2>Cross Script Scripting (XSS)</h2>
	<?php
		include("_partial/desc.php");
	?>
	<div class="py-4">
		<form name="guestform" method="POST" autocomplete="off">
			<input type="hidden" name="page" value="xss">
			<input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
			<p>
				<label>Nom *</label>
				<input required class="form-control" name="name" type="text" size="30" maxlength="10" >
			</p>
			<p>
				<label>Commentaire *</label>
				<textarea required class="form-control" name="message" cols="50" rows="3" maxlength="50" ></textarea>
			</p>
			<p>
				<input class="form-control btn btn-primary" name="btnSign" type="submit" value="Envoyer" >
			</p>
		</form>
	</div>
	
	<div id="comments">
		<?php 
			foreach ($arrComments as $arrDet){
		?>
		<div class="card mb-4">
            <div class="card-body">
                <p><?php echo $arrDet['comment']; ?></p>
				<div class="d-flex justify-content-between">
					<div class="d-flex flex-row align-items-center">
						<p class="small mb-0 ms-2"><?php echo htmlspecialchars($arrDet['name'], ENT_QUOTES, 'UTF-8'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php 
			}
		?>
	</div>
	<?php
		include ("_partial/soluce.php"); 
	?>
</div>