<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    if (defined('SESSION_NAME') && SESSION_NAME) {
        session_name(SESSION_NAME);
    }
    session_start();
}
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/connect.php';
// Liste blanche des pages autorisées
$allowedPages = array_map(function($file) {
    return basename($file, '.php');
}, glob(__DIR__ . '/pages/*.php'));

$strPage = $_GET['page'] ?? 'content';
if (!in_array($strPage, $allowedPages)) {
    $strPage = 'content';
}
?>
<?php include ("_partial/head.php"); ?>

<main class="container">
    <?php include ("_partial/header.php"); ?>  
    <!-- Content -->
    <div class="row g-5 py-3">
    <?php 
        include ("_partial/col.php"); 
        include ("pages/".$strPage.".php");
    ?> 
    </div>
</main>
<?php include_once "_partial/footer.php"; ?>