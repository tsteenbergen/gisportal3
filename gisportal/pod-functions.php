<?php  //Start the Session

require('basicPage.php');
require('openshift-api.php');

$r=array('error'=>true,'msg'=>'Unauthorised action');
$func=$_GET['func'];
$id=$_GET['id'];
$geop=$db->selectOne('geopackages','id,naam,afdeling','id='.$id);

if ($loggedIn && ($is_admin || $geop['afdeling']==$_SESSION['afdeling'])){
	switch ($func) {
		case 'regenerate':
/*			$r['error']=false;
			$r['msg']='';
			$openshift_api->getPodInfo($id);
			if ($openshift_api->succes()) {
				$openshift_api->deletePod($id);
				// wacht tot de pod helemaal weg is
				for ($t=0;$t<60;$t++) {
					sleep(1);
					$openshift_api->getPodInfo($id);
					$r['msg'].=$t.' '.$openshift_api->succes().' '.$openshift_api->response->message.'<br>';
				}
				$r['msg'].='Existing pod stopped and deleted<br>';
			} else {
				$r['msg'].='No existing pod found.<br>';
			}
			$r['msg'].='New pod generated<br>';
			$openshift_api->createPod($id);*/
$openshift_api->command('apis','imagestreams');
$r['msg']=$openshift_api->responseToString().'<br>';
$openshift_api->command('apis','imagestreams/mapserver-sscc');
$r['msg']=$openshift_api->responseToString().'<br>';
			break;
		case 'create':
			$openshift_api->createDeploymentConfig($id);
			$r['error']=!$openshift_api->succes();
			$r['msg']=$openshift_api->responseToString();
			break;
		case 'delete':
			$openshift_api->deletePod($id);
			$r['error']=!$openshift_api->succes();
			$r['msg']=$openshift_api->responseToString();
			break;
		case 'monitor':
			$openshift_api->monitorPod($id);
			$r['error']=!$openshift_api->succes();
			$r['msg']=json_encode($openshift_api->response);
			$r['monitor']=true;
			break;
	}
}
echo json_encode($r);
?>