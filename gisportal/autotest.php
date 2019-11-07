<?php
require('basicPage.php');
require('openshift-api.php');
require('memory.php');

$errors=0;

function is_connected_fsockopen($www) {
    $connected = @fsockopen($www, 80); 
    if ($connected){
        fclose($connected);
		return true;
    }
    return false;
}

function is_connected_ping($www) {
	exec('ping -c 4 '.$www.' 2>&1', $output, $retval);
	if ($retval != 0) {return false;}
	return true; 
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
		return false;
	}
	return true;
}
 
$check_api=false;
$openshift_api->command('GET','endpoint');
if ($openshift_api->response->kind=='EndpointsList') {
	$check_api=true;
}

$sites_to_check=['google.com','github.com'];
$site_check=true;
foreach ($sites_to_check as $site) {
	if (!is_connected_fsockopen($site)) {$site_check=false;}
	if (!is_connected_ping($site)) {$site_check=false;}
}

$mem_check=false;
if (file_exists('/geo-mappen')) {$mem_check=true;}
if (!($memory->persistent_afk>' ')) {$mem_check=false;}

$data = ['memory-error'=>$mem_check,'internal-api-traffic-error'=>$check_api,'mysql-error'=>dbTest(),'external-internet-traffic-error'=>$site_check];
header('Content-Type: application/json');
echo json_encode($data);
?>