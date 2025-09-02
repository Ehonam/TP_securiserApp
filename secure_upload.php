<?php
/**
 * Secure File Upload System
 * 
 * Provides secure file upload functionality with multiple validation layers
 * and protection against common attack vectors.
 */

class SecureFileUpload {
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];
    private $allowedMimeTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf', 'text/plain'
    ];
    private $maxFileSize = 5242880; // 5MB
    private $uploadDir = './uploads/';
    
    public function __construct() {
        // Load configuration
        if (defined('MAX_FILE_SIZE')) {
            $this->maxFileSize = MAX_FILE_SIZE;
        }
        if (defined('UPLOAD_PATH')) {
            $this->uploadDir = UPLOAD_PATH;
        }
        
        // Ensure upload directory exists and is secure
        $this->setupUploadDirectory();
    }
    
    /**
     * Setup secure upload directory
     */
    private function setupUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Create .htaccess file to prevent direct execution
        $htaccessContent = "# Secure uploads directory\n";
        $htaccessContent .= "Options -ExecCGI\n";
        $htaccessContent .= "RemoveHandler .php .phtml .php3\n";
        $htaccessContent .= "AddType text/plain .php .phtml .php3\n";
        
        file_put_contents($this->uploadDir . '.htaccess', $htaccessContent);
        
        // Create index.html to prevent directory listing
        file_put_contents($this->uploadDir . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Access Denied</h1></body></html>');
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @return array Validation result
     */
    public function validateFile($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Paramètres de fichier invalides.';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'Aucun fichier envoyé.';
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'Le fichier dépasse la taille maximale autorisée.';
                break;
            default:
                $errors[] = 'Erreur inconnue lors du téléchargement.';
                break;
        }
        
        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'Le fichier est trop volumineux. Taille maximale: ' . $this->formatBytes($this->maxFileSize);
        }
        
        // Get file extension
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file extension
        if (!in_array($extension, $this->allowedTypes)) {
            $errors[] = 'Type de fichier non autorisé. Extensions autorisées: ' . implode(', ', $this->allowedTypes);
        }
        
        // Validate MIME type
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $errors[] = 'Type MIME non autorisé: ' . $mimeType;
        }
        
        // Additional security checks for images
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = 'Fichier image invalide ou corrompu.';
            }
        }
        
        // Check for dangerous content in filename
        if (preg_match('/[^a-zA-Z0-9._-]/', basename($file['name']))) {
            $errors[] = 'Nom de fichier contient des caractères non autorisés.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Process file upload
     * 
     * @param array $file $_FILES array element
     * @return array Upload result
     */
    public function processUpload($file) {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        // Generate secure filename
        $extension = $validation['extension'];
        $basename = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $safeBasename = substr($safeBasename, 0, 50); // Limit length
        
        // Generate unique filename to prevent overwriting
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        $newFilename = $safeBasename . '_' . $timestamp . '_' . $randomString . '.' . $extension;
        
        $uploadPath = $this->uploadDir . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Set secure permissions
            chmod($uploadPath, 0644);
            
            // Log successful upload
            error_log("Secure upload: " . $newFilename . " from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            return [
                'success' => true,
                'filename' => $newFilename,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $validation['mime_type']
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Erreur lors du déplacement du fichier.']
            ];
        }
    }
    
    /**
     * Get list of uploaded files (secure)
     * 
     * @return array List of files with metadata
     */
    public function getUploadedFiles() {
        $files = [];
        
        if (!is_dir($this->uploadDir)) {
            return $files;
        }
        
        $scandir = scandir($this->uploadDir);
        
        foreach ($scandir as $filename) {
            if ($filename === '.' || $filename === '..' || 
                $filename === '.htaccess' || $filename === 'index.html') {
                continue;
            }
            
            $filePath = $this->uploadDir . $filename;
            
            if (is_file($filePath)) {
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Only show allowed file types
                if (in_array($extension, $this->allowedTypes)) {
                    $files[] = [
                        'name' => $filename,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'extension' => $extension,
                        'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])
                    ];
                }
            }
        }
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $files;
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Clean old uploaded files (maintenance)
     * 
     * @param int $daysOld Remove files older than this many days
     * @return int Number of files deleted
     */
    public function cleanOldFiles($daysOld = 30) {
        $deletedCount = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        if (!is_dir($this->uploadDir)) {
            return $deletedCount;
        }
        
        $files = scandir($this->uploadDir);
        
        foreach ($files as $filename) {
            if ($filename === '.' || $filename === '..' || 
                $filename === '.htaccess' || $filename === 'index.html') {
                continue;
            }
            
            $filePath = $this->uploadDir . $filename;
            
            if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }
}