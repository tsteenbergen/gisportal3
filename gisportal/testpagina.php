<?php
require('basicPage.php');
require('openshift-api.php');
require('memory.php');

$errors=0;
function wrapError($txt) {
	$errors++;
	return '<div class="test-error">'.$txt.'</div>';
}

function is_connected_fsockopen($www) {
    $connected = @fsockopen($www, 80); 
    if ($connected){
        fclose($connected);
		return 'Connected';
    }
    return wrapError('Not connected');
}

function is_connected_ping($www) {
	exec('ping -c 4 '.$www.' 2>&1', $output, $retval);
	if ($retval != 0) {return wrapError('Not connected');}
	return 'Connected'; 
}

$mysqli=false;
function dbTest() {
	global $mysqli;
	
	$dbhost=getenv('MYSQL_SERVICE_HOST');
	$dbport=getenv('MYSQL_SERVICE_PORT');
	$dbname=getenv('databasename');
	$dbuser=getenv('databaseuser');
	$dbpassword=getenv('databasepassword');
	if ($dbhost!='' && $dbname!='' && $dbuser!='' && $dbpassword!='') {
		$mysqli = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname, (int)$dbport);
	}
	if (mysqli_connect_errno()) {
		$mysqli=false;
		return wrapError('Database connect failed: '.mysqli_connect_error());
	}
	$mysqli->query('USE '.$dbname);
	return 'Connected to database '.$dbname;
}
 

$test0.='<h2>Interne API-calls</h2>Het gisportaal moet API-calls kunnen doen om kaarten via het containerplatform beschikbaar te stellen (deploymentconfig, service, route, etc.).';
$test0.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$openshift_api->command('api','endpoints');
if ($openshift_api->response->kind=='EndpointsList') {
	$endpts='API accessible';
} else {
	$endpts=wrapError('API not accessible');
}
$test0.='<tr><td>Test API-call (list endpoints):</td><td>'.$endpts.'</td></tr>';
$test0.='</table>';

$test1.='<h2>Connectie met internet</h2>Er moeten kaart- en metadatagegevens vanaf het internet kunnen worden opgehaald. Ook moet github bereikbaar zijn voor Dockerfiles e.d.';
$sites_to_check=['google.com','github.com'];
$test0.='<table style="margin-bottom: 32px;"><tr><th>Domein</th><th>fsockopen poort 80</th><th>Ping</th></tr>';
foreach ($sites_to_check as $site) {
	$test1.='<tr><td>'.$site.'</td><td>'.is_connected_fsockopen($site).'</td><td>'.is_connected_ping($site).'</td></tr>';
}
$test1.='</table>';

$test2.='<h2>Perisisent storage</h2>Persistent storage is nodig om alle bestanden (kaarten) op te slaan.';
$test2.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$test2.='<tr><td colspan="2">Root map /geo-mappen:</td><td style="text-align: right;">'.(file_exists('/geo-mappen')?'Exists':'<div class="test-error">Does not exist</div>').'</td></tr>';
$test2.='<tr><td colspan="2">Persistent storage vlgs Openshift:</td><td style="text-align: right;">'.($memory->persistent_afk>' '?$memory->persistent_afk:'<div class="test-error">Not retrieved</div>').'</td></tr>';
$test2.='<tr><td>Openshift:</td><td style="text-align: right;">'.$memory->persistent.' b</td><td style="text-align: right;">'.$memory->persistent_mb.'</td></tr>';
$test2.='<tr><td>Geheugen in gebruik:</td><td style="text-align: right;">'.$memory->used.' b</td></><td style="text-align: right;">'.$memory->used_mb.'</td></tr>';
$test2.='<tr><td>Vrij geheugen:</td><td style="text-align: right;">'.$memory->available.' b</td><td style="text-align: right;">'.$memory->available_mb.'</td></tr>';
$test2.='<tr><td>Totaal geheugen:</td><td style="text-align: right;">'.$memory->total.' b</td><td style="text-align: right;">'.$memory->total_mb.'</td></tr>';
$test2.='</table>';

$test3.='<h2>MYSQL</h2>Er moet een MYSQL-database zijn waarin alle kaartdefinities staan.';
$test3.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$test3.='<tr><td>Connect status:</td><td>'.dbTest().'</td></tr>';
$test3.='</table>';

if ($errors==0) {
	$r.='Er zijn geen fouten geconstateerd.<br><br>';
} else {
	if ($errors==1) {
		$r='<b>LET OP:</b> Er is 1 fout geconstateerd.<br><br>';
	} else {
		$r='<b>LET OP:</b> Er zijn '.$errors.' fouten geconstateerd.<br><br>';
	}
}
$r.=$test0.$test1.$test2.$test3;

$basicPage->render('Testpagina',$r);
?>