<?php
require('basicPage.php');

if ($loggedIn){
	if ($_POST['logout']=='logout'){
		session_destroy();
		$basicPage->redirect('login.php');
	}
	$r='<form method="POST">';
	$r.='<input type="hidden" name="logout" value="logout">';
	$r.='<button type="submit">Uitloggen</button>';
	$r.='</form>';
} else {
    $basicPage->fout('Autorisatie','Je bent niet ingelogd.');
	$r='';
}

$basicPage->render('Uitloggen',$r);
?>