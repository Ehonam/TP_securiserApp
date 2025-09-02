<?php
	$strPreco 	= "	<ul>
						<li>Mise en place d'un jeton</li>
						<li>Demander le mot de passe actuel pour vérification</li>
					</ul>";
	$strDesc	= "La faille CSRF est une attaque dans laquelle un attaquant exploite la confiance entre un utilisateur et un site web. L'attaque consiste à envoyer une requête HTTP depuis le navigateur de la victime vers un site web tiers, sans que la victime en soit consciente. Cette requête est souvent une action malveillante, telle que supprimer des données, effectuer un achat ou changer un mot de passe.";
	$strTip		= "Changer les données dans l'url ou utiliser Burp Suite";
?>


<div class="col-md-8">
	<h2>CSRF</h2>
	<?php
		include("_partial/desc.php");
		include("connect.php");
		if (isset($_GET['password_new'])){
			if ($_GET['password_new'] == ''){
				echo "Vous devez renseigner un mot de passe";
			}else if ($_GET['password_new'] != $_GET['password_conf']){
				echo "Le mot de passe et sa confirmation ne correspondent pas";
			}else{
				$db->exec("UPDATE users SET password = '".$_GET['password_new']."' WHERE id = 1");
			}
		}
	?>
	<form action="#" method="GET" >
		<input type="hidden" name="page" value="csrf">
		<p>
			<label>Nouveau mot de passe :</label>
			<input class="form-control" type="password" name="password_new">
		</p>
		<p>
			<label>Confirmer le mot de passe :</label>
			<input class="form-control" type="password" name="password_conf" >
		</p>
		<p>
			<input class="form-control btn btn-primary" type="submit" value="changer">
		</p>
	</form>
	
	<?php
		include ("_partial/soluce.php"); 
	?>	
</div>