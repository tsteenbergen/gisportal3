<?php  //Start the Session

require('basicPage.php');

if ($loggedIn){
	if (!$loggedInViaWindowsUser && isset($_POST['mijn-pwd1']) && isset($_POST['mijn-pwd2'])) {
		$pwd1=$_POST['mijn-pwd1'];
		$pwd2=$_POST['mijn-pwd2'];
		if ($pwd1==$pwd2) {
			if (strlen($pwd1)>=6) {
				$db->update('personen',array('Qpassword'=>md5($pwd1)),'id='.$_SESSION['user']);
				$basicPage->meld('Password wijzigen','Het password is gewijzigd.');
			} else {
				$basicPage->fout('Password wijzigen','Het nieuwe password moet minimaal 6 tekens bevatten.');
			}
		} else {
			$basicPage->fout('Password wijzigen','Het nieuwe password is niet gelijk aan de bevestiging.');
		}
	}
	$r='Je bent ingelogd als: '.$username.'<br>';
	if (!$loggedInViaWindowsUser) {
		$r.='<form method="POST">';
		$r.='<div><label class="pwd-label" for="mijn-pwd1">Nieuw password:</label><input type="password" name="mijn-pwd1" id="mijn-pwd1" placeholder="Nieuw password" size="32" required></div>';
		$r.='<div><label for="mijn-pwd2" class="pwd-label">Bevestig password:</label><input type="password" name="mijn-pwd2" id="mijn-pwd2" placeholder="Bevestig password" size="32" required></div>';
		$r.='<button type="submit" class="button-right">Password wijzigen</button>';
		$r.='</form>';
	}
} else {
    $basicPage->fout('Autorisatie','Je bent niet ingelogd.');
}
$basicPage->render('Mijn &hellip;',$r);
?>