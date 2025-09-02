<?php
	$strPreco 	= "	<ul>
						<li>Stocker en BDD les tentatives de connexion</li>
						<li>Définir la limite du nombre de tentatives</li>
						<li>Définir le devenir d'un nombre de tentatives trop élevé (bloqué 15 minutes, compte désactivé, Adresse IP en blacklist)</li>
						<li>Définir le mot de passe 'fort' et le hacher lors de la création / modification de celui-ci : <a href='index.php?page=csrf'>Modifier le mot de passe</a></li>
					</ul>";
	$strDesc	= "En utilisant la technique de brute force, un attaquant peut générer automatiquement des milliers, voire des millions de combinaisons de mots de passe, jusqu'à ce que le mot de passe correct soit trouvé. Les attaquants peuvent utiliser des outils automatisés pour accélérer le processus et essayer de trouver le mot de passe le plus rapidement possible.";
	$strTip		= "Utiliser Burp Suite";
?>
<div class="col-md-8">
	<h2>Brut Force</h2>
	<?php
		include("_partial/desc.php");

		$strPage = "brut_force";
		include ("login.php");
		
		include ("_partial/soluce.php"); 
	?>
</div>