<?php
	$strPreco 	= "	<ul>
						<li>Nettoyage des données saisies</li>
						<li>Utilisation de requêtes préparées</li>
					</ul>";
	$strDesc	= "La faille XSS est une vulnérabilité de sécurité qui se produit lorsqu'un site web stocke de manière persistante des données d'utilisateur qui contiennent du code malveillant qui sera ensuite affiché sur les pages web pour les utilisateurs légitimes.";
	$strTip		= "insérer du script dans le formulaire : <script>alert(\"coucou\");</script>";

	include("connect.php");
	
	if (isset($_GET['name']) > 0){
		$db->exec("INSERT INTO comments (name, comment) VALUES ('".$_GET['name']."', '".$_GET['message']."')");
	}

	$arrComments = array();
	$arrComments = $db->query('SELECT * FROM comments WHERE publish = 1')->fetchAll();
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
		<?php 
			foreach ($arrComments as $arrDet){
		?>
		<div class="card mb-4">
            <div class="card-body">
                <p><?php echo $arrDet['comment']; ?></p>
				<div class="d-flex justify-content-between">
					<div class="d-flex flex-row align-items-center">
						<p class="small mb-0 ms-2"><?php echo $arrDet['name']; ?></p>
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