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
			$ond=$db->selectOne('onderwerpen',$velden,'id='.$id.($is_afd_admin?' AND afdeling='.$my_afd:''));
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
				$routes=$db->select('geopackages','id,kaartnaam','onderwerp='.$ond['id']);
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
						$msg='';
						if ($ond['id']==0) {
							$ond['id']=$db->insert('onderwerpen',$a);
						} else {
							if ($ond['afkorting']!=$a['Qafkorting']) {
								if ($routes) {
									require('../openshift-api.php');
									for($t=0;$t<count($routes);$t++) {
										$route=$routes[$t];
										$openshift_api->deleteDeploymentConfig($route['id'],['route']);
										// Wacht tot route weg is
										$maxAant=3; // wacht maximaal 0.3 seconden
										while ($maxAant>0) {
											$openshift_api->command('apis/route.openshift.io/v1','routes'.'/gpid-'.$route['id']);
											if ($openshift_api->response->status=='Failure' && $openshift_api->response->reason=='NotFound') {
												$maxAant=0;
											} else {
												$maxAant--;
												usleep(100000); // 100.000 microseconden is 0.1 seconde
											}
										}
										$openshift_api->createDeploymentConfig('../',$route['id'],$a['Qafkorting'],$route['kaartnaam'],'onnodig','onnodig',['route']);
									}
									$msg='<br><br><b>Let op:</b> Er zijn '.count($routes).' kaarten die door deze wijziging een nieuwe URL hebben gekregen.';
								}
							}
							$db->update('onderwerpen',$a,'id='.$ond['id']);
						}
						$basicPage->redirect('/geo/portal/beheer/index.php?tab=1',false,'Opslaan','Het onderwerp is opgeslagen.'.$msg);
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
				$r.='<div style="display: inline-block;"><form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
					$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/geo/portal/beheer/index.php?tab=1">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Dit onderwerp verwijderen?<br><br>NB: Dit kan niet ongedaan worden gemaakt.\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($ond['naam']).'" size="32"></td></tr>';
				$r.='<tr><td>(deel van) URL:</td><td><input name="afkorting" id="afk" value="'.htmlspecialchars($ond['afkorting']).'" size="8"><input id="afk_oud" value="'.htmlspecialchars($ond['afkorting']).'" type="hidden"></td></tr>';
				$r.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect('afdeling',$ond['afdeling'],$afds,$is_afd_admin).'</td></tr>';
				$r.='<tr><td colspan="2"><div id="areYouSure" style="display: none;" class="areYouSure">Door deze wijziging verandert de URL van '.count($routes).' kaarten met dit onderwerp.<br><br><input type="checkbox" id="areYouSureCheck"'.(count($routes)==0?' checked="checked"':'').'><label for="areYouSureCheck"> Ik wil deze wijziging inderdaad doorvoeren.</label></div></td></tr>';
				$r.='</table></form>';
				$r.='<div class="button-below"><button onclick="formOnderwerpOpslaan();">Opslaan</button></div>';
				$r.='</div>';
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