<?php
/**
 * Cross-Site Scripting (XSS) Vulnerability Demonstration
 * 
 * EDUCATIONAL PURPOSE: This page intentionally contains XSS vulnerabilities
 * to demonstrate how they work and how to prevent them.
 */

$strPreco = "<ul>
                <li>Nettoyage des données saisies (htmlspecialchars, filter_var)</li>
                <li>Validation côté serveur</li>
                <li>Content Security Policy (CSP)</li>
                <li>Échappement des données en sortie</li>
            </ul>";
            
$strDesc = "La faille XSS est une vulnérabilité de sécurité qui se produit lorsqu'un site web stocke de manière persistante des données d'utilisateur qui contiennent du code malveillant qui sera ensuite affiché sur les pages web pour les utilisateurs légitimes.";

$strTip = "insérer du script dans le formulaire : <script>alert(\"coucou\");</script>";

include("connect.php");

// SECURE: Sanitize and validate user input, use prepared statements
if (isset($_GET['name']) && !empty($_GET['name'])) {
    // Sanitize input data
    $name = filter_var(trim($_GET['name']), FILTER_SANITIZE_SPECIAL_CHARS);
    $message = filter_var(trim($_GET['message'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Additional validation
    if (strlen($name) > 0 && strlen($name) <= 50 && strlen($message) > 0 && strlen($message) <= 500) {
        // Use prepared statement to prevent SQL injection
        $stmt = $db->prepare("INSERT INTO comments (name, comment, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $name, 
            $message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}

// Retrieve comments from database
$arrComments = [];
try {
    $arrComments = $db->query('SELECT * FROM comments WHERE publish = 1')->fetchAll();
} catch (PDOException $e) {
    $arrComments = [];
}
?>
<div class="col-md-8">
	<h2>Cross Script Scripting (XSS)</h2>
	<?php
		include("_partial/desc.php");
	?>
	<div class="py-4">
		<form name="guestform">
			<input type="hidden" name="page" value="xss">
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
		<h4>Commentaires</h4>
		<!-- VULNERABILITY DEMONSTRATION AREA -->
		<?php foreach ($arrComments as $arrDet): ?>
		<div class="card mb-4">
            <div class="card-body">
                <!-- SECURE: Escaped output prevents XSS attacks -->
                <p><?php echo htmlspecialchars($arrDet['comment'], ENT_QUOTES, 'UTF-8'); ?></p>
				<div class="d-flex justify-content-between">
					<div class="d-flex flex-row align-items-center">
						<!-- SECURE: Escaped name field -->
						<p class="small mb-0 ms-2"><?php echo htmlspecialchars($arrDet['name'], ENT_QUOTES, 'UTF-8'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
		include ("_partial/soluce.php"); 
	?>
</div>