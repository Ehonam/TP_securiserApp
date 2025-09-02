<?php
/**
 * Security Training Application - Main Entry Point
 * 
 * EDUCATIONAL NOTE: This application intentionally contains security
 * vulnerabilities for training purposes. Do not use in production!
 */

// Initialize application
define('APP_INIT', true);
session_start();

// Load configuration
require_once 'config.php';

// Get requested page with input validation
$requestedPage = $_GET['page'] ?? 'content';

// SECURITY IMPROVEMENT: Use whitelist from config instead of file_exists check
if (!in_array($requestedPage, $allowedPages)) {
    $requestedPage = 'content'; // Default to safe page
}

// Final check that file exists
if (!file_exists("pages/{$requestedPage}.php")) {
    header("Location: index.php");
    exit();
}

$strPage = $requestedPage;

// Set vulnerability information for the current page
$currentVulnerability = getVulnerabilityInfo($strPage);
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

<?php include ("_partial/footer.php"); ?>  