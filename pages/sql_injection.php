<?php
	$strPreco 	= "	<ul>
						<li>Utiliser des requêtes préparées</li>
						<li>Nettoyer les entrées de l'utilisateur</li>
					</ul>";
	$strDesc	= "L'injection SQL est une technique d'attaque informatique qui consiste à insérer du code SQL malveillant dans une requête SQL. Cette attaque peut être utilisée pour accéder, modifier ou supprimer des données dans une base de données, et peut être particulièrement dangereuse si les données sensibles sont impliquées.";
	$strTip		= "' OR '1'='1";
?>
<div class="col-md-8">
	<h2>Injection SQL</h2>
	<?php 
		include("_partial/desc.php");
		$strPage = "sql_injection";

		include ("login.php");
		include ("_partial/soluce.php"); 
	?>
</div>