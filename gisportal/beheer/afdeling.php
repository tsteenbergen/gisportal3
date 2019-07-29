<?php
require('../basicPage.php');

$title='Beheer afdeling';
$r='';
if ($loggedIn && $is_admin){
	if (isset($_GET['id'])) {
		$func=$_POST['func'];
		$id=$_GET['id'];
		$velden='id,naam';
		if ($id>=1) {
			$afd=$db->selectOne('afdelingen',$velden,'id='.$id);
			$r='<h2>Beheer afdeling</h2>';
		} else {
			$id=0;
			$afd=array('id'=>0,'naam'=>'');
			$title='Nieuwe afdeling';
		}
		if ($func=='delete') {
			$a=$db->select('personen','id','afdeling='.$afd['id']);
			if (!$a) {
				if ($afd['id']>=1) {
					if ($afd['id']>1) {
						$db->delete('afdelingen','id='.$afd['id']);
						$basicPage->redirect('/beheer/index.php?tab=2',false,'Verwijderen','De afdeling is verwijderd.');
					} else {
						$basicPage->fout('Internal error','Afdeling van superadmin kan niet worden verwijderd.');
					}
				} else {
					$basicPage->fout('Internal error','Afdeling niet gevonden.');
				}
			} else {
				$basicPage->redirect('/beheer/index.php?tab=2',true,'Verwijderen','Er zijn nog personen aan deze afdeling gekoppeld. Verwijder deze eerst.');
			}
		} else {
			if ($afd) {
				if ($func=='opslaan') {
					$a=array('Qnaam'=>$db->validateString($_POST['naam'],'naam',1,32,'Er is geen naam opgegeven','De naam is te lang (max 32 tekens)',array('afdelingen','naam=\''.$_POST['naam'].'\' AND id<>'.$afd['id'],'Deze afdeling komt al in de database voor')));
					if (!$db->foutMeldingen) {
						if ($afd['id']==0) {
							$afd['id']=$db->insert('afdelingen',$a);
						} else {
							$db->update('afdelingen',$a,'id='.$afd['id']);
						}
						$basicPage->redirect('/beheer/index.php?tab=2',false,'Opslaan','De afdeling is opgeslagen.');
					} else {
						$basicPage->add_js_inline('var foutmeldingen='.json_encode($db->foutMeldingen).';');
						// zorg dat de POST waarden weer worden getoond
						$afd['naam']=$a['Qnaam'];
					}
				}
				$r.='<form id="form" method="POST"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="func" id="func"><table>';
				if ($id>=1) {
					$r.='<tr><td colspan="2" class="button-top"><a class="small-button" style="float: left;" href="/beheer/index.php?tab=2">Annuleren</a><a class="small-button" onclick="areYouSure(\'Verwijderen\',\'Deze afdeling verwijderen?\',function () {$(\'#func\').val(\'delete\'); $(\'#form\').submit();});">Verwijderen</a></td></tr>';
				}
				$r.='<tr><td>Naam:</td><td><input name="naam" value="'.htmlspecialchars($afd['naam']).'" size="32"></td></tr>';
	//			$r.='<tr><td>E-mail:</td><td><input name="naam" value="'.htmlspecialchars($afd['email']).'" size="32"></td></tr>';
	//			$r.='<tr><td>Afdeling:</td><td>'.$basicPage->getSelect($afd['afdeling'],afds).'</td></tr>';
	//			$r.='<tr><td>Autorisatie:</td><td></td></tr>';
				$r.='<tr><td colspan="2" class="button-below"><button onclick="$(\'#func\').val(\'opslaan\'); $(\'#form\').submit();">Opslaan</button></td></tr>';
				$r.='</table></form>';
			} else {
				$basicPage->fout('Internal error','Afdeling niet gevonden.');
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