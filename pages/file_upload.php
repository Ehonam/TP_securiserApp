<?php
	$strPreco 	= "<ul>
						<li>Autoriser uniquement les types de fichier nécessaires</li>
						<li>Vérifier les informations du fichier</li>
						<li>Renommer le fichier</li>
					</ul>";
	$strDesc	= "Les attaquants peuvent utiliser des fichiers malveillants qui ont une extension différente de celle autorisée pour contourner les contrôles de sécurité. Si la taille du fichier est trop grande, cela peut entraîner une surcharge du serveur et un déni de service (DoS). Les attaquants peuvent également télécharger des fichiers volumineux pour occuper l'espace de stockage du serveur et empêcher le téléchargement de fichiers légitimes. Les fichiers téléchargés peuvent contenir des virus ou des logiciels malveillants qui peuvent infecter le serveur et compromettre la sécurité de l'ensemble du système.";
	$strTip		= "Ajouter un fichier malveillant...";
?>

<div class="col-md-8 position-relative">
	<h2>Traitement des fichiers</h2>
	<?php
		include("_partial/desc.php");
		if (count($_FILES) >0){
			$uploaddir = './uploads/';
			$uploadfile = $uploaddir . basename($_FILES['myFile']['name']);
			if (move_uploaded_file($_FILES['myFile']['tmp_name'], $uploadfile)) {
				echo "Le fichier a bien été uploadé";
			} else {
				echo "Erreur d'upload du fichier";
			}
		}
	?>
	<form enctype="multipart/form-data" action="index.php?page=file_upload" method="POST">
		<p>
			<input class="form-control" type="file" name="myFile">
		</p>
		<p>
			<input class="form-control btn btn-primary" type="submit" name="upload" value="Envoyer">
		</p>
	</form>
	<?php
		$scandir = scandir("./uploads");
		echo "<ul>";
		foreach($scandir as $fichier){
			if ($fichier != "." && $fichier != ".."){
	?>
			<li><a href="uploads/<?php echo $fichier; ?>" ><?php echo $fichier; ?></a></li>
	<?php
			}
		}
		echo "</ul>";
	
		include ("_partial/soluce.php"); 
	?>
</div>