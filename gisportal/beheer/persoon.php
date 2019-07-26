<?php
require('basicPage.php');

$title='Beheer persoon';
$r='';
if ($loggedIn && ($is_afd_admin || $is_admin)){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,naam,email,ad_account,afdeling,afd_admin,admin';
		if ($id>=1) {
			$per=$db->selectOne('personen',$velden,'id='.$id.($is_afd_admin?' AND afdeling='.$my_afd:''));
		} else {
			$id=0;
			$per=array('id'=>0,'naam'=>'','afdeling'=>$my_afd);
			$title='Nieuwe persoon';
		}
		if ($func=='delete') {
			if ($per['id']>=1) {
				if ($per['id']>1) {
					$db->delete('personen','id='.$per['id']);
					$basicPage->redirect('/beheer/index.php',false,'Verwijderen','De persoon is verwijderd.');
				} else {
					$basicPage->fout('Internal error','Superadmin kan niet worden verwijderd.');
				}
			} else {
				$basicPage->fout('Internal error','Persoon niet gevonden.');
			}
		} else {
			if ($per) {
				if ($func=='opslaan') {
					$a=array(
						'Qnaam'=>$db->validateString($_POST['naam'],'naam',1,32,'Er is geen naam opgegeven','De naam is te lang (max 32 tekens)',array('personen','naam=\''.$_POST['naam'].'\' AND id<>'.$per['id'],'Deze naam komt al in de database voor')),
						'Qemail'=>$db->validateString($_POST['email'],'email',0,32,'','Het emailadres is te lang (max 32 tekens)',array('personen','email=\''.$_POST['email'].'\' AND id<>'.$per['id'],'Dit emailadres komt al in de database voor')),
						'Qad_account'=>$db->validateString($_POST['ad_account'],'ad_account',0,32,'','De accountnaam is te lang (max 32 tekens)',array('personen','ad_account=\''.$_POST['ad_account'].'\' AND id<>'.$per['id'],'Dit account komt al in de database voor')),
						'afdeling'=>($is_admin?$_POST['afdeling']:$my_afd),
						'Qafd_admin'=>($_POST['admin']==1?'J':'N'),
						'Qadmin'=>($_POST['admin']==2 && $is_admin?'J':'N')
					);
					if (!$db->foutMeldingen) {
						if ($per['id']==0) {
							$per['id']=$db->insert('personen',$a);
						} else {
							$db->update('personen',$a,'id='.$per['id']);
						}
						$basicPage->redirect('/beheer/index.php',false,'Opslaan','De persoon is opgeslagen.');
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$per['naam']=$a['Qnaam'];
						$per['email']=$a['Qemail'];
						$per['ad_account']=$a['Qad_account'];
						$per['afdeling']=$a['Qafdeling'];
						$per['afd_admin']=$a['Qafd_admin'];
						$per['admin']=$a['Qadmin'];
					}
				}
				$a=$db->select('afdelingen','id,naam','id>=1','naam');
				$afds=[];
				if ($a) {foreach ($a as $b) {$afds[]=$b['id'].'='.htmlspecialchars($b['naam']);}}
				$r.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
				$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/beheer/index.php">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Deze persoon verwijderen?<br><br>NB: Dit kan niet ongedaan worden gemaakt.\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($per['naam']).'" size="32"></td></tr>';
				$r.='<tr><td>AD-account:</td><td><input name="ad_account" value="'.htmlspecialchars($per['ad_account']).'" size="32"></td></tr>';
				$r.='<tr><td>E-mail:</td><td><input name="email" value="'.htmlspecialchars($per['email']).'" size="32"></td></tr>';
				$r.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect('afdeling',$per['afdeling'],$afds,$is_afd_admin).'</td></tr>';
				$auts=array('0=Geen autorisatie','1=Beheerder afdeling');
				if ($is_admin) {
					$auts[]='2=Administrator';
				}
				$r.='<tr><td>Autorisatie:</td><td>'.$basicPage->getSelect('admin',($per['afd_admin']=='J'?1:($per['admin']=='J'?2:0)),$auts).'</td></tr>';
				$r.='<tr><td colspan="2" class="button-below"><button onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();">Opslaan</button></td></tr>';
				$r.='</table></form>';
			} else {
				$basicPage->fout('Internal error','Persoon niet gevonden.');
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