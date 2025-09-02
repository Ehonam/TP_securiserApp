<?php
	$strPreco   = "    <ul>
						<li>Remplacer les caractères spéciaux</li>
						<li>Traiter la saisie utilisateur en fonction de ce qui est demandé :<br>
						IP donc 4 <u>nombres</u> entre 0 et 255, séparés par des .</li>
					</ul>";
	$strDesc    = "L'injection de commande est une attaque informatique qui consiste à insérer des commandes malveillantes dans des entrées utilisateur ou des données qui seront ensuite exécutées par un système informatique. Cette attaque est souvent utilisée pour prendre le contrôle d'un système informatique à distance.";
	$strTip     = "Utiliser le séparateur & ou && ou ; selon le système d'exploitation";
?>

<div class="col-md-8">
	<h2>Injection de commande </h2>
	<?php
		use _partial\desc;

		// If desc.php defines a class, instantiate or use it as needed, for example:
		// $desc = new desc();
		// echo $desc->render();
	?>

	<div class="py-4">
		<form name="ping" action="#" method="post">
			<p>
				Saisir une adresse IP:
				<p><input type="text" class="form-control"  name="ip" size="30" value="<?php echo $_POST['ip']??""; ?>" ></p>
				<p><input type="submit" class="form-control btn btn-primary" name="Submit" value="Envoyer"> </p>
				
			</p>
		</form>
	</div>
	
<?php
	if( isset( $_POST[ 'ip' ] ) &&  $_POST[ 'ip' ] != "") {
		// Get input
		$target = $_REQUEST[ 'ip' ];

		// Determine OS and execute the ping command.
		if( stristr( php_uname( 's' ), 'Windows NT' ) ) {
			// Windows
			$cmd = shell_exec( 'ping  ' . $target );
		}
		else {
			// *nix
			$cmd = shell_exec( 'ping  -c 4 ' . $target );
		}

		// Feedback for the end user
		echo "<pre>".$cmd."</pre>";
	}
	
	use _partial\soluce;
	// The class soluce is now imported via the use statement. Ensure autoloading is configured.
?>
	
</div>