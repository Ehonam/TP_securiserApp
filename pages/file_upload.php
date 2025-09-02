<?php
	$strPreco 	= "<ul>
						<li>Autoriser uniquement les types de fichier nécessaires (images, PDF, texte)</li>
						<li>Vérifier les informations du fichier (taille, MIME type, en-têtes)</li>
						<li>Renommer le fichier avec un nom sécurisé</li>
						<li>Protéger le répertoire d'upload contre l'exécution</li>
						<li>Valider les images avec getimagesize()</li>
					</ul>";
	$strDesc	= "Les attaquants peuvent utiliser des fichiers malveillants qui ont une extension différente de celle autorisée pour contourner les contrôles de sécurité. Si la taille du fichier est trop grande, cela peut entraîner une surcharge du serveur et un déni de service (DoS). Les attaquants peuvent également télécharger des fichiers volumineux pour occuper l'espace de stockage du serveur et empêcher le téléchargement de fichiers légitimes. Les fichiers téléchargés peuvent contenir des virus ou des logiciels malveillants qui peuvent infecter le serveur et compromettre la sécurité de l'ensemble du système.";
	$strTip		= "Essayer de télécharger un fichier PHP ou un fichier avec une double extension";
	
	// Load secure upload system
	require_once __DIR__ . '/../secure_upload.php';
	require_once __DIR__ . '/../csrf_protection.php';
?>

<div class="col-md-8 position-relative">
	<h2>Traitement des fichiers</h2>
	<?php
		include("_partial/desc.php");
		
		// Initialize secure upload system
		$secureUpload = new SecureFileUpload();
		$uploadMessage = '';
		$messageType = '';
		
		// Process file upload (POST only with CSRF protection)
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['myFile']) && !empty($_FILES['myFile']['name'])) {
			// Validate CSRF token
			if (!CSRFProtection::validateFromData($_POST)) {
				$uploadMessage = 'Erreur de sécurité: Token CSRF invalide.';
				$messageType = 'danger';
			} else {
				// Process secure upload
				$result = $secureUpload->processUpload($_FILES['myFile']);
				
				if ($result['success']) {
					$uploadMessage = "Fichier téléchargé avec succès: " . htmlspecialchars($result['filename']);
					$messageType = 'success';
				} else {
					$uploadMessage = "Erreurs de téléchargement:<br>" . implode('<br>', array_map('htmlspecialchars', $result['errors']));
					$messageType = 'danger';
				}
			}
		}
		
		// Display message
		if (!empty($uploadMessage)) {
			echo "<div class='alert alert-{$messageType}'>{$uploadMessage}</div>";
		}
		
		// Clean old files periodically (1% chance)
		if (rand(1, 100) === 1) {
			$secureUpload->cleanOldFiles(30);
		}
	?>
	<form enctype="multipart/form-data" action="index.php?page=file_upload" method="POST">
		<?php echo CSRFProtection::getTokenField(); ?>
		
		<div class="mb-3">
			<label for="myFile" class="form-label">Sélectionner un fichier:</label>
			<input class="form-control" type="file" id="myFile" name="myFile" required 
			       accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">
			<div class="form-text">
				Types autorisés: JPG, PNG, GIF, PDF, TXT<br>
				Taille maximale: <?php echo defined('MAX_FILE_SIZE') ? number_format(MAX_FILE_SIZE / 1024 / 1024, 1) : '5'; ?> MB
			</div>
		</div>
		
		<button type="submit" class="btn btn-primary" name="upload">
			<i class="fas fa-upload"></i> Télécharger le fichier
		</button>
	</form>
	<?php
		// Display uploaded files securely
		$uploadedFiles = $secureUpload->getUploadedFiles();
		
		if (!empty($uploadedFiles)) {
			echo "<div class='mt-4'><h5>Fichiers téléchargés:</h5>";
			echo "<div class='row'>";
			
			foreach ($uploadedFiles as $file) {
				echo "<div class='col-md-4 mb-3'>";
				echo "<div class='card'>";
				
				if ($file['is_image']) {
					echo "<img src='uploads/" . htmlspecialchars($file['name']) . "' class='card-img-top' style='height: 200px; object-fit: cover;' alt='Image'>";
				} else {
					echo "<div class='card-img-top bg-light d-flex align-items-center justify-content-center' style='height: 200px;'>";
					echo "<i class='fas fa-file-" . ($file['extension'] === 'pdf' ? 'pdf' : 'alt') . " fa-3x text-secondary'></i>";
					echo "</div>";
				}
				
				echo "<div class='card-body'>";
				echo "<h6 class='card-title'>" . htmlspecialchars(substr($file['name'], 0, 30)) . "</h6>";
				echo "<p class='card-text'><small class='text-muted'>";
				echo "Taille: " . number_format($file['size'] / 1024, 1) . " KB<br>";
				echo "Modifié: " . date('d/m/Y H:i', $file['modified']);
				echo "</small></p>";
				echo "<a href='uploads/" . htmlspecialchars($file['name']) . "' class='btn btn-sm btn-outline-primary' target='_blank'>";
				echo "<i class='fas fa-eye'></i> Voir";
				echo "</a>";
				echo "</div>";
				echo "</div>";
				echo "</div>";
			}
			
			echo "</div>";
			echo "</div>";
		} else {
			echo "<div class='mt-4 text-muted'>Aucun fichier téléchargé.</div>";
		}
	
		// Display security information
		echo "<div class='mt-4 alert alert-info'><h6>Mesures de sécurité implémentées:</h6>";
		echo "<ul class='mb-0'>";
		echo "<li>Validation des extensions et types MIME</li>";
		echo "<li>Vérification de la taille des fichiers</li>";
		echo "<li>Renommage sécurisé des fichiers</li>";
		echo "<li>Protection du répertoire d'upload (.htaccess)</li>";
		echo "<li>Validation des images avec getimagesize()</li>";
		echo "<li>Protection CSRF</li>";
		echo "</ul></div>";
		
		include ("_partial/soluce.php"); 
	?>
</div>