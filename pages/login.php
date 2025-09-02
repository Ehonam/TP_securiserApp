<?php 
/**
 * Secure Authentication System
 * 
 * SECURE VERSION: Uses prepared statements and brute force protection
 */

include("connect.php");
require_once __DIR__ . '/../auth_security.php';
require_once __DIR__ . '/../csrf_protection.php';

$arrUser = false;
$loginError = '';
$loginSuccess = false;
$remainingLockout = 0;

// Initialize auth security
$authSecurity = new AuthSecurity($db);
$userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Check if IP is currently locked
$remainingLockout = $authSecurity->getRemainingLockoutTime($userIp);

// Process login attempt (POST only for security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    // Validate CSRF token
    if (!CSRFProtection::validateFromData($_POST)) {
        $loginError = 'Erreur de sécurité: Token CSRF invalide.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'] ?? '';
        
        // Attempt authentication with security checks
        $authResult = $authSecurity->authenticate($username, $password, $userIp);
        
        if ($authResult['success']) {
            $arrUser = $authResult['user'];
            $loginSuccess = true;
            
            // Start secure session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $arrUser['id'];
            $_SESSION['username'] = $arrUser['login'];
            $_SESSION['login_time'] = time();
        } else {
            $loginError = $authResult['error'];
            if (isset($authResult['lockout_time'])) {
                $remainingLockout = $authSecurity->getRemainingLockoutTime($userIp);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username'])) {
    // For backward compatibility, but show warning
    $loginError = 'Utilisez le formulaire sécurisé ci-dessous pour vous connecter.';
}

// Clean old login attempts periodically (1% chance)
if (rand(1, 100) === 1) {
    $authSecurity->cleanOldLoginAttempts();
}
?>
<div class="py-4">
	<?php if (!empty($loginError)): ?>
		<div class="alert alert-danger" role="alert">
			<?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
		</div>
	<?php endif; ?>
	
	<?php if ($remainingLockout > 0): ?>
		<div class="alert alert-warning" role="alert">
			<strong>Compte temporairement bloqué</strong><br>
			Trop de tentatives de connexion échouées. Veuillez attendre 
			<span id="countdown"><?php echo $remainingLockout; ?></span> secondes.
		</div>
		<script>
		let countdown = <?php echo $remainingLockout; ?>;
		const countdownEl = document.getElementById('countdown');
		const timer = setInterval(() => {
			countdown--;
			countdownEl.textContent = countdown;
			if (countdown <= 0) {
				clearInterval(timer);
				location.reload();
			}
		}, 1000);
		</script>
	<?php else: ?>
		<form method="POST" action="#">
			<input type="hidden" name="page" value="<?php echo htmlspecialchars($strPage, ENT_QUOTES, 'UTF-8'); ?>">
			<?php echo CSRFProtection::getTokenField(); ?>
			
			<div class="mb-3">
				<label for="username" class="form-label">Nom d'utilisateur:</label>
				<input class="form-control" 
				       type="text" 
				       id="username"
				       name="username" 
				       required
				       autocomplete="username"
				       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
			</div>
			
			<div class="mb-3">
				<label for="password" class="form-label">Mot de passe:</label>
				<input class="form-control" 
				       type="password" 
				       id="password"
				       name="password"
				       required
				       autocomplete="current-password">
				<small class="form-text text-muted">Utilisez un mot de passe fort (8+ caractères)</small>
			</div>
			
			<button type="submit" class="btn btn-primary" name="login">Se connecter</button>
		</form>
	<?php endif; ?>
</div>

<?php if ($loginSuccess && $arrUser): ?>
	<div class="alert alert-success" role="alert">
		<h4>Connexion réussie!</h4>
		<p>Bienvenue <?php echo htmlspecialchars($arrUser['name'], ENT_QUOTES, 'UTF-8'); ?>!</p>
		<p><small class="text-muted">Session sécurisée établie.</small></p>
		
		<?php if ($strPage === "csrf"): ?>
			<small class="text-muted">Accès autorisé pour la démonstration CSRF.</small>
		<?php endif; ?>
	</div>
<?php elseif (!empty($loginError)): ?>
	<div class="alert alert-danger" role="alert">
		<strong>Échec de la connexion</strong><br>
		<?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
		<?php if ($remainingLockout > 0): ?>
			<br><small>Compte bloqué pour <?php echo $remainingLockout; ?> secondes.</small>
		<?php endif; ?>
	</div>
<?php endif; ?>