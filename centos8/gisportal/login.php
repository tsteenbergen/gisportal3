<?php  //Start the Session
require('basicPage.php');

// Om database wijzigingen te kunnen doen, is het volgende gemaakt:
// - In de database is de versie opgenomen
// - In de map 'database-version' staan sql-file met sql-commando's.
//   De eerste file is database-version-1.sql met daarin het aanmaken van alle basistabellen.
// - Als er 1 of meer files bestaan met een hoger versienummer, dan worden deze files uitgevoerd en wordt het database-versienummer opgehoogd, zodat dit niet nogmaals gebeurt.
$database_msg='';
$database_version=$db->selectOne('database_version','version','id=1');
if ($database_version) {
	$database_version=(int)$database_version['version'];
} else {
	$database_version=0;
}
$fnaam='database-versions/database-version-'.($database_version+1).'.sql';
while (file_exists($fnaam)) {
	$database_version++;
	$db->update('database_version',array('version'=>$database_version),'id=1',false);
	$database_msg.=($database_msg==''?'':'<br>').'Update to version '.$database_version.':<ul>';
	$fn = fopen($fnaam,"r");
	while(! feof($fn))  {
		$regel = trim(fgets($fn));
		if ($regel!='' && substr($regel,0,2)!='//') {
			$database_msg.='<li>'.$regel.'</li>';
			$ans=$db->query($regel,false,false);
		}
	}
	fclose($fn);
	$database_msg.='</ul>';
	$fnaam='database-versions/database-version-'.($database_version+1).'.sql';
}
if ($database_msg=='') {$database_msg='Database-version '.$database_version.' (no update required)';}

function rGlobs($dir, &$answer) {
	$dir = rtrim($dir,'/');
	if (!is_dir($dir)) return;
	foreach (scandir($dir) as $fileName) {
		if ($fileName!='.' && $fileName!='..') {
			$dir2 = $dir.'/'.$fileName;
			if (!is_dir($dir2)) {
				$answer[]=$dir2;
			} else {
				rGlobs($dir2,$answer);
			}
		}
	}
}

if (isset($_POST['username']) and isset($_POST['password'])){
	$username = $_POST['username'];
	$password = $_POST['password'];
	$user=$db->selectOne('personen','id,naam,afdeling,password,afd_admin,admin','email=\''.$username.'\'');
	if ($user && md5($password)==$user['password']) { // ook in basicPage.php
		// controleer of geo-mappen bestaat en valide is
		if (file_exists($basicPage->getConfig('geo-mappen'))) {
			$_SESSION['username'] = $username;
			$_SESSION['user'] = $user['id'];
			$_SESSION['afdeling'] = $user['afdeling'];
			$_SESSION['is_admin'] = ($user['admin']=='J');
			$_SESSION['is_afd_admin'] = ($user['afd_admin']=='J');
			$basicPage->writeLog('Administrator \''.$_SESSION['username'].'\' logged in.','',true);
			$basicPage->writeLog($database_msg);
			// Uitvoeren diverse testen
			require ('openshift-api.php');
			// Test bestaan api
			$openshift_api->command('GET','endpoint');
			$basicPage->writeLog($openshift_api->response->kind=='EndpointsList'?'API is ok.':'<b style="background-color: red;color: white;padding: 0px 8px;">Error:</b> API not available. Check $api_url in openshift-api.php and/or the permissions of the serviceaccount gisbeheer.');
			$redir='index.php';
			if (isset($_GET['to'])) {
				$to=explode(',',$_GET['to']);
				switch ($to[0]) {
					case 'geo-package': $redir='/geo/portal/beheer/geo-package.php?id='.$to[1]; break;
				}
				
			}
			$basicPage->redirect($redir);
		} else {
			$basicPage->fout('Critical error','Je hebt zojuist proberen in te loggen. Dit is mislukt, omdat er geen storage aan de applicatie is toegewezen. Meld a.j.b. direct aan de beheerder(s): Critical error; No storage \'geo-mappen\' added to POD gisportal.');
		}
	} else{
		$basicPage->fout('Inloggen','Onbekende E-mail of password .');
	}
}
if ($loggedIn){
	$username = $_SESSION['username'];
	$r='Je bent ingelogd als: '.$username.'<br>';
} else {
	$r='<form method="POST">';
	$r.='<div><label class="login-label" for="inputEmail">E-mail:</label><input type="text" name="username" id="inputEmail" placeholder="Username" size="32" required value="'.$username.'"></div>';
    $r.='<div><label for="inputPassword" class="login-label">Password:</label><input type="password" name="password" id="inputPassword" placeholder="Password" size="32" required value="'.$password.'"></div>';
    $r.='<button type="submit" class="button-right">Inloggen</button>';
    $r.='</form>';
}
$basicPage->render('Inloggen',$r);
?>