<?php
require('../basicPage.php');
require('../openshift-api.php');

if ($loggedIn && ($is_admin || $is_afd_admin)){
	$tabA='';
	if ($is_admin) {
		$tabA='<div id="tabs-A" style="vertical-align: top;">';
		$afds=$db->select('afdelingen','id,naam','id>=1','naam');
		$tabA.='<table class="colored">';
		$tabA.='<tr class="top-button"><td colspan="2"><a class="small-button" href="/geo/portal/beheer/afdeling.php?id=0">Nieuwe afdeling</a></td></tr>';
		$tabA.='<tr class="header"><td style="min-width: 160px;">Naam</td><td></td></tr>';
		if ($afds) {
			foreach ($afds as $afd) {
				$tabA.='<tr><td>'.htmlspecialchars($afd['naam']).'</td><td><a class="small-button" href="/geo/portal/beheer/afdeling.php?id='.$afd['id'].'">Bewerk</a></td></tr>';
			}
		}
		$tabA.='</table></div>';

		$tabA.='<div id="tabs-A1" style="vertical-align: top;">';
		$imgs=$db->select('images','id,image,repo,deflt','id>=1','image');
		$tabA.='<table class="colored">';
		$tabA.='<tr class="top-button"><td colspan="4"><a class="small-button" href="/geo/portal/beheer/image.php?id=0">Nieuw image</a></td></tr>';
		$tabA.='<tr class="header"><td></td><td style="min-width: 160px;">Image</td><td>Repo</td><td></td><td style="width: 20px;"></td><td>Versions</td></tr>';
		if ($imgs) {
			foreach ($imgs as $img) {
				$vs=$db->select('versions','id,version,extensions,deflt','image='.$img['id']);
				$versions='<table>';
				if ($vs) foreach ($vs as $v) {
					$ext=str_replace(chr(13).chr(10),chr(13),htmlspecialchars($v['extensions']));
					$ext=str_replace(chr(10),chr(13),$ext);
					$ext=str_replace(chr(13),'<br>',$ext);
					$ext=str_replace('  ',' ',$ext);
					$versions.='<tr><td>'.($v['deflt']=='J'?'Default':'').'</td><td>'.$v['version'].'</td><td>'.$ext.'</td><td><a class="small-button" href="/geo/portal/beheer/version.php?id='.$v['id'].'&iid='.$img['id'].'">Bewerk</a></td></tr>';
				}
				$versions.='</table>';
				$tabA.='<tr><td>'.($img['deflt']=='J'?'Default':'').'</td><td>'.htmlspecialchars($img['image']).'</td><td>'.htmlspecialchars($img['repo']).'</td><td><a class="small-button" href="/geo/portal/beheer/image.php?id='.$img['id'].'">Bewerk image</a><a class="small-button" style="margin-left: 20px;" href="/geo/portal/beheer/version.php?id=0&iid='.$img['id'].'">Nieuwe versie</a></td><td></td><td>'.$versions.'</td></tr>';
			}
		}
		$tabA.='</table></div>';



		$tabA.='<div id="tabs-A2" style="vertical-align: top;">';
		$tabA.='<table class="colored">';
		$openshift_api->command('api','persistentvolumeclaims/'.$basicPage->persistent_storage);
/*		
		stdClass::__set_state(array( 
			'kind' => 'PersistentVolumeClaim', 
			'apiVersion' => 'v1', 
			'metadata' => stdClass::__set_state(array( 'name' => 'geomappen', 'namespace' => 'sscc-geoweb-co', 'selfLink' => '/api/v1/namespaces/sscc-geoweb-co/persistentvolumeclaims/geomappen', 'uid' => '6f69ab2b-fc86-11e9-8598-f2a7cee00114', 'resourceVersion' => '52472960', 'creationTimestamp' => '2019-11-01T09:03:13Z', 'annotations' => stdClass::__set_state(array( 'pv.kubernetes.io/bind-completed' => 'yes', 'pv.kubernetes.io/bound-by-controller' => 'yes', 'volume.beta.kubernetes.io/storage-provisioner' => 'kubernetes.io/glusterfs', )), 'finalizers' => array ( 0 => 'kubernetes.io/pvc-protection', ), )), 
			'spec' => stdClass::__set_state(array( 
				'accessModes' => array ( 0 => 'ReadWriteMany', ), 
				'resources' => stdClass::__set_state(array( 
					'requests' => stdClass::__set_state(array( 
						'storage' => '10Gi', )), )), 'volumeName' => 'pvc-6f69ab2b-fc86-11e9-8598-f2a7cee00114', 'storageClassName' => 'glusterfs-storage-expandable', )), 
			'status' => stdClass::__set_state(array( 'phase' => 'Bound', 'accessModes' => array ( 0 => 'ReadWriteMany', ), 'capacity' => stdClass::__set_state(array( 'storage' => '10Gi', )), )), 
		))
*/	
		$path=$basicPage->getConfig('geo-mappen');
		$mem='1'.var_export($openshift_api->response,true).'<br>';
		$mem.='2'.var_export($openshift_api->response->spec,true).'<br>';
		$mem.='3'.var_export($openshift_api->response->spec['resources'],true).'<br>';
		$mem.='4'.var_export($openshift_api->response->spec->resources->requests,true).'<br>';
		$mem.='5'.var_export($openshift_api->response->spec->resources->requests->storage,true).'<br>';
		$mem.=shell_exec('fd '.$path.' > '.$path.'/fd.fd').'<br>';
		$mem.='fd.fd:'.file_get_contents($path.'/fd.fd').'||||';
		$tabA.='<tr><td>Geheugen persistent storage:</td><td>'.$mem.'</td></tr>';
		$tabA.='</table></div>';
		
	}
	
	$tabO='<div id="tabs-O" style="vertical-align: top;">';
	$onds=$db->select('onderwerpen AS a LEFT JOIN afdelingen AS b ON a.afdeling=b.id','a.id,a.afkorting,a.naam,b.naam AS afd_naam','a.id>=1'.($is_afd_admin?' AND a.afdeling='.$my_afd:''),'afd_naam,a.naam');
	$tabO.='<table class="colored">';
	$tabO.='<tr class="top-button"><td colspan="4"><a class="small-button" href="/geo/portal/beheer/onderwerp.php?id=0">Nieuw onderwerp</a></td></tr>';
	$tabO.='<tr class="header"><td>Afdeling</td><td>Afkorting</td><td>Naam</td><td></td></tr>';
	if ($onds) {
		foreach ($onds as $ond) {
			$tabO.='<tr><td>'.htmlspecialchars($ond['afd_naam']).'</td><td>'.htmlspecialchars($ond['afkorting']).'</td><td>'.htmlspecialchars($ond['naam']).'</td><td><a class="small-button" href="/geo/portal/beheer/onderwerp.php?id='.$ond['id'].'">Bewerk</a></td></tr>';
		}
	}
	$tabO.='</table></div>';
	
	$tabP='<div id="tabs-P" style="vertical-align: top;">';
	$pers=$db->select('personen AS a LEFT JOIN afdelingen AS b ON a.afdeling=b.id','a.id,a.naam,b.naam AS afd_naam,a.ad_account,a.email,a.afd_admin,a.admin','a.id>=1'.($is_afd_admin?' AND a.afdeling='.$my_afd:''),'afd_naam,a.naam');
	$tabP.='<table class="colored">';
	$tabP.='<tr class="top-button"><td colspan="6"><div style="float: left;">Filter op:<input size="20" style="margin-left: 30px;" onkeyup="filterPersonen(this);"></div><a class="small-button" href="/geo/portal/beheer/persoon.php?id=0">Nieuwe persoon</a></td></tr>';
	$tabP.='<tr class="header"><td>Afdeling</td><td>Naam</td><td>AD account</td><td>E-mail</td><td>Autorisatie</td><td></td></tr>';
	if ($pers) {
		foreach ($pers as $per) {
			$tabP.='<tr class="TRpersoon"><td>'.htmlspecialchars($per['afd_naam']).'</td><td>'.htmlspecialchars($per['naam']).'</td><td>'.htmlspecialchars($per['ad_account']).'</td><td>'.htmlspecialchars($per['email']).'</td><td>'.($per['afd_admin']=='J'?'Afd. beheerder':($per['admin']=='J'?'Administrator':'')).'</td><td><a class="small-button" href="/geo/portal/beheer/persoon.php?id='.$per['id'].'">Bewerk</a></td></tr>';
		}
	}
	$tabP.='</table></div>';
//	$tabP.=var_export($db->query('select * from personen'),true);
//	$tabP.=var_export($db->select('audit_trail','*','id>1','id DESC'),true);
	
	$r.='<div id="tabs">';
	$r.='<ul><li><a href="#tabs-P">Personen</a></li><li><a href="#tabs-O">Onderwerpen</a></li>';
	if ($tabA!='') {$r.='<li><a href="#tabs-A">Afdelingen</a></li><li><a href="#tabs-A1">Images</a></li><li><a href="#tabs-A2">Geheugen</a></li>';}
	$r.='</ul>';
	$r.=$tabP.$tabO;
	if ($tabA!='') {$r.=$tabA;}
	$r.='</div>';
	$tab=$_GET['tab']; if ($tab=='') {$tab=0;}
	$basicPage->add_js_ready('$( "#tabs" ).tabs({active: '.$tab.',heightStyle: \'auto\'});');
} else {
    $basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render('Beheer',$r);
?>