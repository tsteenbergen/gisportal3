<?php  //Start the Session

require('basicPage.php');
require('openshift-api.php');

if ($loggedIn){
	$checks=$openshift_api->healthChecks($_POST['id']);
}
header('Content-Type: application/json');
echo json_encode($checks);
?>