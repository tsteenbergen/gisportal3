<?php
require('../basicPage.php');

$title='Beheer onderwerp';
$r='';
if ($loggedIn && ($is_afd_admin || $is_admin)){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,afdeling,naam,afkorting';
		if ($id>=1) {
			$ond=$db->selectOne('onderwerpen',$velden,'id='.$id.($https://github.com/rivm-syso/gisportal.gitis_afd_admin?' AND afdeling='.$my_afd:''));
		} else {
			$id=0;
			$ond=array('id'=>0,'naam'=>'','afdeling'=>$my_afd);
			$title='Nieuw onderwerp';
		}
		if ($func=='delete') {
			$a=$db->select('geopackages','id','onderwerp='.$ond['id']);
			if (!$a) {
				if ($ond['id']>=1) {
					$db->delete('onderwerpen','id='.$ond['id']);
					$basicPage->redirect('/geo/portal/beheer/index.php?tab=1',false,'Verwijderen','Het onderwerp is verwijderd.');
				} else {
					$basicPage->fout('Internal error','Onderwerp niet gevonden.');
				}
			} else {
				$basicPage->redirect('/geo/portal/beheer/index.php?tab=1',true,'Verwijderen','Er zijn nog geopackages aan dit onderwerp gekoppeld. Verwijder deze eerst.');
			}
		} else {
			if ($ond) {
				if ($func=='opslaan') {
					$afd=($is_admin?$_POST['afdeling']:$my_afd);
					$a=array(
						'Qnaam'=>$db->validateString($_POST['naam'],'naam',1,64,'Er is geen naam opgegeven','De naam is te lang (max 64 tekens)',array('onderwerpen','naam=\''.$_POST['naam'].'\' AND afdeling='.$afd.' AND id<>'.$ond['id'],'Dit onderwerp komt al in de database voor')),
						'Qafkorting'=>$db->validateString($_POST['afkorting'],'afkorting',1,32,'Er is geen afkorting opgegeven','De afkorting is te lang (max 32 tekens)',array('onderwerpen','afkorting=\''.$_POST['afkorting'].'\' AND afdeling='.$afd.' AND id<>'.$ond['id'],'Deze afkorting komt al in de database voor')),
						'afdeling'=>$afd
					);
					if ($a['Qafkorting']!='') {
						for ($t=0;$t<strlen($a['Qafkorting']);$t++) {
							$c=substr($a['Qafkorting'],$t,1);
							if (! (  ($c>='a' && $c<='z') || ($c>='A' && $c<='Z') || ($c>='0' && $c<='9') || $c=='-' || $c=='_'  )  ) {
								$db->foutMeldingen[]=['afkorting','De URL mag alleen letters, cijfers, - of _ bevatten'];
								$t=strlen($a['Qafkorting']);
							}
						}
					}
					if (!$db->foutMeldingen) {
						if ($ond['id']==0) {
							$ond['id']=$db->insert('onderwerpen',$a);
						} else {
							if ($ond['afkorting']!=$a['Qafkorting']) {
								$routes=$db->select('geopackages','id,version,kaartnaam','onderwerp='.$ond['id']);
$basicPage->writeLog('$routes='.var_export($routes,true));
								if ($routes) {
									require('../openshift-api.php');
									for($t=0;$t<count($routes);$t++) {
										$route=$routes[$t];
										$openshift_api->deleteDeploymentConfig($route['id'],['routes']);
										// Wacht tot route weg is
										$maxAant=3; // wacht maximaal 0.3 seconden
										while ($maxAant>0) {
											$openshift_api->command('apis/route.openshift.io/v1','routes'.'?labelSelector=name=gpid-'.$route['id']);
											if ($this->response->status=='Failure' && $this->response->reason=='NotFound') {
												$maxAant=0;
											}
											$maxAant--;
											if ($maxAant>=0) {usleep(100000);} // 100.000 microseconden is 0.1 seconde
										}
										$openshift_api->createDeploymentConfig('../',$routes['id'],$a['Qafkorting'],$routes['kaartnaam'],'onnodig','onnodig',['routes']);
									}
								}
							}
							$db->update('onderwerpen',$a,'id='.$ond['id']);
						}
						$basicPage->redirect('/geo/portal/beheer/index.php?tab=1',false,'Opslaan','Het onderwerp is opgeslagen.');
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$ond['naam']=$a['Qnaam'];
						$ond['afkorting']=$a['Qafkorting'];
						$ond['afdeling']=$a['afdeling'];
					}
				}
				$a=$db->select('afdelingen','id,naam','id>=1','naam');
				$afds=[];
				if ($a) {foreach ($a as $b) {$afds[]=$b['id'].'='.htmlspecialchars($b['naam']);}}
				$r.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
					$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/geo/portal/beheer/index.php?tab=1">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Dit onderwerp verwijderen?<br><br>NB: Dit kan niet ongedaan worden gemaakt.\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($ond['naam']).'" size="32"></td></tr>';
				$r.='<tr><td>(deel van) URL:</td><td><input name="afkorting" value="'.htmlspecialchars($ond['afkorting']).'" size="8"></td></tr>';
				$r.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect('afdeling',$ond['afdeling'],$afds,$is_afd_admin).'</td></tr>';
				$r.='<tr><td colspan="2" class="button-below"><button onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();">Opslaan</button></td></tr>';
				$r.='</table></form>';
			} else {
				$basicPage->fout('Internal error','Onderwerp niet gevonden.');
			}
		}
	} else {
		$basicPage->fout('Internal error','Er is geen ID opgegeven.');
	}
} else {
	$basicPage->fout('Autorisatie','Je hebt niet de juiste autorisatie.');
}

$basicPage->render($title,$r);
?>