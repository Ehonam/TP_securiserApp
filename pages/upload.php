<?php
// pages/upload.php - SÉCURISÉ
require_once __DIR__ . '/../includes/security.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide";
    } else {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = "Aucun fichier sélectionné";
        } else {
            $file = $_FILES['file'];
            
            // ✅ VALIDATION SÉCURISÉE COMPLÈTE
            $validationErrors = Security::validateUploadedFile($file);
            
            if (!empty($validationErrors)) {
                $error = implode('<br>', $validationErrors);
                
                // Log tentative upload malveillant
                $stmt = $db->prepare("INSERT INTO security_logs (event_type, description, ip_address, user_id, severity) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['dangerous_upload_attempt', "Upload suspect: " . $file['name'], $_SERVER['REMOTE_ADDR'], $_SESSION['user_id'], 'high']);
            } else {
                // ✅ GÉNÉRATION NOM SÉCURISÉ
                $secureFilename = Security::generateSecureFilename($file['name']);
                $targetPath = __DIR__ . '/../uploads/' . $secureFilename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // ✅ ENREGISTREMENT BDD
                    $stmt = $db->prepare("INSERT INTO uploaded_files (user_id, original_name, stored_name, file_path, file_size, mime_type, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $file['name'], $secureFilename, 'uploads/' . $secureFilename, $file['size'], $file['type'], $_SERVER['REMOTE_ADDR']]);
                    
                    $message = "Fichier uploadé avec succès : " . htmlspecialchars($secureFilename);
                } else {
                    $error = "Erreur lors du déplacement du fichier";
                }
            }
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880"> <!-- 5MB -->
    
    <label>Fichier image (JPG, PNG, GIF max 5MB) :</label>
    <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif" required>
    <button type="submit">Uploader</button>
</form>