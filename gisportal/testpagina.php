<?php
require('basicPage.php');
require('memory.php');

function is_connected_fsockopen($www) {
    $connected = @fsockopen($www, 80); 
    if ($connected){
        fclose($connected);
		return 'Connected';
    }
    return '<div class="test-error">Not connected</div>';
}

function is_connected_ping($www) {
	exec('ping -c 4 '.$www.' 2>&1', $output, $retval);
	if ($retval != 0) {return '<div class="test-error">Not connected</div>';}
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
		return '<div class="test-error">Database connect failed: '.mysqli_connect_error().'</div>';
	}
	$mysqli->query('USE '.$dbname);
	return 'Connected to database '.$dbname;
}
function dbTest2() {
	global $mysqli;
	
	$r='<div class="test-error">No result</div>';
	if ($mysqli) {
		$result = $mysqli->query('SELECT count(id) AS aantal FROM geopackages WHERE id>=1');
		$r=var_export($result,true);
		if ($result && $result!==true) {
			if ($result->fetch_assoc) {
				//$r=$result->fetch_assoc();
				//$r=$r['aantal'].' records';
			}
			$result->free();
		}
	}
	return $r;
}
  

$r.='Deze testpagina checkt een aantal zaken...<br><br>';

$r.='<h2>Connectie met internet</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Site</th><th>fsockopen, poort 80</th><th>Ping</th></tr>';
$sites_to_check=['google.com','github.com'];
foreach ($sites_to_check as $site) {
	$r.='<tr><td>'.$site.'</td><td>'.is_connected_fsockopen($site).'</td><td>'.is_connected_ping($site).'</td></tr>';
}
$r.='</table>';

$r.='<h2>Perisisent storage</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$r.='<tr><td colspan="2">Root map /geo-mappen:</td><td>'.(file_exists('/geo-mappen')?'Exists':'<div class="test-error">Does not exist</div>').'</td></tr>';
$r.='<tr><td colspan="2">Persistent storage vlgs Openshift:</td><td style="text-align: right;">'.($memory->persistent_afk>' '?$memory->persistent_afk:'<div class="test-error">Not retrieved</div>').'</td></tr>';
$r.='<tr><td>Openshift:</td><td style="text-align: right;">'.$memory->persistent.' b</td><td style="text-align: right;">'.$memory->persistent_mb.'</td></tr>';
$r.='<tr><td>Geheugen in gebruik:</td><td style="text-align: right;">'.$memory->used.' b</td></><td style="text-align: right;">'.$memory->used_mb.'</td></tr>';
$r.='<tr><td>Vrij geheugen:</td><td style="text-align: right;">'.$memory->available.' b</td><td style="text-align: right;">'.$memory->available_mb.'</td></tr>';
$r.='<tr><td>Totaal geheugen:</td><td style="text-align: right;">'.$memory->total.' b</td><td style="text-align: right;">'.$memory->total_mb.'</td></tr>';
$r.='</table>';

$r.='<h2>MYSQL</h2>';
$r.='<table style="margin-bottom: 32px;"><tr><th>Test</th><th>Resultaat</th></tr>';
$r.='<tr><td>Connect status:</td><td>'.dbTest().'</td></tr>';
$r.='<tr><td>SELECT count(id) FROM geopackages:</td><td>'.dbTest2().'</td></tr>';
$r.='</table>';


$basicPage->render('Testpagina',$r);
?>