<?php 
	include("connect.php");
	
	$arrUser = false;
	if (isset($_GET['username'])){
		$arrUser = $db->query("SELECT * FROM users WHERE login = '".$_GET['username']."' AND password = '".$_GET['password']."';")->fetch();
	}
?>
<div class="py-4">
	<form action="#">
		<input type="hidden" name="page" value="<?php echo $strPage; ?>">
		<p>
			<label>Username:</label>
			<input class="form-control" type="text" name="username">
		</p>
		<p>
			<label>Password:</label>
			<input class="form-control" type="password" name="password" >
		</p>
		<p>
			<input class="form-control btn btn-primary" type="submit" value="Se connecter" name="login" >
		</p>
</div>

<?php 
	if ($arrUser){
		echo "Bienvenue ".$arrUser['name'];
		if ($strPage == "csrf"){
			$_SESSION['user'] = $arrUser;
		}
	}
?>